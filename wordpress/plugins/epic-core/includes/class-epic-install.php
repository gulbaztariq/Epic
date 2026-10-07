<?php
/**
 * First-run setup: the structure the website depends on. Safe to run again at any
 * time (it only adds what is missing), which is what `wp epic setup` does.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Install {

	public static function activate(): void {
		self::run();
	}

	/** Everything the site needs besides its content. @return array<string,int> what was created */
	public static function run(): array {
		// The content types must exist before rewrite rules are flushed.
		Epic_Types::register();
		Epic_Types::rewrite_rules();

		Epic_Visits::install();

		// Addresses without a trailing slash, exactly as the Laravel site had them.
		update_option( 'permalink_structure', '/%postname%' );
		update_option( 'use_smilies', 0 );

		self::remove_defaults();
		$created = self::ensure_pages();

		$home = Epic_Data::page_id( 'home' );
		if ( $home ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $home );
		}

		flush_rewrite_rules( false );

		return $created;
	}

	/** Delete the "Hello world!" post and "Sample Page" a new WordPress ships, if untouched. */
	public static function remove_defaults(): void {
		$hello = get_page_by_path( 'hello-world', OBJECT, 'post' );
		if ( $hello && 0 === strpos( trim( wp_strip_all_tags( $hello->post_content ) ), 'Welcome to WordPress' ) ) {
			wp_delete_post( $hello->ID, true );
		}

		$sample = get_page_by_path( 'sample-page' );
		if ( $sample && 0 === strpos( trim( wp_strip_all_tags( $sample->post_content ) ), 'This is an example page' ) ) {
			wp_delete_post( $sample->ID, true );
		}
	}

	/**
	 * Create any built-in page that is missing, with its place in the address
	 * hierarchy, and the container pages that pass visitors on.
	 *
	 * @return array<string,int> key => page id for each page created
	 */
	public static function ensure_pages(): array {
		$created = array();
		$order   = 0;

		// Container pages first: they are the parents of several built-in pages.
		foreach ( Epic_Schema::container_pages() as $slug => $def ) {
			$page = get_page_by_path( $slug );

			if ( ! $page ) {
				$id = wp_insert_post(
					array(
						'post_type'   => 'page',
						'post_status' => 'publish',
						'post_title'  => $def['title'],
						'post_name'   => $slug,
						'menu_order'  => 900,
					)
				);
				update_post_meta( $id, '_epic_redirect', $def['to'] );
				update_post_meta( $id, 'rank_math_robots', array( 'noindex' ) );
				$created[ $slug ] = (int) $id;
			} elseif ( ! get_post_meta( $page->ID, '_epic_redirect', true ) ) {
				update_post_meta( $page->ID, '_epic_redirect', $def['to'] );
			}
		}

		// Shallow addresses before deep ones, so a parent always exists first.
		$pages = Epic_Schema::builtin_pages();
		uasort(
			$pages,
			static fn( $a, $b ) => substr_count( $a['path'], '/' ) <=> substr_count( $b['path'], '/' )
		);

		foreach ( $pages as $key => $def ) {
			++$order;
			Epic_Data::flush_pages();

			if ( Epic_Data::page_id( $key ) ) {
				continue;
			}

			$slug = '' === $def['path'] ? 'home' : basename( $def['path'] );

			// Adopt a page already sitting at this address (a fresh WordPress ships "Sample Page").
			$existing = '' === $def['path'] ? get_page_by_path( 'home' ) : get_page_by_path( $def['path'] );

			if ( $existing instanceof WP_Post && ! get_post_meta( $existing->ID, '_epic_key', true ) ) {
				update_post_meta( $existing->ID, '_epic_key', $key );
				continue;
			}

			$parent_path = dirname( $def['path'] );
			$parent      = ( '.' === $parent_path || '' === $parent_path ) ? null : get_page_by_path( $parent_path );

			$id = wp_insert_post(
				array(
					'post_type'   => 'page',
					'post_status' => 'publish',
					'post_title'  => $def['title'],
					'post_name'   => $slug,
					'post_parent' => $parent ? $parent->ID : 0,
					'menu_order'  => $order,
				),
				true
			);

			if ( is_wp_error( $id ) ) {
				continue;
			}

			update_post_meta( $id, '_epic_key', $key );

			if ( 'search' === $key ) {
				update_post_meta( $id, 'rank_math_robots', array( 'noindex' ) );
			}

			$created[ $key ] = (int) $id;
		}

		Epic_Data::flush_pages();

		return $created;
	}
}
