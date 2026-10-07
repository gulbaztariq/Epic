<?php
/**
 * The three public forms (contact, volunteer, subscribe). Each is posted to the page
 * it sits on, validated with the Laravel site's rules and messages, saved as a
 * private item under EPIC → Messages / Volunteers / Subscribers, and the team is
 * emailed.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Forms {

	const FLASH_PREFIX = 'epic_flash_';

	/** @var array<string,mixed>|null */
	private static $flash = null;

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'handle' ), 20 );
		add_action( 'admin_post_epic_export_subscribers', array( __CLASS__, 'export_subscribers' ) );
		add_action( 'admin_notices', array( __CLASS__, 'export_button' ) );
		add_action( 'current_screen', array( __CLASS__, 'mark_read' ) );
		add_filter( 'post_class', array( __CLASS__, 'unread_class' ), 10, 3 );
		add_action( 'admin_head', array( __CLASS__, 'unread_css' ) );
	}

	/* ------------------------------------------------------------ the flash --- */

	/**
	 * The message and old values left by the last submission, once (like a session
	 * flash). Held under a short random token passed in ?epic_flash=, so it works
	 * without a session and survives page caching, which only sees the next GET.
	 *
	 * @return array{type?:string,messages?:string[],old?:array<string,string>}
	 */
	public static function flash(): array {
		if ( null !== self::$flash ) {
			return self::$flash;
		}

		self::$flash = array();
		$token       = isset( $_GET['epic_flash'] ) ? sanitize_key( wp_unslash( $_GET['epic_flash'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

		if ( '' !== $token ) {
			$stored = get_transient( self::FLASH_PREFIX . $token );
			if ( is_array( $stored ) ) {
				self::$flash = $stored;
				delete_transient( self::FLASH_PREFIX . $token );
			}
		}

		return self::$flash;
	}

	/** An old input value, for refilling a form after an error. */
	public static function old( string $key ): string {
		$flash = self::flash();

		return isset( $flash['old'][ $key ] ) ? (string) $flash['old'][ $key ] : '';
	}

	private static function finish( string $type, array $messages, array $old = array() ): void {
		$token = wp_generate_password( 12, false, false );
		set_transient( self::FLASH_PREFIX . strtolower( $token ), compact( 'type', 'messages', 'old' ), 10 * MINUTE_IN_SECONDS );

		$back = self::back_url();

		wp_safe_redirect( add_query_arg( 'epic_flash', strtolower( $token ), $back ), 303 );
		exit;
	}

	/**
	 * Where to send the visitor after a submission: the page they were on (the sign-up
	 * band sits on every page and posts to the subscribe page), else the page posted to.
	 */
	private static function back_url(): string {
		$referer = isset( $_SERVER['HTTP_REFERER'] ) ? wp_validate_redirect( esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ), '' ) : '';
		$uri     = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '';

		return remove_query_arg( 'epic_flash', $referer ?: ( $uri ?: home_url( '/' ) ) );
	}

	/* ------------------------------------------------------------- handling --- */

	public static function handle(): void {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['epic_form'] ) || is_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}

		$form = sanitize_key( wp_unslash( $_POST['epic_form'] ) ); // phpcs:ignore WordPress.Security.NonceVerification

		if ( ! in_array( $form, array( 'contact', 'volunteer', 'subscribe' ), true ) ) {
			return;
		}

		$input = array();
		foreach ( wp_unslash( $_POST ) as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
			if ( is_string( $value ) ) {
				$input[ sanitize_key( $key ) ] = trim( $value );
			}
		}

		// Honeypot: a real visitor never sees this field.
		if ( ! empty( $input['website'] ) ) {
			self::finish( 'error', array( 'The submission could not be accepted.' ), $input );
		}

		if ( self::throttled() ) {
			self::finish( 'error', array( 'Too many submissions from your connection. Please wait a few minutes and try again.' ), $input );
		}

		call_user_func( array( __CLASS__, 'process_' . $form ), $input );
	}

	/** At most ten submissions per connection every ten minutes. */
	private static function throttled(): bool {
		$key   = 'epic_rate_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$count = (int) get_transient( $key );

		if ( $count >= 10 ) {
			return true;
		}

		set_transient( $key, $count + 1, 10 * MINUTE_IN_SECONDS );

		return false;
	}

	/**
	 * Check fields against simple rules (required, email, max:N).
	 *
	 * @param array<string,string>               $input
	 * @param array<string,array<int,string>>    $rules
	 * @return string[] messages
	 */
	private static function validate( array $input, array $rules ): array {
		$errors = array();

		foreach ( $rules as $field => $checks ) {
			$value = $input[ $field ] ?? '';
			$label = str_replace( '_', ' ', $field );

			foreach ( $checks as $check ) {
				if ( 'required' === $check && '' === $value ) {
					$errors[] = "The {$label} field is required.";
					break;
				}

				if ( '' === $value ) {
					continue;
				}

				if ( 'email' === $check && ! is_email( $value ) ) {
					$errors[] = "The {$label} field must be a valid email address.";
				} elseif ( 0 === strpos( $check, 'max:' ) && mb_strlen( $value ) > (int) substr( $check, 4 ) ) {
					$errors[] = "The {$label} field must not be greater than " . (int) substr( $check, 4 ) . ' characters.';
				}
			}
		}

		return $errors;
	}

	private static function process_contact( array $input ): void {
		$errors = self::validate(
			$input,
			array(
				'name'         => array( 'required', 'max:120' ),
				'email'        => array( 'required', 'email', 'max:160' ),
				'phone'        => array( 'max:40' ),
				'organisation' => array( 'max:160' ),
				'subject'      => array( 'max:180' ),
				'message'      => array( 'required', 'max:5000' ),
			)
		);

		if ( $errors ) {
			self::finish( 'error', $errors, $input );
		}

		$id = self::store(
			'epic_message',
			$input['name'],
			array(
				'name'         => sanitize_text_field( $input['name'] ),
				'email'        => sanitize_email( $input['email'] ),
				'phone'        => sanitize_text_field( $input['phone'] ?? '' ),
				'organisation' => sanitize_text_field( $input['organisation'] ?? '' ),
				'subject'      => sanitize_text_field( $input['subject'] ?? '' ),
				'message'      => sanitize_textarea_field( $input['message'] ),
				'is_read'      => '0',
			)
		);

		self::notify(
			'New contact message from ' . sanitize_text_field( $input['name'] ),
			"Name: {$input['name']}\nEmail: {$input['email']}\nPhone: " . ( $input['phone'] ?? '' ) . "\nOrganisation: " . ( $input['organisation'] ?? '' ) . "\nSubject: " . ( $input['subject'] ?? '' ) . "\n\n{$input['message']}\n\n" . get_edit_post_link( $id, 'raw' ),
			$input['email']
		);

		self::finish( 'success', array( 'Thank you for reaching out. Our team will respond to you shortly.' ) );
	}

	private static function process_volunteer( array $input ): void {
		$errors = self::validate(
			$input,
			array(
				'name'         => array( 'required', 'max:120' ),
				'email'        => array( 'required', 'email', 'max:160' ),
				'phone'        => array( 'max:40' ),
				'city'         => array( 'max:120' ),
				'country'      => array( 'max:120' ),
				'interest'     => array( 'max:160' ),
				'availability' => array( 'max:120' ),
				'message'      => array( 'max:4000' ),
			)
		);

		$cv = 0;
		if ( ! empty( $_FILES['cv']['name'] ) ) {
			$upload = self::upload_cv( $_FILES['cv'] ); // phpcs:ignore WordPress.Security
			if ( is_wp_error( $upload ) ) {
				$errors[] = $upload->get_error_message();
			} else {
				$cv = $upload;
			}
		}

		if ( $errors ) {
			self::finish( 'error', $errors, $input );
		}

		$id = self::store(
			'epic_volunteer',
			$input['name'],
			array(
				'name'         => sanitize_text_field( $input['name'] ),
				'email'        => sanitize_email( $input['email'] ),
				'phone'        => sanitize_text_field( $input['phone'] ?? '' ),
				'city'         => sanitize_text_field( $input['city'] ?? '' ),
				'country'      => sanitize_text_field( $input['country'] ?? '' ),
				'interest'     => sanitize_text_field( $input['interest'] ?? '' ),
				'availability' => sanitize_text_field( $input['availability'] ?? '' ),
				'message'      => sanitize_textarea_field( $input['message'] ?? '' ),
				'cv_path'      => $cv ? (string) $cv : '',
				'is_read'      => '0',
			)
		);

		self::notify(
			'New volunteer application from ' . sanitize_text_field( $input['name'] ),
			"Name: {$input['name']}\nEmail: {$input['email']}\nPhone: " . ( $input['phone'] ?? '' ) . "\nInterest: " . ( $input['interest'] ?? '' ) . "\n\n" . ( $input['message'] ?? '' ) . "\n\n" . get_edit_post_link( $id, 'raw' ),
			$input['email']
		);

		self::finish( 'success', array( 'Thank you for your interest in volunteering with EPIC. We will be in touch.' ) );
	}

	private static function process_subscribe( array $input ): void {
		$errors = self::validate(
			$input,
			array(
				'name'         => array( 'max:120' ),
				'email'        => array( 'required', 'email', 'max:160' ),
				'organisation' => array( 'max:160' ),
			)
		);

		if ( $errors ) {
			self::finish( 'error', $errors, $input );
		}

		$email    = sanitize_email( $input['email'] );
		$existing = get_posts(
			array(
				'post_type'      => 'epic_subscriber',
				'post_status'    => 'any',
				'title'          => $email,
				'posts_per_page' => 1,
				'fields'         => 'ids',
			)
		);

		$meta = array(
			'name'         => sanitize_text_field( $input['name'] ?? '' ),
			'organisation' => sanitize_text_field( $input['organisation'] ?? '' ),
			'status'       => 'subscribed',
			'source'       => sanitize_text_field( $input['source'] ?? 'website' ),
		);

		if ( $existing ) {
			foreach ( $meta as $key => $value ) {
				update_post_meta( $existing[0], 'epic_' . $key, $value );
			}
		} else {
			self::store( 'epic_subscriber', $email, $meta );
		}

		self::finish( 'success', array( 'You are subscribed. Look out for EPIC research, events and insights in your inbox.' ) );
	}

	/**
	 * @param array<string,string> $meta
	 */
	private static function store( string $type, string $title, array $meta ): int {
		$id = wp_insert_post(
			array(
				'post_type'   => $type,
				'post_status' => 'publish',
				'post_title'  => sanitize_text_field( $title ),
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			self::finish( 'error', array( 'Sorry, something went wrong saving your details. Please try again.' ) );
		}

		foreach ( $meta as $key => $value ) {
			update_post_meta( $id, 'epic_' . $key, $value );
		}

		return (int) $id;
	}

	/** Email the team (best effort: the item is always saved in the dashboard). */
	private static function notify( string $subject, string $body, string $reply_to ): void {
		$to = epic_setting( 'notification_email' ) ?: epic_setting( 'contact_email' );

		if ( ! $to || ! is_email( $to ) ) {
			return;
		}

		wp_mail( $to, $subject, $body, array( 'Reply-To: ' . $reply_to ) );
	}

	/**
	 * Store an uploaded CV in the media library: pdf, doc or docx, up to 5 MB.
	 *
	 * @param array<string,mixed> $file
	 * @return int|WP_Error attachment id
	 */
	private static function upload_cv( array $file ) {
		if ( ! empty( $file['error'] ) && UPLOAD_ERR_NO_FILE !== (int) $file['error'] ) {
			return new WP_Error( 'cv', 'The cv could not be uploaded.' );
		}

		if ( (int) ( $file['size'] ?? 0 ) > 5 * MB_IN_BYTES ) {
			return new WP_Error( 'cv', 'The cv field must not be greater than 5120 kilobytes.' );
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$allowed = array(
			'pdf'  => 'application/pdf',
			'doc'  => 'application/msword',
			'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		);

		// CVs live in their own folder, kept out of search engines by robots.txt.
		$private_folder = static function ( $dir ) {
			$dir['subdir'] = '/epic-cv';
			$dir['path']   = $dir['basedir'] . '/epic-cv';
			$dir['url']    = $dir['baseurl'] . '/epic-cv';

			return $dir;
		};
		add_filter( 'upload_dir', $private_folder );

		$moved = wp_handle_upload(
			$file,
			array(
				'test_form' => false,
				'mimes'     => $allowed,
				'unique_filename_callback' => static fn( $dir, $name, $ext ) => sanitize_file_name( pathinfo( $name, PATHINFO_FILENAME ) . '-' . strtolower( wp_generate_password( 6, false, false ) ) . $ext ),
			)
		);

		remove_filter( 'upload_dir', $private_folder );

		if ( isset( $moved['error'] ) ) {
			return new WP_Error( 'cv', 'The cv field must be a file of type: pdf, doc, docx.' );
		}

		$index = dirname( $moved['file'] ) . '/index.html';
		if ( ! file_exists( $index ) ) {
			file_put_contents( $index, '' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- stops folder listings.
		}

		$id = wp_insert_attachment(
			array(
				'post_mime_type' => $moved['type'],
				'post_title'     => sanitize_file_name( wp_basename( $moved['file'] ) ),
				'post_status'    => 'inherit',
			),
			$moved['file']
		);

		return is_wp_error( $id ) ? $id : (int) $id;
	}

	/* ------------------------------------------------------------- the inbox --- */

	/** Opening a message or application marks it read. */
	public static function mark_read( $screen ): void {
		if ( ! $screen || 'post' !== $screen->base || ! in_array( $screen->post_type, array( 'epic_message', 'epic_volunteer' ), true ) ) {
			return;
		}

		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification

		if ( $post_id && current_user_can( 'edit_post', $post_id ) ) {
			update_post_meta( $post_id, 'epic_is_read', '1' );
		}
	}

	public static function unread_class( $classes, $class, $post_id ) {
		if ( is_admin() && in_array( get_post_type( $post_id ), array( 'epic_message', 'epic_volunteer' ), true ) && '0' === (string) get_post_meta( $post_id, 'epic_is_read', true ) ) {
			$classes[] = 'epic-unread';
		}

		return $classes;
	}

	public static function unread_css(): void {
		echo '<style>.epic-unread .row-title{font-weight:700}.epic-unread td,.epic-unread th{background:#f4f9ff}</style>';
	}

	/** An "Export CSV" button above the subscriber list. */
	public static function export_button(): void {
		$screen = get_current_screen();

		if ( ! $screen || 'edit-epic_subscriber' !== $screen->id ) {
			return;
		}

		printf(
			'<div class="notice notice-info inline"><p><a class="button button-primary" href="%s">Export subscribers as CSV</a></p></div>',
			esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=epic_export_subscribers' ), 'epic_export_subscribers' ) )
		);
	}

	public static function export_subscribers(): void {
		if ( ! current_user_can( 'edit_pages' ) ) {
			wp_die( 'You are not allowed to export subscribers.', 403 );
		}

		check_admin_referer( 'epic_export_subscribers' );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=epic-subscribers-' . gmdate( 'Y-m-d' ) . '.csv' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'email', 'name', 'organisation', 'status', 'source', 'subscribed_on' ) );

		// A cell that starts with = + - @ is read as a formula by spreadsheets; make it plain text.
		$safe = static fn( $value ) => preg_match( '/^[=+\-@\t\r]/', (string) $value ) ? "'" . $value : (string) $value;

		$ids = get_posts(
			array(
				'post_type'      => 'epic_subscriber',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'orderby'        => 'date',
				'order'          => 'DESC',
			)
		);

		foreach ( $ids as $id ) {
			fputcsv(
				$out,
				array(
					$safe( get_the_title( $id ) ),
					$safe( get_post_meta( $id, 'epic_name', true ) ),
					$safe( get_post_meta( $id, 'epic_organisation', true ) ),
					$safe( get_post_meta( $id, 'epic_status', true ) ),
					$safe( get_post_meta( $id, 'epic_source', true ) ),
					get_the_date( 'Y-m-d H:i:s', $id ),
				)
			);
		}

		fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
		exit;
	}
}
