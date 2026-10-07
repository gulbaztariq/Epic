<?php
/**
 * The EPIC menu: an overview, the site settings, the visitor report, and the home
 * for every content type registered by Epic_Types.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Admin {

	const CAP = 'edit_pages';

	public static function init(): void {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 1 );
		add_action( 'admin_menu', array( __CLASS__, 'late_menu' ), 30 );
		add_action( 'admin_post_epic_save_settings', array( __CLASS__, 'save_settings' ) );
		add_action( 'admin_notices', array( __CLASS__, 'notices' ) );
		add_filter( 'admin_footer_text', array( __CLASS__, 'footer_text' ) );
	}

	/** The parent menu has to exist before the content types attach their lists to it. */
	public static function menu(): void {
		add_menu_page( 'EPIC', 'EPIC', self::CAP, 'epic', array( __CLASS__, 'overview' ), 'dashicons-chart-area', 21 );
		add_submenu_page( 'epic', 'EPIC overview', 'Overview', self::CAP, 'epic', array( __CLASS__, 'overview' ) );
	}

	public static function late_menu(): void {
		add_submenu_page( 'epic', 'Visitors', 'Visitors', self::CAP, 'epic-visitors', array( 'Epic_Visits', 'screen' ) );
		add_submenu_page( 'epic', 'EPIC settings', 'Settings', 'manage_options', 'epic-settings', array( __CLASS__, 'settings_screen' ) );
	}

	public static function footer_text( $text ) {
		$screen = get_current_screen();

		if ( $screen && ( isset( Epic_Schema::types()[ $screen->post_type ] ) || false !== strpos( (string) $screen->id, 'epic' ) ) ) {
			return 'EPIC website · powered by WordPress';
		}

		return $text;
	}

	/* ------------------------------------------------------------ overview --- */

	public static function overview(): void {
		$counts = array();

		foreach ( Epic_Schema::types() as $name => $type ) {
			$total          = wp_count_posts( $name );
			$published      = (int) ( $total->publish ?? 0 );
			$counts[ $name ] = array( $type['label'], $published, (int) ( $total->draft ?? 0 ) );
		}

		echo '<div class="wrap"><h1>EPIC website</h1>';
		echo '<p>Everything on the website is edited from here, from <strong>Pages</strong>, and from <strong>Appearance → Menus</strong>. Hide anything without deleting it by saving it as a draft.</p>';

		$unread = self::unread_forms();
		if ( $unread ) {
			printf( '<div class="notice notice-info inline"><p>%d unread form message%s waiting under <a href="%s">Messages</a> and <a href="%s">Volunteers</a>.</p></div>', (int) $unread, 1 === $unread ? ' is' : 's are', esc_url( admin_url( 'edit.php?post_type=epic_message' ) ), esc_url( admin_url( 'edit.php?post_type=epic_volunteer' ) ) );
		}

		echo '<div class="epic-overview">';
		printf( '<a class="epic-card" href="%s"><strong>%d</strong><span>Pages</span></a>', esc_url( admin_url( 'edit.php?post_type=page' ) ), (int) wp_count_posts( 'page' )->publish );

		foreach ( $counts as $name => $row ) {
			printf(
				'<a class="epic-card" href="%s"><strong>%d</strong><span>%s%s</span></a>',
				esc_url( admin_url( 'edit.php?post_type=' . $name ) ),
				(int) $row[1],
				esc_html( $row[0] ),
				$row[2] ? ' · ' . (int) $row[2] . ' hidden' : ''
			);
		}
		echo '</div>';

		echo '<h2>Quick links</h2><ul class="ul-disc">';
		printf( '<li><a href="%s">Open the website</a></li>', esc_url( home_url( '/' ) ) );
		printf( '<li><a href="%s">Edit the menus</a> (header, footer columns, footer legal links)</li>', esc_url( admin_url( 'nav-menus.php' ) ) );
		printf( '<li><a href="%s">Site settings</a> (logo, contact details, social links, colours, footer)</li>', esc_url( admin_url( 'admin.php?page=epic-settings' ) ) );
		if ( defined( 'RANK_MATH_VERSION' ) ) {
			printf( '<li><a href="%s">Search engine optimisation (Rank Math)</a></li>', esc_url( admin_url( 'admin.php?page=rank-math' ) ) );
		}
		echo '</ul></div>';
	}

	private static function unread_forms(): int {
		$total = 0;

		foreach ( array( 'epic_message', 'epic_volunteer' ) as $type ) {
			$query  = new WP_Query(
				array(
					'post_type'      => $type,
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'meta_query'     => array(
						array(
							'key'   => 'epic_is_read',
							'value' => '0',
						),
					),
				)
			);
			$total += (int) $query->found_posts;
		}

		return $total;
	}

	/* ------------------------------------------------------------ settings --- */

	public static function settings_screen(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$groups = Epic_Schema::settings();
		$tab    = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification
		$tab    = isset( $groups[ $tab ] ) ? $tab : 'general';

		echo '<div class="wrap epic-settings"><h1>EPIC settings</h1><nav class="nav-tab-wrapper">';
		foreach ( $groups as $key => $group ) {
			printf(
				'<a class="nav-tab%s" href="%s">%s</a>',
				$key === $tab ? ' nav-tab-active' : '',
				esc_url( admin_url( 'admin.php?page=epic-settings&tab=' . $key ) ),
				esc_html( $group['label'] )
			);
		}
		echo '</nav>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
		echo '<input type="hidden" name="action" value="epic_save_settings"><input type="hidden" name="tab" value="' . esc_attr( $tab ) . '">';
		wp_nonce_field( 'epic_settings_' . $tab, 'epic_settings_nonce' );

		echo '<div class="epic-fields">';
		foreach ( $groups[ $tab ]['fields'] as $key => $field ) {
			$value = Epic_Settings::all()[ $key ] ?? '';
			if ( '' === $value && isset( $field['default'] ) && in_array( $field['type'], array( 'select', 'color', 'range' ), true ) ) {
				$value = $field['default'];
			}

			printf( '<div class="epic-field" style="--col:%d">', (int) ( $field['col'] ?? 12 ) );
			printf( '<label class="epic-label" for="s-%1$s">%2$s</label>', esc_attr( $key ), esc_html( $field['label'] ) );
			self::setting_control( $key, $field, (string) $value );
			if ( ! empty( $field['hint'] ) ) {
				echo '<p class="description">' . esc_html( $field['hint'] ) . '</p>';
			}
			echo '</div>';
		}
		echo '</div>';

		submit_button( $groups[ $tab ]['label'] . ' settings: save' );
		echo '</form></div>';
	}

	private static function setting_control( string $key, array $field, string $value ): void {
		$name = 'epic_setting[' . $key . ']';
		$id   = 's-' . $key;
		$ph   = ! empty( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';

		switch ( $field['type'] ) {
			case 'textarea':
				printf( '<textarea class="large-text" rows="3" id="%s" name="%s"%s>%s</textarea>', esc_attr( $id ), esc_attr( $name ), $ph, esc_textarea( $value ) );
				break;

			case 'code':
				printf( '<textarea class="large-text code" rows="4" id="%s" name="%s"%s>%s</textarea>', esc_attr( $id ), esc_attr( $name ), $ph, esc_textarea( $value ) );
				break;

			case 'select':
				printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
				foreach ( $field['options'] as $option => $label ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( $option ), selected( $value, (string) $option, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;

			case 'color':
				printf(
					'<span class="epic-color"><input type="color" id="%1$s" name="%2$s" value="%3$s" data-epic-color><code data-epic-color-readout>%3$s</code> <button type="button" class="button button-small" data-epic-color-reset="%4$s">Use the default</button></span>',
					esc_attr( $id ),
					esc_attr( $name ),
					esc_attr( $value ),
					esc_attr( $field['default'] ?? '#e8f1fa' )
				);
				break;

			case 'range':
				printf(
					'<span class="epic-range"><input type="range" id="%s" name="%s" min="%d" max="%d" step="%d" value="%d" data-epic-range><strong data-epic-range-readout data-unit="%s">%d%s</strong></span>',
					esc_attr( $id ),
					esc_attr( $name ),
					(int) $field['min'],
					(int) $field['max'],
					(int) $field['step'],
					(int) $value,
					esc_attr( $field['unit'] ?? '' ),
					(int) $value,
					esc_html( $field['unit'] ?? '' )
				);
				break;

			case 'image':
				$url = '' !== $value ? ( is_numeric( $value ) ? wp_get_attachment_url( (int) $value ) : $value ) : '';
				printf(
					'<div class="epic-setting-image" data-epic-setting-image><img src="%s" alt=""%s><input type="hidden" name="%s" value="%s"><button type="button" class="button" data-choose>Choose picture</button> <button type="button" class="button-link button-link-delete" data-remove%s>Remove</button></div>',
					esc_url( (string) $url ),
					$url ? '' : ' hidden',
					esc_attr( $name ),
					esc_attr( $value ),
					$url ? '' : ' hidden'
				);
				break;

			default:
				printf( '<input type="text" class="large-text" id="%s" name="%s" value="%s"%s>', esc_attr( $id ), esc_attr( $name ), esc_attr( $value ), $ph );
		}
	}

	public static function save_settings(): void {
		$tab = isset( $_POST['tab'] ) ? sanitize_key( wp_unslash( $_POST['tab'] ) ) : 'general';

		if ( ! current_user_can( 'manage_options' ) || ! isset( $_POST['epic_settings_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['epic_settings_nonce'] ) ), 'epic_settings_' . $tab ) ) {
			wp_die( 'You are not allowed to change these settings.', 403 );
		}

		$groups = Epic_Schema::settings();
		if ( ! isset( $groups[ $tab ] ) ) {
			wp_die( 'Unknown settings tab.', 404 );
		}

		$input   = isset( $_POST['epic_setting'] ) ? (array) wp_unslash( $_POST['epic_setting'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$changes = array();

		foreach ( $groups[ $tab ]['fields'] as $key => $field ) {
			$raw = $input[ $key ] ?? '';

			switch ( $field['type'] ) {
				case 'select':
					$raw = isset( $field['options'][ $raw ] ) ? (string) $raw : (string) ( $field['default'] ?? '' );
					break;
				case 'color':
					$raw = preg_match( '/^#[0-9a-fA-F]{6}$/', (string) $raw ) ? strtolower( (string) $raw ) : (string) $field['default'];
					break;
				case 'range':
					$raw = (string) max( (int) $field['min'], min( (int) $field['max'], (int) $raw ) );
					break;
				case 'image':
					$raw = is_numeric( $raw ) && (int) $raw > 0 ? (string) (int) $raw : Epic_Fields::sanitize_link( (string) $raw );
					break;
				case 'url':
					$raw = Epic_Fields::sanitize_link( (string) $raw );
					break;
				case 'code':
					$raw = current_user_can( 'unfiltered_html' ) ? (string) $raw : wp_kses_post( (string) $raw );
					break;
				case 'textarea':
					$raw = sanitize_textarea_field( (string) $raw );
					break;
				default:
					$raw = sanitize_text_field( (string) $raw );
			}

			$changes[ $key ] = $raw;
		}

		Epic_Settings::put_many( $changes );

		wp_safe_redirect( add_query_arg( array( 'page' => 'epic-settings', 'tab' => $tab, 'epic_saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	public static function notices(): void {
		if ( isset( $_GET['epic_saved'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
		}
	}
}
