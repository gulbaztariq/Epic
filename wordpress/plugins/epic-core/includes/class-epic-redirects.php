<?php
/**
 * Old addresses that must keep working, as permanent redirects:
 *
 * - /p/{slug}                       custom pages used to live under /p/
 * - /who-we-are/board-of-governance the Board's earlier name
 * - /sitemap.xml                    the sitemap is now Rank Math's
 * - /search?q=… and /?s=…           both reach the one search page
 *
 * plus the container pages (/what-we-do, /get-involved, /media) that simply pass
 * the visitor on to their first real page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Redirects {

	public static function init(): void {
		add_action( 'parse_request', array( __CLASS__, 'legacy' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'container' ), 0 );
		add_action( 'template_redirect', array( __CLASS__, 'core_search' ), 1 );
		add_filter( 'robots_txt', array( __CLASS__, 'robots' ), 20, 2 );
		add_action( 'do_faviconico', array( __CLASS__, 'favicon' ), 0 );
	}

	/** The request path relative to the site, without slashes at the ends. */
	private static function path(): string {
		$uri  = (string) wp_parse_url( isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/', PHP_URL_PATH ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$base = trim( (string) wp_parse_url( home_url(), PHP_URL_PATH ), '/' );
		$path = trim( rawurldecode( $uri ), '/' );

		if ( '' !== $base && 0 === strpos( $path, $base ) ) {
			$path = trim( substr( $path, strlen( $base ) ), '/' );
		}

		return $path;
	}

	public static function legacy(): void {
		if ( is_admin() || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? 'GET' ) ) {
			return;
		}

		$path = self::path();

		// /p/{slug}: a custom page, now at the top level.
		if ( preg_match( '~^p/([^/]+)$~', $path, $m ) ) {
			$page = get_page_by_path( sanitize_title( $m[1] ) );
			if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
				self::go( get_permalink( $page ) );
			}
		}

		// The Board of Governance was renamed the Board of Directors.
		if ( 'who-we-are/board-of-governance' === $path ) {
			self::go( epic_page_url( 'board' ) );
		}

		if ( 'sitemap.xml' === $path ) {
			self::go( defined( 'RANK_MATH_VERSION' ) ? home_url( '/sitemap_index.xml' ) : home_url( '/wp-sitemap.xml' ) );
		}
	}

	/** A container page hands the visitor on to the page it stands in front of. */
	public static function container(): void {
		if ( ! is_page() ) {
			return;
		}

		$target = get_post_meta( get_queried_object_id(), '_epic_redirect', true );

		if ( $target ) {
			self::go( home_url( '/' . ltrim( (string) $target, '/' ) ) );
		}
	}

	/** WordPress' own /?s= search uses the site's search page, as /search?q= did. */
	public static function core_search(): void {
		if ( is_search() && ! is_admin() ) {
			self::go( add_query_arg( 'q', rawurlencode( get_search_query( false ) ), epic_page_url( 'search' ) ) );
		}
	}

	private static function go( string $url ): void {
		wp_safe_redirect( $url, 301 );
		exit;
	}

	/** The same crawl rules as the Laravel site, with the sitemap pointing at Rank Math's. */
	public static function robots( $output, $public ) {
		if ( ! $public ) {
			return $output;
		}

		$sitemap = defined( 'RANK_MATH_VERSION' ) ? home_url( '/sitemap_index.xml' ) : home_url( '/wp-sitemap.xml' );
		$lines   = array(
			'User-agent: *',
			'Disallow: /wp-admin/',
			'Allow: /wp-admin/admin-ajax.php',
			'Disallow: /wp-content/uploads/epic-cv/',
			'Disallow: /wp-content/uploads/epic/volunteers/',
			'',
			'Sitemap: ' . $sitemap,
		);

		return implode( "\n", $lines ) . "\n";
	}

	/** Browsers ask for /favicon.ico before reading the page: answer with the site's own icon. */
	public static function favicon(): void {
		$custom = Epic_Settings::image( 'favicon' );
		$file   = get_theme_file_path( 'assets/images/favicon.png' );

		if ( $custom ) {
			wp_safe_redirect( $custom, 302 );
			exit;
		}

		if ( is_file( $file ) ) {
			header( 'Content-Type: image/png' );
			header( 'Cache-Control: public, max-age=604800' );
			readfile( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions
			exit;
		}
	}
}
