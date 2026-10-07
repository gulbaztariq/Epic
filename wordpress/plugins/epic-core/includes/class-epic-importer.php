<?php
/**
 * Imports a folder written by tools/export-laravel.php into WordPress.
 *
 * Every imported item remembers which Laravel row it came from (_epic_source), so
 * running the import again updates what is there instead of duplicating it.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Importer {

	/**
	 * How each Laravel table maps onto a content type: which column becomes the
	 * title, which the body, which decides whether the item is shown.
	 */
	const SPEC = array(
		'page_sections'          => array( 'type' => 'epic_section', 'title' => 'heading', 'content' => 'body', 'status' => 'is_active' ),
		'list_items'             => array( 'type' => 'epic_item', 'title' => 'title', 'status' => 'is_active' ),
		'focus_areas'            => array( 'type' => 'epic_focus', 'title' => 'title', 'status' => 'is_active' ),
		'stats'                  => array( 'type' => 'epic_stat', 'title' => 'label', 'status' => 'is_active' ),
		'team_members'           => array( 'type' => 'epic_member', 'title' => 'name', 'content' => 'bio', 'status' => 'is_active' ),
		'projects'               => array( 'type' => 'epic_project', 'title' => 'title', 'slug' => 'slug', 'content' => 'description', 'status' => 'is_published' ),
		'chapters'               => array( 'type' => 'epic_chapter', 'title' => 'country', 'status' => 'is_active' ),
		'partners'               => array( 'type' => 'epic_partner', 'title' => 'name', 'status' => 'is_active' ),
		'careers'                => array( 'type' => 'epic_career', 'title' => 'title', 'slug' => 'slug', 'content' => 'description' ),
		'publications'           => array( 'type' => 'epic_publication', 'title' => 'title', 'slug' => 'slug', 'content' => 'body', 'status' => 'is_published' ),
		'posts'                  => array( 'type' => 'epic_post', 'title' => 'title', 'slug' => 'slug', 'content' => 'body', 'status' => 'is_published' ),
		'events'                 => array( 'type' => 'epic_event', 'title' => 'title', 'slug' => 'slug', 'content' => 'description', 'status' => 'is_published' ),
		'podcasts'               => array( 'type' => 'epic_podcast', 'title' => 'title', 'slug' => 'slug', 'content' => 'description', 'status' => 'is_published' ),
		'videos'                 => array( 'type' => 'epic_video', 'title' => 'title', 'status' => 'is_published' ),
		'gallery_albums'         => array( 'type' => 'epic_album', 'title' => 'title', 'slug' => 'slug', 'status' => 'is_published' ),
		'contact_messages'       => array( 'type' => 'epic_message', 'title' => 'name' ),
		'volunteer_applications' => array( 'type' => 'epic_volunteer', 'title' => 'name' ),
		'subscribers'            => array( 'type' => 'epic_subscriber', 'title' => 'email' ),
	);

	/** Laravel menu location => menu location here, and the menu's name. */
	const MENUS = array(
		'header'       => array( 'primary', 'Header menu' ),
		'footer'       => array( 'footer', 'Footer columns' ),
		'footer_legal' => array( 'footer_legal', 'Footer legal links' ),
	);

	/** @var string */
	private $dir;

	/** @var array<string,mixed> */
	private $data = array();

	/** @var array<string,int> Laravel upload path => attachment id */
	private $media = array();

	/** @var array<string,array> Laravel upload path => saved picture choices */
	private $pics = array();

	/** @var array<int,int> Laravel page id => page id here */
	private $pages = array();

	/** @var array<string,int> table => rows imported */
	private $counts = array();

	/** @var string[] */
	private $warnings = array();

	/** @var callable|null */
	private $logger;

	public function __construct( string $dir, ?callable $logger = null ) {
		$this->dir    = rtrim( $dir, '/' );
		$this->logger = $logger;
	}

	private function say( string $message ): void {
		if ( $this->logger ) {
			call_user_func( $this->logger, $message );
		}
	}

	private function warn( string $message ): void {
		$this->warnings[] = $message;
		$this->say( 'WARNING: ' . $message );
	}

	/** @return string[] */
	public function warnings(): array {
		return $this->warnings;
	}

	/** @return array<string,int> */
	public function counts(): array {
		return $this->counts;
	}

	/* ---------------------------------------------------------------- load --- */

	/** @return true|WP_Error */
	public function load() {
		$file = $this->dir . '/export.json';

		if ( ! is_file( $file ) ) {
			return new WP_Error( 'epic_import', "No export.json in {$this->dir}. Run tools/export-laravel.php first." );
		}

		$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions

		if ( ! is_array( $data ) || 1 !== (int) ( $data['format'] ?? 0 ) || ! isset( $data['tables'] ) ) {
			return new WP_Error( 'epic_import', 'export.json is not a valid EPIC export (format 1).' );
		}

		$this->data = $data;

		foreach ( $this->rows( 'image_settings' ) as $row ) {
			$this->pics[ (string) $row['path'] ] = array(
				'fit'  => $row['fit'] ?: 'auto',
				'x'    => $row['focus_x'] ?? 50,
				'y'    => $row['focus_y'] ?? 50,
				'zoom' => $row['zoom'] ?? 100,
			);
		}

		return true;
	}

	/** @return array<int,array<string,mixed>> */
	private function rows( string $table ): array {
		return $this->data['tables'][ $table ] ?? array();
	}

	/** What an import would do, without changing anything. @return string[] */
	public function plan(): array {
		$lines = array();

		foreach ( $this->data['tables'] as $table => $rows ) {
			if ( $rows ) {
				$lines[] = sprintf( '%-24s %d rows', $table, count( $rows ) );
			}
		}

		$files   = $this->data['files'] ?? array();
		$missing = 0;
		foreach ( $files as $file ) {
			if ( ! is_file( $this->dir . '/' . $file['path'] ) ) {
				++$missing;
			}
		}
		$lines[] = sprintf( '%-24s %d files (%d missing from the export folder)', 'uploads', count( $files ), $missing );
		$lines[] = sprintf( '%-24s %s visitors, %s page views carried over', 'visit totals', number_format( (int) ( $this->data['visits']['visitors'] ?? 0 ) ), number_format( (int) ( $this->data['visits']['page_views'] ?? 0 ) ) );

		return $lines;
	}

	/* ----------------------------------------------------------------- run --- */

	/**
	 * Run the whole import.
	 *
	 * @return array<string,int> rows imported per table
	 */
	public function run( bool $skip_media = false ): array {
		if ( ! empty( $this->data['timezone'] ) && in_array( $this->data['timezone'], timezone_identifiers_list(), true ) ) {
			update_option( 'timezone_string', $this->data['timezone'] );
		}

		$this->say( 'Creating the pages the site depends on…' );
		Epic_Install::run();

		if ( ! $skip_media ) {
			$this->say( 'Importing uploaded files into the media library…' );
			$this->media();
		} else {
			$this->load_existing_media();
		}

		$this->say( 'Importing settings…' );
		$this->settings();

		$this->say( 'Importing pages…' );
		$this->pages();

		foreach ( self::SPEC as $table => $spec ) {
			if ( $this->rows( $table ) ) {
				$this->say( 'Importing ' . $table . '…' );
				$this->content( $table, $spec );
			}
		}

		$this->say( 'Building the menus…' );
		$this->menus();

		$this->visits();

		Epic_Data::flush_pages();
		Epic_Settings::flush();
		flush_rewrite_rules( false );

		return $this->counts;
	}

	/* --------------------------------------------------------------- media --- */

	/** Attachments from an earlier import, so a re-run without files still maps pictures. */
	private function load_existing_media(): void {
		global $wpdb;

		$rows = $wpdb->get_results( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_epic_source_path'" );
		foreach ( (array) $rows as $row ) {
			$this->media[ $row->meta_value ] = (int) $row->post_id;
		}
	}

	private function media(): void {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';

		$this->load_existing_media();

		$upload = wp_upload_dir();
		$alts   = array();
		foreach ( $this->rows( 'media_files' ) as $row ) {
			$alts[ (string) $row['path'] ] = array( (string) ( $row['name'] ?? '' ), (string) ( $row['alt_text'] ?? '' ) );
		}

		foreach ( $this->data['files'] ?? array() as $file ) {
			$path   = (string) $file['path'];
			$source = $this->dir . '/' . $path;

			if ( isset( $this->media[ $path ] ) && get_post( $this->media[ $path ] ) ) {
				continue;
			}

			if ( ! is_file( $source ) ) {
				$this->warn( "Missing file {$path}" );
				continue;
			}

			$relative = 'epic/' . preg_replace( '~^uploads/~', '', $path );
			$dest     = $upload['basedir'] . '/' . $relative;

			wp_mkdir_p( dirname( $dest ) );
			if ( ! copy( $source, $dest ) ) {
				$this->warn( "Could not copy {$path}" );
				continue;
			}

			$type = wp_check_filetype( $dest );
			[ $name, $alt ] = $alts[ $path ] ?? array( '', '' );

			$id = wp_insert_attachment(
				array(
					'post_mime_type' => $type['type'] ?: 'application/octet-stream',
					'post_title'     => '' !== $name ? pathinfo( $name, PATHINFO_FILENAME ) : pathinfo( $dest, PATHINFO_FILENAME ),
					'post_status'    => 'inherit',
					'guid'           => $upload['baseurl'] . '/' . $relative,
				),
				$dest
			);

			if ( is_wp_error( $id ) || ! $id ) {
				$this->warn( "Could not add {$path} to the media library" );
				continue;
			}

			if ( 0 === strpos( (string) $type['type'], 'image/' ) ) {
				wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
			}

			if ( '' !== $alt ) {
				update_post_meta( $id, '_wp_attachment_image_alt', $alt );
			}

			update_post_meta( $id, '_epic_source_path', $path );
			$this->media[ $path ] = (int) $id;
		}

		$this->counts['uploads'] = count( $this->media );
	}

	/** A stored upload path (or address) as a value an image/file field can hold. */
	private function media_value( $path ): string {
		$path = trim( (string) $path );

		if ( '' === $path ) {
			return '';
		}

		if ( preg_match( '~^(https?:)?//~i', $path ) ) {
			return $path;
		}

		$path = ltrim( $path, '/' );

		return isset( $this->media[ $path ] ) ? (string) $this->media[ $path ] : '';
	}

	/* ---------------------------------------------------------- body copy --- */

	/** Body copy as HTML: plain text becomes paragraphs; links to uploads follow the files. */
	private function html_body( $text ): string {
		$text = trim( (string) $text );

		if ( '' === $text ) {
			return '';
		}

		$has_markup = false;
		foreach ( array( '<p', '<ul', '<ol', '<h1', '<h2', '<h3', '<h4', '<div', '<br', '<blockquote', '<table' ) as $tag ) {
			if ( false !== strpos( $text, $tag ) ) {
				$has_markup = true;
				break;
			}
		}

		if ( ! $has_markup ) {
			$html = '';
			foreach ( preg_split( '/\n\s*\n/', $text ) ?: array() as $paragraph ) {
				$html .= '<p>' . nl2br( esc_html( trim( $paragraph ) ) ) . "</p>\n";
			}
			$text = trim( $html );
		}

		return $this->rewrite_urls( $text );
	}

	private function rewrite_urls( string $html ): string {
		return (string) preg_replace_callback(
			'~(\b(?:src|href|poster|data-src)\s*=\s*)(["\'])([^"\']+)\2~i',
			function ( $m ) {
				if ( preg_match( '~(?:^|/)(uploads/[^?#]+)~', $m[3], $found ) ) {
					$path = rawurldecode( $found[1] );
					if ( isset( $this->media[ $path ] ) ) {
						$url = wp_get_attachment_url( $this->media[ $path ] );
						if ( $url ) {
							return $m[1] . $m[2] . esc_url( $url ) . $m[2];
						}
					}
				}

				return $m[0];
			},
			$html
		);
	}

	/* ------------------------------------------------------------ settings --- */

	private function settings(): void {
		$fields = Epic_Schema::setting_fields();
		$values = array();

		foreach ( $this->rows( 'settings' ) as $row ) {
			$key = (string) $row['key'];

			if ( ! isset( $fields[ $key ] ) ) {
				continue;
			}

			$value = (string) ( $row['value'] ?? '' );
			$values[ $key ] = 'image' === $fields[ $key ]['type'] ? $this->media_value( $value ) : $value;
		}

		if ( $values ) {
			Epic_Settings::put_many( $values );
		}

		$this->counts['settings'] = count( $values );
	}

	/* --------------------------------------------------------------- pages --- */

	private function pages(): void {
		$builtin = Epic_Schema::builtin_pages();
		$legacy  = Epic_Schema::legacy_slugs();
		$done    = 0;

		foreach ( $this->rows( 'pages' ) as $row ) {
			$key = (string) ( $row['key'] ?? '' );

			if ( '' === $key ) {
				// Installs from before page keys: find the page by the slug it had.
				foreach ( $builtin as $candidate => $def ) {
					if ( in_array( $row['slug'], $legacy[ $candidate ] ?? array( $candidate ), true ) ) {
						$key = $candidate;
						break;
					}
				}

				if ( '' === $key && in_array( $row['title'], array( 'Board of Directors', 'Board of Governance' ), true ) ) {
					$key = 'board';
				}
			}

			$is_builtin = '' !== $key && isset( $builtin[ $key ] );
			$post_id    = $is_builtin ? Epic_Data::page_id( $key ) : $this->custom_page( $row );

			if ( ! $post_id ) {
				$this->warn( 'Could not place page ' . $row['slug'] );
				continue;
			}

			$this->pages[ (int) $row['id'] ] = $post_id;

			$update = array(
				'ID'           => $post_id,
				'post_title'   => (string) $row['title'],
				'post_content' => $this->html_body( $row['body'] ?? '' ),
				'menu_order'   => (int) ( $row['sort'] ?? 0 ),
				'post_status'  => ( $is_builtin || ! empty( $row['is_published'] ) ) ? 'publish' : 'draft',
			);
			wp_update_post( wp_slash( $update ) );

			$this->save_fields( $post_id, Epic_Schema::page_fields(), $row );
			update_post_meta( $post_id, '_epic_source', 'pages:' . $row['id'] );

			// Search engine title and description, in Rank Math's own fields.
			$is_home = 'home' === $key;
			$title   = trim( (string) ( $row['meta_title'] ?? '' ) );
			if ( '' !== $title ) {
				update_post_meta( $post_id, 'rank_math_title', $is_home ? $title : $title . ' %sep% %sitename%' );
			}

			$description = trim( (string) ( $row['meta_description'] ?? '' ) );
			if ( '' === $description ) {
				$description = epic_summarise( (string) ( $row['intro'] ?: ( $row['body'] ?: ( $row['hero_subtitle'] ?? '' ) ) ), 155 );
			}
			if ( '' !== $description ) {
				update_post_meta( $post_id, 'rank_math_description', $description );
			}

			Epic_Seo::sync_featured_image( $post_id, get_post( $post_id ) );
			++$done;
		}

		Epic_Data::flush_pages();
		$this->counts['pages'] = $done;
	}

	/** The WordPress page for a page the Laravel editors created (it lived under /p/). */
	private function custom_page( array $row ): int {
		$slug = sanitize_title( (string) $row['slug'] );

		$existing = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'any',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => '_epic_source',
				'meta_value'     => 'pages:' . $row['id'],
			)
		);

		if ( $existing ) {
			return (int) $existing[0];
		}

		// WordPress ships a draft "Privacy Policy" page; adopt it rather than clash with its address.
		$same = get_page_by_path( $slug );
		if ( $same instanceof WP_Post && ! get_post_meta( $same->ID, '_epic_key', true ) && ! $same->post_parent ) {
			return (int) $same->ID;
		}

		$id = wp_insert_post(
			array(
				'post_type'   => 'page',
				'post_status' => 'draft',
				'post_title'  => (string) $row['title'],
				'post_name'   => $slug,
			),
			true
		);

		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/* ------------------------------------------------------------- content --- */

	/**
	 * Import every row of one table.
	 *
	 * @param array<string,string> $spec
	 */
	private function content( string $table, array $spec ): void {
		$type   = $spec['type'];
		$schema = Epic_Schema::types()[ $type ];
		$done   = 0;

		foreach ( $this->rows( $table ) as $row ) {
			$active = ! isset( $spec['status'] ) || ! empty( $row[ $spec['status'] ] );
			$source = $table . ':' . $row['id'];

			$post = array(
				'post_type'   => $type,
				'post_status' => $active ? 'publish' : 'draft',
				'post_title'  => (string) ( $row[ $spec['title'] ] ?? '' ),
				'post_content' => isset( $spec['content'] ) ? $this->html_body( $row[ $spec['content'] ] ?? '' ) : '',
			);

			if ( ! empty( $spec['slug'] ) && ! empty( $row[ $spec['slug'] ] ) ) {
				$post['post_name'] = sanitize_title( (string) $row[ $spec['slug'] ] );
			}

			$created = $this->mysql_date( $row['created_at'] ?? null );
			if ( $created ) {
				$post['post_date']     = $created;
				$post['post_date_gmt'] = get_gmt_from_date( $created );
			}

			// The page a section belongs to, and the order, live in the post row.
			if ( 'epic_section' === $type ) {
				$post['post_parent'] = $this->pages[ (int) $row['page_id'] ] ?? 0;

				if ( ! $post['post_parent'] ) {
					$this->warn( "Section {$row['id']} belongs to a page that was not imported" );
					continue;
				}
			}

			foreach ( $schema['fields'] as $field ) {
				if ( 'section' === $field['type'] ) {
					continue;
				}

				$column = $row[ $field['key'] ] ?? null;

				switch ( $field['store'] ?? 'meta' ) {
					case 'excerpt':
						$post['post_excerpt'] = (string) $column;
						break;
					case 'menu_order':
						$post['menu_order'] = (int) $column;
						break;
				}
			}

			$existing = $this->find_by_source( $source );
			if ( $existing ) {
				$post['ID'] = $existing;
				$id         = wp_update_post( wp_slash( $post ), true );
			} else {
				$id = wp_insert_post( wp_slash( $post ), true );
			}

			if ( is_wp_error( $id ) || ! $id ) {
				$this->warn( "Could not import {$source}: " . ( is_wp_error( $id ) ? $id->get_error_message() : 'unknown error' ) );
				continue;
			}

			$this->save_fields( (int) $id, $schema['fields'], $row, $table );
			update_post_meta( $id, '_epic_source', $source );

			// The post is saved before its fields, so tell the SEO layer once they are in.
			Epic_Seo::sync_featured_image( (int) $id, get_post( $id ) );

			++$done;
		}

		$this->counts[ $table ] = $done;
	}

	private function find_by_source( string $source ): int {
		global $wpdb;

		return (int) $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_epic_source' AND meta_value = %s LIMIT 1", $source ) );
	}

	/** A Laravel timestamp as a MySQL date, or null. */
	private function mysql_date( $value ): ?string {
		$value = trim( (string) $value );

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}/', $value ) ) {
			return null;
		}

		$value = substr( str_replace( 'T', ' ', $value ), 0, 19 );
		if ( 10 === strlen( $value ) ) {
			$value .= ' 00:00:00';
		}

		return strtotime( $value ) && strtotime( $value ) <= time() ? $value : null;
	}

	/**
	 * Write a row's values into post meta, converted to what each field type stores.
	 *
	 * @param array<int,array<string,mixed>> $fields
	 * @param array<string,mixed>            $row
	 */
	private function save_fields( int $post_id, array $fields, array $row, string $table = '' ): void {
		foreach ( $fields as $field ) {
			if ( 'section' === $field['type'] || 'meta' !== ( $field['store'] ?? 'meta' ) ) {
				continue;
			}

			$key = $field['key'];
			$raw = $row[ $key ] ?? null;

			switch ( $field['type'] ) {
				case 'checkbox':
					$value = ! empty( $raw ) ? '1' : '0';
					if ( null === $raw && isset( $field['default'] ) ) {
						$value = $field['default'] ? '1' : '0';
					}
					break;

				case 'number':
					$value = (string) (int) $raw;
					break;

				case 'date':
					$value = preg_match( '/^\d{4}-\d{2}-\d{2}/', (string) $raw ) ? substr( (string) $raw, 0, 10 ) : '';
					break;

				case 'datetime':
					$value = preg_match( '/^\d{4}-\d{2}-\d{2}/', (string) $raw ) ? substr( str_replace( 'T', ' ', (string) $raw ) . ( 10 === strlen( (string) $raw ) ? ' 00:00:00' : '' ), 0, 19 ) : '';
					break;

				case 'image':
					$value = $this->media_value( $raw );
					if ( ! isset( $field['adjust'] ) || false !== $field['adjust'] ) {
						$pic = $this->pics[ ltrim( (string) $raw, '/' ) ] ?? array();
						update_post_meta( $post_id, 'epic_' . $key . '__pic', Epic_Pic::sanitize( $pic ) );
					}
					break;

				case 'file':
					$value = $this->media_value( $raw );
					break;

				case 'richtext':
					$value = $this->html_body( $raw );
					break;

				case 'gallery':
					$value = 'gallery_albums' === $table ? $this->album_photos( (int) $row['id'] ) : array();
					break;

				case 'select':
					$options = Epic_Schema::options( $field['options'] ?? array() );
					// A value the dashboard list no longer offers is kept rather than silently changed.
					$value   = '' !== (string) $raw ? (string) $raw : (string) ( $field['default'] ?? '' );
					unset( $options );
					break;

				default:
					$value = is_scalar( $raw ) ? (string) $raw : '';
			}

			update_post_meta( $post_id, 'epic_' . $key, $value );
		}
	}

	/** The photos of one album, in order, with their captions and fit choices. @return array<int,array<string,mixed>> */
	private function album_photos( int $album_id ): array {
		$photos = array();

		foreach ( $this->rows( 'gallery_images' ) as $image ) {
			if ( (int) $image['gallery_album_id'] !== $album_id ) {
				continue;
			}

			$value = $this->media_value( $image['image'] );
			if ( '' === $value ) {
				continue;
			}

			$pic      = $this->pics[ ltrim( (string) $image['image'], '/' ) ] ?? array();
			$photos[] = array(
				'sort'    => (int) ( $image['sort'] ?? 0 ),
				'id'      => is_numeric( $value ) ? (int) $value : '',
				'url'     => is_numeric( $value ) ? '' : $value,
				'caption' => (string) ( $image['caption'] ?? '' ),
			) + Epic_Pic::sanitize( $pic );
		}

		usort( $photos, static fn( $a, $b ) => $a['sort'] <=> $b['sort'] );

		return array_map(
			static function ( $photo ) {
				unset( $photo['sort'] );

				return $photo;
			},
			$photos
		);
	}

	/* --------------------------------------------------------------- menus --- */

	private function menus(): void {
		$locations = array();
		$built     = 0;

		foreach ( self::MENUS as $laravel => [ $location, $name ] ) {
			$rows = array_values(
				array_filter(
					$this->rows( 'menu_items' ),
					static fn( $row ) => $row['location'] === $laravel && ! empty( $row['is_active'] )
				)
			);

			if ( ! $rows ) {
				continue;
			}

			$menu = wp_get_nav_menu_object( $name );
			if ( $menu ) {
				// Rebuilt from the export, so re-running never doubles the items.
				foreach ( (array) wp_get_nav_menu_items( $menu->term_id ) as $old ) {
					wp_delete_post( $old->ID, true );
				}
				$menu_id = (int) $menu->term_id;
			} else {
				$menu_id = (int) wp_create_nav_menu( $name );
			}

			if ( ! $menu_id ) {
				$this->warn( "Could not create the {$name} menu" );
				continue;
			}

			usort( $rows, static fn( $a, $b ) => array( (int) $a['sort'], (int) $a['id'] ) <=> array( (int) $b['sort'], (int) $b['id'] ) );

			$ids = array();
			foreach ( $rows as $row ) {
				if ( empty( $row['parent_id'] ) ) {
					$ids[ $row['id'] ] = $this->menu_item( $menu_id, $row, 0, count( $ids ) + 1 );
					++$built;
				}
			}
			foreach ( $rows as $row ) {
				if ( ! empty( $row['parent_id'] ) && isset( $ids[ $row['parent_id'] ] ) ) {
					$ids[ $row['id'] ] = $this->menu_item( $menu_id, $row, $ids[ $row['parent_id'] ], count( $ids ) + 1 );
					++$built;
				}
			}

			$locations[ $location ] = $menu_id;
		}

		if ( $locations ) {
			// Stored for the EPIC theme directly, so it works before the theme is switched on.
			$mods = get_option( 'theme_mods_epic', array() );
			$mods = is_array( $mods ) ? $mods : array();
			$mods['nav_menu_locations'] = $locations + (array) ( $mods['nav_menu_locations'] ?? array() );
			update_option( 'theme_mods_epic', $mods );

			if ( 'epic' === get_stylesheet() ) {
				set_theme_mod( 'nav_menu_locations', $mods['nav_menu_locations'] );
			}
		}

		$this->counts['menu_items'] = $built;
	}

	private function menu_item( int $menu_id, array $row, int $parent, int $position ): int {
		$url  = trim( (string) $row['url'] );
		$args = array(
			'menu-item-title'     => (string) $row['label'],
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent,
			'menu-item-position'  => $position,
			'menu-item-target'    => ( '_blank' === ( $row['target'] ?? '' ) ) ? '_blank' : '',
		);

		$page = $this->page_for_url( $url );
		if ( $page ) {
			$args += array(
				'menu-item-type'      => 'post_type',
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page,
			);
		} else {
			$args += array(
				'menu-item-type' => 'custom',
				'menu-item-url'  => '' === $url ? '#' : $url,
			);
		}

		$id = wp_update_nav_menu_item( $menu_id, 0, $args );

		return is_wp_error( $id ) ? 0 : (int) $id;
	}

	/** The page an internal menu address points at, so the link follows the page. */
	private function page_for_url( string $url ): int {
		$path = trim( (string) wp_parse_url( $url, PHP_URL_PATH ), '/' );

		if ( '' === $url || '#' === $url || ( preg_match( '~^https?://~i', $url ) && ! $this->is_own_host( $url ) ) || false !== strpos( $url, '?' ) ) {
			return 0;
		}

		foreach ( Epic_Schema::builtin_pages() as $key => $def ) {
			if ( $def['path'] === $path ) {
				return Epic_Data::page_id( $key );
			}
		}

		if ( preg_match( '~^p/([^/]+)$~', $path, $m ) ) {
			$page = get_page_by_path( sanitize_title( $m[1] ) );

			return $page ? (int) $page->ID : 0;
		}

		return 0;
	}

	private function is_own_host( string $url ): bool {
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );
		$own  = array_filter( array( (string) wp_parse_url( home_url(), PHP_URL_HOST ), (string) wp_parse_url( (string) ( $this->data['source_url'] ?? '' ), PHP_URL_HOST ) ) );

		return $host && in_array( $host, $own, true );
	}

	/* -------------------------------------------------------------- visits --- */

	private function visits(): void {
		$visits = $this->data['visits'] ?? array();

		if ( empty( $visits ) ) {
			return;
		}

		update_option( 'epic_visits_offset_views', (int) ( $visits['page_views'] ?? 0 ) );
		update_option( 'epic_visits_offset_visitors', (int) ( $visits['visitors'] ?? 0 ) );
		update_option( 'epic_visits_since', (string) ( $visits['since'] ?? '' ), false );
		update_option( 'epic_visits_history', (array) ( $visits['daily'] ?? array() ), false );
		delete_transient( 'epic_visit_totals' );
	}
}
