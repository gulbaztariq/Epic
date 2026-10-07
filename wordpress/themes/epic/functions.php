<?php
/**
 * EPIC theme bootstrap: the design assets, the head, and routing of built-in pages to
 * their layouts. Content comes from the EPIC Core plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'EPIC_THEME_VERSION', '1.0.0' );

/** The theme is only the design; without the plugin there is nothing to show, so say so. */
if ( ! function_exists( 'epic_setting' ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p>The EPIC theme needs the <strong>EPIC Core</strong> plugin. Activate it under Plugins.</p></div>';
		}
	);

	add_action(
		'template_redirect',
		static function () {
			wp_die( 'The EPIC theme needs the EPIC Core plugin to be active.', 'EPIC', array( 'response' => 503 ) );
		}
	);

	return;
}

require_once get_theme_file_path( 'inc/components.php' );

add_action(
	'after_setup_theme',
	static function () {
		add_theme_support( 'title-tag' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'html5', array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ) );
	}
);

/* ------------------------------------------------------------------ head --- */

/** Mark the page as script-enabled before anything paints, as the original layout did. */
add_action(
	'wp_head',
	static function () {
		echo '<script>document.documentElement.classList.add(\'js\');</script>' . "\n";
	},
	1
);

add_action(
	'wp_head',
	static function () {
		echo '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
		echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";
		echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Montserrat:wght@700;800&family=Playfair+Display:ital,wght@0,600;0,700;1,500;1,600&display=swap" rel="stylesheet">' . "\n";

		$icon = Epic_Settings::image( 'favicon' ) ?: epic_asset( 'images/favicon.png' );
		echo '<link rel="icon" type="image/png" href="' . esc_url( $icon ) . '">' . "\n";
		echo '<link rel="apple-touch-icon" href="' . esc_url( $icon ) . '">' . "\n";
	},
	2
);

/** Code the editor pasted into Settings → Integrations. */
add_action(
	'wp_head',
	static function () {
		$code = epic_setting( 'head_code' );
		if ( $code ) {
			echo $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- administrator-entered tags, output as written.
		}
	},
	100
);

add_action(
	'wp_footer',
	static function () {
		$code = epic_setting( 'body_code' );
		if ( $code ) {
			echo $code . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- administrator-entered tags, output as written.
		}
	},
	100
);

add_action(
	'wp_enqueue_scripts',
	static function () {
		$css = get_theme_file_path( 'assets/css/site.css' );
		$wp  = get_theme_file_path( 'assets/css/wordpress.css' );
		$js  = get_theme_file_path( 'assets/js/site.js' );

		wp_enqueue_style( 'epic-site', get_theme_file_uri( 'assets/css/site.css' ), array(), (string) filemtime( $css ) );
		wp_enqueue_style( 'epic-wordpress', get_theme_file_uri( 'assets/css/wordpress.css' ), array( 'epic-site' ), (string) filemtime( $wp ) );
		wp_add_inline_style( 'epic-site', ':root{' . epic_theme_css() . '}' );

		wp_enqueue_script(
			'epic-site',
			get_theme_file_uri( 'assets/js/site.js' ),
			array(),
			(string) filemtime( $js ),
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		// The design is hand-written; WordPress' block and global styles would only fight it.
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
		wp_dequeue_style( 'classic-theme-styles' );
	},
	100
);

/** A clean head: no emoji script, generator tag, feed links nobody uses, or shortlinks. */
add_action(
	'init',
	static function () {
		remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
		remove_action( 'wp_print_styles', 'print_emoji_styles' );
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
		remove_action( 'wp_head', 'feed_links', 2 );
		remove_action( 'wp_head', 'feed_links_extra', 3 );
		remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
		remove_action( 'wp_head', 'wp_oembed_add_host_js' );
		remove_action( 'wp_head', 'rest_output_link_wp_head' );
		remove_action( 'wp_head', 'wp_resource_hints', 2 );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );

		// Block-theme machinery the hand-written design has no use for.
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles' );
		remove_action( 'wp_footer', 'wp_enqueue_global_styles', 1 );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_global_styles_css_custom_properties' );
		remove_action( 'wp_enqueue_scripts', 'wp_enqueue_classic_theme_styles' );
		remove_action( 'wp_body_open', 'wp_global_styles_render_svg_filters' );
	}
);

add_filter( 'wp_img_tag_add_auto_sizes', '__return_false' );
add_filter( 'option_use_smilies', '__return_zero' );
add_filter( 'run_wptexturize', '__return_false' );

/* -------------------------------------------------------------- routing --- */

/**
 * A built-in page is laid out by its key, not by a template the editor picks, so
 * nothing an editor does in the Page Attributes box can break the layout.
 */
add_filter(
	'template_include',
	static function ( $template ) {
		if ( ! is_page() ) {
			return $template;
		}

		$key    = (string) get_post_meta( get_queried_object_id(), '_epic_key', true );
		$layout = '' !== $key ? ( Epic_Schema::builtin_pages()[ $key ]['layout'] ?? '' ) : '';

		if ( '' !== $layout ) {
			$file = locate_template( 'templates/pages/' . $layout . '.php' );
			if ( $file ) {
				return $file;
			}
		}

		return $template;
	},
	20
);

/** Admin users see the toolbar over the sticky header; nudge it down by the bar's height. */
add_action(
	'wp_head',
	static function () {
		if ( is_admin_bar_showing() ) {
			echo '<style>.site-header{top:var(--wp-admin--admin-bar--height,32px)}</style>' . "\n";
		}
	},
	101
);
