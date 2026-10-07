<?php
/**
 * A light visitor counter. A tiny script on each public page reports the view after
 * the page has loaded, so it keeps counting behind a full-page cache and never slows
 * a page down. People only: crawlers do not run the script, and obvious bots are
 * ignored on the server too.
 *
 * Totals carried over from the Laravel site are added to the live counts.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Visits {

	const TABLE         = 'epic_visits';
	const SCHEMA_OPTION = 'epic_visits_schema';
	const SCHEMA        = '1';

	public static function init(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
		add_action( 'wp_footer', array( __CLASS__, 'beacon' ), 99 );
		add_action( 'epic_prune_visits', array( __CLASS__, 'prune' ) );
		add_action( 'init', array( __CLASS__, 'maybe_upgrade' ) );
	}

	public static function table(): string {
		global $wpdb;

		return $wpdb->prefix . self::TABLE;
	}

	public static function maybe_upgrade(): void {
		if ( get_option( self::SCHEMA_OPTION ) !== self::SCHEMA ) {
			self::install();
		}
	}

	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		dbDelta(
			"CREATE TABLE {$table} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				visited_at DATETIME NOT NULL,
				day DATE NOT NULL,
				visitor_key VARCHAR(40) NOT NULL,
				path VARCHAR(191) NOT NULL,
				referrer_host VARCHAR(191) NULL,
				device VARCHAR(12) NOT NULL DEFAULT 'desktop',
				PRIMARY KEY  (id),
				KEY day (day),
				KEY visitor_key (visitor_key),
				KEY path (path)
			) {$charset};"
		);

		update_option( self::SCHEMA_OPTION, self::SCHEMA, true );

		if ( ! wp_next_scheduled( 'epic_prune_visits' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'epic_prune_visits' );
		}
	}

	/* ------------------------------------------------------------ recording --- */

	private static function enabled(): bool {
		if ( '0' === epic_setting( 'analytics_enabled', '1' ) ) {
			return false;
		}

		return ! ( '1' === epic_setting( 'analytics_respect_dnt', '1' ) && '1' === ( $_SERVER['HTTP_DNT'] ?? '' ) );
	}

	/** The reporting script, printed on public pages for people who are not editing. */
	public static function beacon(): void {
		if ( is_admin() || is_404() || is_feed() || is_preview() || is_customize_preview() || ( is_user_logged_in() && current_user_can( 'edit_posts' ) ) || ! self::enabled() ) {
			return;
		}

		$endpoint = esc_url_raw( rest_url( 'epic/v1/hit' ) );

		echo '<script>(function(){try{var m=document.cookie.match(/(?:^|; )epic_vid=([a-z0-9]{16,40})/),v=m?m[1]:Math.random().toString(36).slice(2,12)+Date.now().toString(36)+Math.random().toString(36).slice(2,8);v=v.slice(0,40);if(!m){document.cookie="epic_vid="+v+"; max-age=63072000; path=/; SameSite=Lax"}var b=JSON.stringify({v:v,p:location.pathname,r:document.referrer});if(navigator.sendBeacon){navigator.sendBeacon(' . wp_json_encode( $endpoint ) . ',new Blob([b],{type:"text/plain"}))}else{fetch(' . wp_json_encode( $endpoint ) . ',{method:"POST",body:b,keepalive:true,headers:{"Content-Type":"text/plain"}})}}catch(e){}})();</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- fixed script, endpoint is JSON-encoded.
	}

	public static function routes(): void {
		register_rest_route(
			'epic/v1',
			'/hit',
			array(
				'methods'             => 'POST',
				'callback'            => array( __CLASS__, 'hit' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	public static function hit( WP_REST_Request $request ) {
		if ( ! self::enabled() ) {
			return new WP_REST_Response( null, 204 );
		}

		$body = json_decode( (string) $request->get_body(), true );
		$body = is_array( $body ) ? $body : array();

		$key  = isset( $body['v'] ) ? strtolower( (string) $body['v'] ) : '';
		$path = isset( $body['p'] ) ? (string) $body['p'] : '';
		$ua   = (string) ( $_SERVER['HTTP_USER_AGENT'] ?? '' );

		if ( ! preg_match( '/^[a-z0-9]{16,40}$/', $key ) || '' === $path || '/' !== $path[0] || self::is_bot( $ua ) ) {
			return new WP_REST_Response( null, 204 );
		}

		$path = substr( preg_replace( '~[?#].*$~', '', $path ), 0, 191 );

		// No connection reports more than 90 views in ten minutes; that is a script, not a person.
		$rate  = 'epic_hits_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
		$count = (int) get_transient( $rate );
		if ( $count >= 90 ) {
			return new WP_REST_Response( null, 204 );
		}
		set_transient( $rate, $count + 1, 10 * MINUTE_IN_SECONDS );

		// The same visitor opening the same page again within half a minute is a reload.
		$guard = 'epic_hit_' . md5( $key . $path );
		if ( get_transient( $guard ) ) {
			return new WP_REST_Response( null, 204 );
		}
		set_transient( $guard, 1, 30 );

		$referrer = isset( $body['r'] ) ? (string) $body['r'] : '';
		$host     = $referrer ? (string) wp_parse_url( $referrer, PHP_URL_HOST ) : '';
		$site     = (string) wp_parse_url( home_url(), PHP_URL_HOST );

		global $wpdb;

		$now = current_time( 'mysql' );
		$wpdb->insert(
			self::table(),
			array(
				'visited_at'    => $now,
				'day'           => substr( $now, 0, 10 ),
				'visitor_key'   => $key,
				'path'          => $path,
				'referrer_host' => ( '' !== $host && $host !== $site ) ? substr( $host, 0, 191 ) : null,
				'device'        => self::device( $ua ),
			)
		);

		delete_transient( 'epic_visit_totals' );

		return new WP_REST_Response( null, 204 );
	}

	private static function is_bot( string $ua ): bool {
		return '' === $ua || (bool) preg_match( '/bot|crawl|spider|slurp|facebookexternalhit|preview|monitor|headless|lighthouse|pingdom|curl|wget|python|java\/|httpclient|scrapy/i', $ua );
	}

	private static function device( string $ua ): string {
		if ( preg_match( '/ipad|tablet|kindle|silk/i', $ua ) ) {
			return 'tablet';
		}

		return preg_match( '/mobi|android|iphone|ipod/i', $ua ) ? 'mobile' : 'desktop';
	}

	/* --------------------------------------------------------------- totals --- */

	/** @return array{views:int,visitors:int} Live counts plus anything carried over from the old site. */
	public static function totals(): array {
		$cached = get_transient( 'epic_visit_totals' );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;
		$table = self::table();

		$row = $wpdb->get_row( "SELECT COUNT(*) AS views, COUNT(DISTINCT visitor_key) AS visitors FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL

		$totals = array(
			'views'    => (int) ( $row->views ?? 0 ) + (int) get_option( 'epic_visits_offset_views', 0 ),
			'visitors' => (int) ( $row->visitors ?? 0 ) + (int) get_option( 'epic_visits_offset_visitors', 0 ),
		);

		set_transient( 'epic_visit_totals', $totals, 10 * MINUTE_IN_SECONDS );

		return $totals;
	}

	public static function total_visitors(): int {
		return self::totals()['visitors'];
	}

	public static function total_page_views(): int {
		return self::totals()['views'];
	}

	/** Delete records older than the retention setting (0 keeps everything). */
	public static function prune(): void {
		$days = (int) epic_setting( 'analytics_retention_days', '0' );

		if ( $days <= 0 ) {
			return;
		}

		global $wpdb;
		$wpdb->query( $wpdb->prepare( 'DELETE FROM ' . self::table() . ' WHERE day < %s', gmdate( 'Y-m-d', time() - $days * DAY_IN_SECONDS ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		delete_transient( 'epic_visit_totals' );
	}

	/* --------------------------------------------------------------- report --- */

	public static function screen(): void {
		if ( ! current_user_can( 'edit_pages' ) ) {
			return;
		}

		global $wpdb;

		$table = self::table();
		$range = isset( $_GET['range'] ) ? (int) $_GET['range'] : 30; // phpcs:ignore WordPress.Security.NonceVerification
		$range = in_array( $range, array( 7, 30, 90, 365 ), true ) ? $range : 30;
		$since = gmdate( 'Y-m-d', current_time( 'timestamp', true ) - ( $range - 1 ) * DAY_IN_SECONDS ); // phpcs:ignore WordPress.DateTime

		$daily = $wpdb->get_results( $wpdb->prepare( "SELECT day, COUNT(*) AS views, COUNT(DISTINCT visitor_key) AS visitors FROM {$table} WHERE day >= %s GROUP BY day ORDER BY day DESC", $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$pages = $wpdb->get_results( $wpdb->prepare( "SELECT path, COUNT(*) AS views FROM {$table} WHERE day >= %s GROUP BY path ORDER BY views DESC LIMIT 15", $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$refs  = $wpdb->get_results( $wpdb->prepare( "SELECT referrer_host AS host, COUNT(*) AS views FROM {$table} WHERE day >= %s AND referrer_host IS NOT NULL GROUP BY referrer_host ORDER BY views DESC LIMIT 10", $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$dev   = $wpdb->get_results( $wpdb->prepare( "SELECT device, COUNT(*) AS views FROM {$table} WHERE day >= %s GROUP BY device ORDER BY views DESC", $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$sum   = $wpdb->get_row( $wpdb->prepare( "SELECT COUNT(*) AS views, COUNT(DISTINCT visitor_key) AS visitors FROM {$table} WHERE day >= %s", $since ) ); // phpcs:ignore WordPress.DB.PreparedSQL
		$all   = self::totals();
		$peak  = max( 1, ...array_map( static fn( $d ) => (int) $d->views, $daily ?: array( (object) array( 'views' => 1 ) ) ) );

		echo '<div class="wrap"><h1>Visitors</h1>';
		echo '<p>People who opened a public page. Crawlers and logged-in editors are not counted. Detailed traffic analysis is available through Rank Math Analytics and Google Search Console.</p>';

		echo '<p>';
		foreach ( array( 7 => '7 days', 30 => '30 days', 90 => '90 days', 365 => '12 months' ) as $days => $label ) {
			printf( '<a class="button%s" href="%s">%s</a> ', $days === $range ? ' button-primary' : '', esc_url( admin_url( 'admin.php?page=epic-visitors&range=' . $days ) ), esc_html( $label ) );
		}
		echo '</p>';

		echo '<div class="epic-overview">';
		printf( '<div class="epic-card"><strong>%s</strong><span>Visitors, last %d days</span></div>', esc_html( number_format_i18n( (int) ( $sum->visitors ?? 0 ) ) ), (int) $range );
		printf( '<div class="epic-card"><strong>%s</strong><span>Page views, last %d days</span></div>', esc_html( number_format_i18n( (int) ( $sum->views ?? 0 ) ) ), (int) $range );
		printf( '<div class="epic-card"><strong>%s</strong><span>Visitors since launch</span></div>', esc_html( number_format_i18n( $all['visitors'] ) ) );
		printf( '<div class="epic-card"><strong>%s</strong><span>Page views since launch</span></div>', esc_html( number_format_i18n( $all['views'] ) ) );
		echo '</div>';

		echo '<h2>Day by day</h2><table class="widefat striped" style="max-width:900px"><thead><tr><th>Day</th><th>Visitors</th><th>Page views</th><th style="width:40%"></th></tr></thead><tbody>';
		foreach ( $daily as $d ) {
			printf(
				'<tr><td>%s</td><td>%d</td><td>%d</td><td><span style="display:block;height:10px;border-radius:2px;background:#0f6fc0;width:%d%%"></span></td></tr>',
				esc_html( wp_date( 'D, j M Y', strtotime( $d->day . ' 12:00:00' ) ) ),
				(int) $d->visitors,
				(int) $d->views,
				(int) round( $d->views / $peak * 100 )
			);
		}
		if ( ! $daily ) {
			echo '<tr><td colspan="4">No visits recorded in this period yet.</td></tr>';
		}
		echo '</tbody></table>';

		echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:24px;max-width:1100px">';
		self::table_block( 'Top pages', 'Page', $pages, 'path' );
		self::table_block( 'Where visitors came from', 'Site', $refs, 'host' );
		self::table_block( 'Devices', 'Device', $dev, 'device' );
		echo '</div></div>';
	}

	/** @param array<int,object> $rows */
	private static function table_block( string $title, string $label, array $rows, string $key ): void {
		echo '<div><h2>' . esc_html( $title ) . '</h2><table class="widefat striped"><thead><tr><th>' . esc_html( $label ) . '</th><th>Views</th></tr></thead><tbody>';
		foreach ( $rows as $row ) {
			printf( '<tr><td>%s</td><td>%d</td></tr>', esc_html( (string) $row->$key ), (int) $row->views );
		}
		if ( ! $rows ) {
			echo '<tr><td colspan="2">Nothing yet.</td></tr>';
		}
		echo '</tbody></table></div>';
	}
}
