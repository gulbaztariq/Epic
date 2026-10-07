<?php
/**
 * Registers the content types and keeps their web addresses identical to the ones
 * the Laravel site used, so inbound links and search rankings carry over.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Types {

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register' ), 5 );
		add_action( 'init', array( __CLASS__, 'rewrite_rules' ), 6 );
		add_filter( 'post_type_link', array( __CLASS__, 'post_link' ), 10, 2 );
		add_filter( 'request', array( __CLASS__, 'fix_request' ), 1 );
		add_action( 'template_redirect', array( __CLASS__, 'canonical_post_address' ), 1 );

		// A classic editor everywhere: the edit screens are forms with a body, not page-builder canvases.
		add_filter( 'use_block_editor_for_post_type', array( __CLASS__, 'classic_editor' ), 10, 2 );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
		add_action( 'edit_form_after_title', array( __CLASS__, 'body_label' ) );

		// Built-in pages keep their address.
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'lock_builtin_slug' ), 20, 2 );

		add_action( 'admin_init', array( __CLASS__, 'admin_lists' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'admin_query' ) );
	}

	/* ------------------------------------------------------------ register --- */

	public static function register(): void {
		foreach ( Epic_Schema::types() as $name => $type ) {
			$singular = $type['singular'];
			$plural   = $type['label'];

			$args = array(
				'labels'              => array(
					'name'               => $plural,
					'singular_name'      => $singular,
					'menu_name'          => $plural,
					'all_items'          => $plural,
					'add_new'            => 'Add new',
					'add_new_item'       => 'Add new ' . strtolower( $singular ),
					'edit_item'          => 'Edit ' . strtolower( $singular ),
					'new_item'           => 'New ' . strtolower( $singular ),
					'view_item'          => 'View ' . strtolower( $singular ),
					'search_items'       => 'Search ' . strtolower( $plural ),
					'not_found'          => 'Nothing here yet.',
					'not_found_in_trash' => 'Nothing in the bin.',
				),
				'description'         => $type['description'],
				'public'              => (bool) $type['public'],
				'publicly_queryable'  => (bool) $type['public'],
				'exclude_from_search' => ! $type['public'],
				'show_ui'             => true,
				'show_in_menu'        => 'epic',
				'show_in_nav_menus'   => (bool) $type['public'],
				'show_in_admin_bar'   => false,
				'show_in_rest'        => (bool) $type['public'],
				'query_var'           => (bool) $type['public'],
				'has_archive'         => false,
				'hierarchical'        => false,
				'supports'            => $type['supports'],
				'menu_icon'           => $type['icon'],
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
				'delete_with_user'    => false,
				'rewrite'             => false,
			);

			if ( $type['public'] && ! empty( $type['rewrite'] ) ) {
				$args['rewrite'] = array(
					'slug'       => $type['rewrite'],
					'with_front' => false,
				);
			}

			// Form submissions arrive from the website; nobody adds them by hand.
			if ( ! empty( $type['form'] ) ) {
				$args['capabilities'] = array( 'create_posts' => 'do_not_allow' );
			}

			register_post_type( $name, $args );
		}

		// Pages the site itself relies on are found by this key, never by their address.
		register_post_meta(
			'page',
			'_epic_key',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => false,
				'sanitize_callback' => 'sanitize_key',
				'auth_callback'     => static fn() => current_user_can( 'manage_options' ),
			)
		);
	}

	/** Blogs and press releases share one content type but live under two addresses. */
	public static function rewrite_rules(): void {
		add_rewrite_rule( '^blogs-and-articles/([^/]+)/?$', 'index.php?post_type=epic_post&epic_post=$matches[1]', 'top' );
		add_rewrite_rule( '^media/press-releases/([^/]+)/?$', 'index.php?post_type=epic_post&epic_post=$matches[1]', 'top' );
	}

	/** The address of a blog, article or press release. */
	public static function post_link( $link, $post ) {
		if ( ! $post instanceof WP_Post || 'epic_post' !== $post->post_type ) {
			return $link;
		}

		if ( '' === $post->post_name || in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ), true ) ) {
			return $link;
		}

		$category = get_post_meta( $post->ID, 'epic_category', true );
		$base     = 'press_release' === $category ? 'media/press-releases' : 'blogs-and-articles';

		return home_url( user_trailingslashit( '/' . $base . '/' . $post->post_name ) );
	}

	/** A blog opened under the press-release address (or the reverse) goes to its real one. */
	public static function canonical_post_address(): void {
		if ( ! is_singular( 'epic_post' ) ) {
			return;
		}

		$wanted  = untrailingslashit( (string) wp_parse_url( get_permalink(), PHP_URL_PATH ) );
		$current = untrailingslashit( (string) wp_parse_url( epic_current_url(), PHP_URL_PATH ) );

		if ( $wanted && $current && $wanted !== $current ) {
			wp_safe_redirect( get_permalink(), 301 );
			exit;
		}
	}

	/**
	 * Two address clashes the rewrite rules cannot settle on their own:
	 *
	 * 1. ?page=2 is how every list was paged on the Laravel site, and the links
	 *    are already out there. WordPress would read it as "page 2 of this page"
	 *    and answer 404, so it is moved to a private variable the templates read.
	 * 2. /publications/journal is a page, but the publications rule claims every
	 *    /publications/{something}. If no publication has that address, look for a
	 *    page at the same path.
	 *
	 * @param array<string,mixed> $vars
	 * @return array<string,mixed>
	 */
	public static function fix_request( $vars ) {
		if ( isset( $_GET['page'] ) && ! is_admin() ) { // phpcs:ignore WordPress.Security.NonceVerification
			$GLOBALS['epic_pg'] = max( 1, (int) $_GET['page'] ); // phpcs:ignore WordPress.Security.NonceVerification
			unset( $vars['page'] );
		}

		foreach ( Epic_Schema::types() as $type => $def ) {
			if ( empty( $def['public'] ) || empty( $def['rewrite'] ) || ! isset( $vars[ $type ] ) || is_admin() ) {
				continue;
			}

			$slug = (string) $vars[ $type ];
			$exists = get_posts(
				array(
					'post_type'      => $type,
					'name'           => $slug,
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'posts_per_page' => 1,
				)
			);

			if ( ! $exists && get_page_by_path( $def['rewrite'] . '/' . $slug ) ) {
				return array( 'pagename' => $def['rewrite'] . '/' . $slug );
			}
		}

		return $vars;
	}

	/* ---------------------------------------------------------- the editor --- */

	/** @param bool $use */
	public static function classic_editor( $use, $post_type ) {
		return ( isset( Epic_Schema::types()[ $post_type ] ) || 'page' === $post_type ) ? false : $use;
	}

	public static function title_placeholder( $text, $post ) {
		$type = Epic_Schema::types()[ $post->post_type ] ?? null;

		return $type['title_label'] ?? $text;
	}

	/** A heading above the main editor, naming what it holds (Full text, Biography...). */
	public static function body_label( $post ): void {
		$type = Epic_Schema::types()[ $post->post_type ] ?? null;

		if ( $type && ! empty( $type['body_label'] ) ) {
			echo '<p class="epic-body-label">' . esc_html( $type['body_label'] ) . '</p>';
		} elseif ( 'page' === $post->post_type ) {
			echo '<p class="epic-body-label">Main content</p>';
		}
	}

	/**
	 * A page the site depends on keeps its address: its content, title and hero are
	 * freely editable, but the slug is put back, as the Laravel dashboard made it
	 * read-only.
	 */
	public static function lock_builtin_slug( $data, $postarr ) {
		if ( 'page' !== ( $data['post_type'] ?? '' ) || empty( $postarr['ID'] ) ) {
			return $data;
		}

		$key = get_post_meta( (int) $postarr['ID'], '_epic_key', true );
		$def = $key ? ( Epic_Schema::builtin_pages()[ $key ] ?? null ) : null;

		if ( $def && '' !== $def['path'] ) {
			$data['post_name'] = basename( $def['path'] );
		}

		return $data;
	}

	/* ---------------------------------------------------------- admin lists --- */

	public static function admin_lists(): void {
		foreach ( Epic_Schema::types() as $name => $type ) {
			add_filter(
				"manage_{$name}_posts_columns",
				static fn( $columns ) => self::columns( $columns, $type )
			);
			add_action(
				"manage_{$name}_posts_custom_column",
				static function ( $column, $post_id ) use ( $type ) {
					self::column_value( $column, (int) $post_id, $type );
				},
				10,
				2
			);
		}

		add_action( 'restrict_manage_posts', array( __CLASS__, 'filter_dropdowns' ) );

		// Pages: show which built-in page each one is.
		add_filter(
			'manage_page_posts_columns',
			static function ( $columns ) {
				$columns['epic_key'] = 'EPIC page';

				return $columns;
			}
		);
		add_action(
			'manage_page_posts_custom_column',
			static function ( $column, $post_id ) {
				if ( 'epic_key' !== $column ) {
					return;
				}

				$key = get_post_meta( $post_id, '_epic_key', true );
				$def = $key ? ( Epic_Schema::builtin_pages()[ $key ] ?? null ) : null;
				echo $def ? '<span class="epic-badge">' . esc_html( $key ) . '</span>' : '<span class="description">Custom page</span>';
			},
			10,
			2
		);
	}

	/**
	 * @param array<string,string> $columns
	 * @return array<string,string>
	 */
	private static function columns( array $columns, array $type ): array {
		$out = array();

		if ( isset( $columns['cb'] ) ) {
			$out['cb'] = $columns['cb'];
		}

		// Images first, then the title, as on the Laravel lists.
		foreach ( $type['columns'] as $col ) {
			if ( 'image' === $col['kind'] ) {
				$out[ 'epic_' . $col['field'] ] = $col['label'];
			}
		}

		$out['title'] = $columns['title'] ?? 'Title';

		foreach ( $type['columns'] as $col ) {
			if ( 'image' !== $col['kind'] ) {
				$out[ 'epic_' . $col['field'] ] = $col['label'];
			}
		}

		if ( isset( $columns['date'] ) && empty( $type['form'] ) ) {
			$out['date'] = $columns['date'];
		} elseif ( isset( $columns['date'] ) ) {
			$out['date'] = 'Received';
		}

		return $out;
	}

	private static function column_value( string $column, int $post_id, array $type ): void {
		foreach ( $type['columns'] as $col ) {
			if ( 'epic_' . $col['field'] !== $column ) {
				continue;
			}

			$field = $col['field'];
			$raw   = 'menu_order' === $field ? get_post_field( 'menu_order', $post_id ) : ( 'page' === $col['kind'] ? get_post_field( 'post_parent', $post_id ) : get_post_meta( $post_id, 'epic_' . $field, true ) );

			switch ( $col['kind'] ) {
				case 'badge':
					$map = Epic_Schema::options( $col['map'] ?? array() );
					echo '' === (string) $raw ? '—' : '<span class="epic-badge">' . esc_html( $map[ $raw ] ?? $raw ) . '</span>';
					break;

				case 'image':
					$pic = Epic_Pic::make( $raw );
					echo $pic ? '<img class="epic-thumb" src="' . esc_url( $pic->url ) . '" alt="" loading="lazy">' : '—';
					break;

				case 'bool':
					echo in_array( $raw, array( '1', 1, true ), true ) ? '<span class="epic-yes">Yes</span>' : '<span class="epic-no">No</span>';
					break;

				case 'date':
				case 'datetime':
					$date = Epic_Item::to_date( (string) $raw );
					echo $date ? esc_html( $date->format( 'datetime' === $col['kind'] ? 'd M Y, H:i' : 'd M Y' ) ) : '—';
					break;

				case 'icon':
					echo $raw ? '<span class="epic-icon-cell">' . Epic_Icons::render( (string) $raw, 'icon' ) . '</span>' : '—'; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup.
					break;

				case 'page':
					echo $raw ? esc_html( get_the_title( (int) $raw ) ) : '—';
					break;

				case 'count':
					echo is_array( $raw ) ? (int) count( $raw ) : 0;
					break;

				case 'order':
					echo (int) $raw;
					break;

				default:
					echo '' === (string) $raw ? '—' : esc_html( (string) $raw );
			}

			return;
		}
	}

	/** Dropdown filters above a list, for every field marked `filter`. */
	public static function filter_dropdowns( string $post_type ): void {
		$type = Epic_Schema::types()[ $post_type ] ?? null;

		if ( ! $type ) {
			return;
		}

		foreach ( $type['fields'] as $field ) {
			if ( empty( $field['filter'] ) ) {
				continue;
			}

			$param   = 'epic_f_' . $field['key'];
			$current = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

			echo '<select name="' . esc_attr( $param ) . '"><option value="">All ' . esc_html( strtolower( $field['label'] ) ) . 's</option>';
			foreach ( Epic_Schema::options( $field['options'] ) as $value => $label ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $value ), selected( $current, (string) $value, false ), esc_html( $label ) );
			}
			echo '</select>';
		}
	}

	/** Apply those filters and each type's natural order to the admin list. */
	public static function admin_query( WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$post_type = $query->get( 'post_type' );
		$type      = is_string( $post_type ) ? ( Epic_Schema::types()[ $post_type ] ?? null ) : null;

		if ( ! $type ) {
			return;
		}

		$meta = (array) $query->get( 'meta_query' );
		foreach ( $type['fields'] as $field ) {
			$param = 'epic_f_' . ( $field['key'] ?? '' );
			if ( ! empty( $field['filter'] ) && ! empty( $_GET[ $param ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$meta[] = array(
					'key'   => 'epic_' . $field['key'],
					'value' => sanitize_text_field( wp_unslash( $_GET[ $param ] ) ), // phpcs:ignore WordPress.Security.NonceVerification
				);
			}
		}
		if ( $meta ) {
			$query->set( 'meta_query', $meta );
		}

		// Lists of curated content follow the order the site shows them in.
		if ( empty( $_GET['orderby'] ) && ! empty( $type['orderby'] ) && ! in_array( 'date', array_keys( $type['orderby'] ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			foreach ( Epic_Data::ordering( $post_type ) as $arg => $value ) {
				if ( 'meta_key' === $arg ) {
					continue; // Ordering by a field would hide items that lack it.
				}
				if ( 'orderby' === $arg ) {
					$value = array_filter( (array) $value, static fn( $k ) => 'meta_value' !== $k, ARRAY_FILTER_USE_KEY );
				}
				$query->set( $arg, $value );
			}
		}
	}
}
