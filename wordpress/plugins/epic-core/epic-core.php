<?php
/**
 * Plugin Name:       EPIC Core
 * Description:       The content model, edit screens, forms, visitor counter, SEO integration and importer behind the EPIC website. Use it with the EPIC theme and Rank Math SEO.
 * Version:           1.0.0
 * Requires at least: 6.0
 * Requires PHP:      8.0
 * Author:            EPIC
 * License:           GPL-2.0-or-later
 * Text Domain:       epic-core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EPIC_CORE_VERSION', '1.0.0' );
define( 'EPIC_CORE_FILE', __FILE__ );
define( 'EPIC_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'EPIC_CORE_URL', plugin_dir_url( __FILE__ ) );

foreach ( array( 'icons', 'pic', 'schema', 'item', 'settings', 'data', 'menus', 'types', 'fields', 'admin', 'forms', 'visits', 'redirects', 'seo', 'install', 'importer' ) as $epic_file ) {
	require_once EPIC_CORE_DIR . 'includes/class-epic-' . $epic_file . '.php';
}
unset( $epic_file );

require_once EPIC_CORE_DIR . 'includes/helpers.php';

Epic_Menus::init();
Epic_Types::init();
Epic_Fields::init();
Epic_Admin::init();
Epic_Forms::init();
Epic_Visits::init();
Epic_Redirects::init();
Epic_Seo::init();

register_activation_hook( __FILE__, array( 'Epic_Install', 'activate' ) );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once EPIC_CORE_DIR . 'includes/class-epic-cli.php';
	WP_CLI::add_command( 'epic', 'Epic_Cli' );
}

/** Clear cached page lookups whenever a page or its key changes. */
add_action(
	'save_post_page',
	static function () {
		Epic_Data::flush_pages();
	}
);
add_action(
	'updated_post_meta',
	static function ( $meta_id, $post_id, $meta_key ) {
		if ( '_epic_key' === $meta_key ) {
			Epic_Data::flush_pages();
		}
	},
	10,
	3
);
