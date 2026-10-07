<?php
/**
 * WP-CLI commands:
 *
 *   wp epic setup                      create the pages the site depends on, set permalinks
 *   wp epic import <folder>            import an export made by tools/export-laravel.php
 *   wp epic seo                        configure Rank Math for this site
 *   wp epic status                     what is on the site
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) {
	return;
}

class Epic_Cli {

	/**
	 * Create the structure the website needs: built-in pages, container pages,
	 * the visitor table, permalinks. Safe to run again.
	 */
	public function setup(): void {
		$created = Epic_Install::run();

		WP_CLI::success( $created ? 'Created: ' . implode( ', ', array_keys( $created ) ) : 'Everything was already in place.' );
	}

	/**
	 * Import a folder written by tools/export-laravel.php.
	 *
	 * ## OPTIONS
	 *
	 * <folder>
	 * : The export folder (it holds export.json and uploads/).
	 *
	 * [--dry-run]
	 * : Describe what would be imported, and change nothing.
	 *
	 * [--skip-media]
	 * : Do not copy uploaded files again (they were imported earlier).
	 */
	public function import( array $args, array $assoc ): void {
		$importer = new Epic_Importer(
			(string) $args[0],
			static function ( $message ) {
				WP_CLI::log( $message );
			}
		);

		$loaded = $importer->load();
		if ( is_wp_error( $loaded ) ) {
			WP_CLI::error( $loaded->get_error_message() );
		}

		if ( ! empty( $assoc['dry-run'] ) ) {
			foreach ( $importer->plan() as $line ) {
				WP_CLI::log( '  ' . $line );
			}
			WP_CLI::success( 'Dry run only: nothing was changed.' );

			return;
		}

		$counts = $importer->run( ! empty( $assoc['skip-media'] ) );

		foreach ( $counts as $table => $count ) {
			WP_CLI::log( sprintf( '  %-24s %d', $table, $count ) );
		}

		$warnings = $importer->warnings();
		WP_CLI::success( 'Imported.' . ( $warnings ? ' ' . count( $warnings ) . ' warning(s), listed above.' : '' ) );
	}

	/**
	 * Configure Rank Math for this site (titles, sitemap, schema, modules).
	 */
	public function seo(): void {
		foreach ( Epic_Seo::configure() as $line ) {
			WP_CLI::log( '  ' . $line );
		}

		WP_CLI::success( 'Done.' );
	}

	/**
	 * Show what is on the site.
	 */
	public function status(): void {
		$rows = array();

		foreach ( Epic_Schema::types() as $name => $type ) {
			$counts = wp_count_posts( $name );
			$rows[] = array(
				'type'      => $type['label'],
				'published' => (int) ( $counts->publish ?? 0 ),
				'hidden'    => (int) ( $counts->draft ?? 0 ),
			);
		}

		$counts = wp_count_posts( 'page' );
		array_unshift(
			$rows,
			array(
				'type'      => 'Pages',
				'published' => (int) $counts->publish,
				'hidden'    => (int) $counts->draft,
			)
		);

		WP_CLI\Utils\format_items( 'table', $rows, array( 'type', 'published', 'hidden' ) );
		WP_CLI::log( 'Rank Math: ' . ( defined( 'RANK_MATH_VERSION' ) ? 'active ' . RANK_MATH_VERSION : 'not active' ) );
		WP_CLI::log( 'Theme: ' . get_stylesheet() );
	}
}
