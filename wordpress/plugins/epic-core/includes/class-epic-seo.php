<?php
/**
 * Search engine optimisation, built on Rank Math.
 *
 * Rank Math does the heavy lifting (titles, descriptions, canonical addresses, Open
 * Graph and Twitter cards, XML sitemap, JSON-LD, redirections, 404 monitor). This
 * class:
 *
 *  - configures it once for this site (configure()),
 *  - gives it what only EPIC knows: the introduction of a page is its description,
 *    the main picture of an item is its social image, a page's header breadcrumbs
 *    are its BreadcrumbList,
 *  - adds the structured data Rank Math's free version lacks for events and vacancies,
 *  - prints a basic title, description and social tags if Rank Math is switched off,
 *    so the site is never left without them.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Seo {

	/** The main picture field of each type; it becomes the featured image (and so the social image). */
	const IMAGE_FIELDS = array(
		'page'             => 'hero_image',
		'epic_project'     => 'image',
		'epic_publication' => 'cover_image',
		'epic_post'        => 'image',
		'epic_event'       => 'image',
		'epic_podcast'     => 'cover_image',
		'epic_album'       => 'cover_image',
	);

	public static function init(): void {
		add_action( 'save_post', array( __CLASS__, 'sync_featured_image' ), 30, 2 );

		add_filter( 'rank_math/frontend/description', array( __CLASS__, 'description' ), 20 );
		add_filter( 'rank_math/frontend/breadcrumb/items', array( __CLASS__, 'breadcrumbs' ), 20 );
		add_filter( 'rank_math/json_ld', array( __CLASS__, 'json_ld' ), 20, 2 );
		add_filter( 'rank_math/frontend/robots', array( __CLASS__, 'robots' ), 20 );
		add_filter( 'rank_math/opengraph/facebook/image', array( __CLASS__, 'default_social_image' ), 20 );
		add_filter( 'rank_math/opengraph/twitter/image', array( __CLASS__, 'default_social_image' ), 20 );

		// Without Rank Math the site still gets a sensible title and tags.
		add_filter( 'pre_get_document_title', array( __CLASS__, 'fallback_title' ), 5 );
		add_action( 'wp_head', array( __CLASS__, 'fallback_head' ), 1 );
	}

	private static function rank_math_active(): bool {
		return defined( 'RANK_MATH_VERSION' );
	}

	/* -------------------------------------------------- featured image sync --- */

	/** Keep the featured image in step with the item's main picture. */
	public static function sync_featured_image( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || ! isset( self::IMAGE_FIELDS[ $post->post_type ] ) ) {
			return;
		}

		$field = self::IMAGE_FIELDS[ $post->post_type ];
		$value = get_post_meta( $post_id, 'epic_' . $field, true );

		// An album with no cover uses its first photo.
		if ( empty( $value ) && 'epic_album' === $post->post_type ) {
			$photos = get_post_meta( $post_id, 'epic_images', true );
			$value  = is_array( $photos ) && ! empty( $photos[0]['id'] ) ? $photos[0]['id'] : '';
		}

		if ( is_numeric( $value ) && (int) $value > 0 ) {
			update_post_meta( $post_id, '_thumbnail_id', (int) $value );
		} else {
			delete_post_meta( $post_id, '_thumbnail_id' );
		}
	}

	/* ------------------------------------------------------ filters (Rank Math) --- */

	/** A page's description is its introduction when nobody wrote one; an item's is its summary or opening text. */
	public static function description( $description ) {
		if ( '' !== trim( (string) $description ) || ! is_singular() ) {
			return $description;
		}

		$post = get_queried_object();
		if ( ! $post instanceof WP_Post ) {
			return $description;
		}

		return self::derived_description( $post ) ?: $description;
	}

	public static function derived_description( WP_Post $post ): string {
		if ( '' !== trim( $post->post_excerpt ) ) {
			return epic_summarise( $post->post_excerpt, 155 );
		}

		if ( 'page' === $post->post_type ) {
			$intro = (string) get_post_meta( $post->ID, 'epic_intro', true );
			$text  = '' !== trim( $intro ) ? $intro : $post->post_content;
			$sub   = (string) get_post_meta( $post->ID, 'epic_hero_subtitle', true );

			return epic_summarise( '' !== trim( $text ) ? $text : $sub, 155 );
		}

		return epic_summarise( $post->post_content, 155 );
	}

	/** A page with no picture of its own is shared with the site logo, as the original site did. */
	public static function default_social_image( $url ) {
		return '' !== trim( (string) $url ) ? $url : epic_site_logo();
	}

	/** Pages that exist only to redirect or to hold search results are never indexed. */
	public static function robots( $robots ) {
		if ( is_page() && ( get_post_meta( get_queried_object_id(), '_epic_redirect', true ) || 'search' === get_post_meta( get_queried_object_id(), '_epic_key', true ) ) ) {
			$robots['index'] = 'noindex';
		}

		return $robots;
	}

	/**
	 * The trail in the page header is the BreadcrumbList search engines see.
	 *
	 * @param array<int,array<int,string>> $crumbs
	 * @return array<int,array<int,string>>
	 */
	public static function breadcrumbs( $crumbs ) {
		$trail = $GLOBALS['epic_breadcrumbs'] ?? null;

		if ( ! is_array( $trail ) || ! $trail ) {
			return $crumbs;
		}

		// A label with no address of its own in the header ("What We Do") still names a real page.
		$section_pages = array(
			'Who We Are'          => 'about-us',
			'What We Do'          => 'themes',
			'Publications'        => 'publications',
			'Partnerships & MoUs' => 'partnerships',
			'Get Involved'        => 'careers',
			'Media'               => 'press-releases',
		);

		$items = array( array( 'Home', home_url( '/' ) ) );
		$last  = array_key_last( $trail );

		foreach ( $trail as $label => $url ) {
			if ( ! $url && $label === $last ) {
				$url = strtok( epic_current_url(), '?' );
			} elseif ( ! $url && isset( $section_pages[ $label ] ) ) {
				$url = epic_page_url( $section_pages[ $label ] );
			}

			// The current page is named after the page itself, so a section and its first page do not repeat.
			if ( $url && ! empty( $items ) && $items[ count( $items ) - 1 ][1] === $url ) {
				continue;
			}

			if ( $url ) {
				$items[] = array( html_entity_decode( (string) $label, ENT_QUOTES, 'UTF-8' ), (string) $url );
			}
		}

		return $items;
	}

	/**
	 * Structured data beyond Rank Math's free schema: Event, JobPosting, and the
	 * organisation's social profiles.
	 *
	 * @param array<string,mixed> $data
	 * @return array<string,mixed>
	 */
	public static function json_ld( $data, $jsonld = null ) {
		// Searches go to the site's own search page.
		if ( isset( $data['WebSite']['potentialAction']['target'] ) ) {
			$data['WebSite']['potentialAction']['target'] = add_query_arg( 'q', '{search_term_string}', epic_page_url( 'search' ) );
		}

		// Every page is at least a WebPage, including the ones Rank Math has no schema type for.
		if ( is_singular() && ! isset( $data['WebPage'] ) && ! isset( $data['epicWebPage'] ) ) {
			$post = get_queried_object();
			$page = array(
				'@type'         => 'WebPage',
				'@id'           => get_permalink( $post ) . '#webpage',
				'url'           => get_permalink( $post ),
				'name'          => html_entity_decode( wp_strip_all_tags( get_the_title( $post ) ), ENT_QUOTES, 'UTF-8' ),
				'datePublished' => mysql2date( 'c', $post->post_date_gmt, false ),
				'dateModified'  => mysql2date( 'c', $post->post_modified_gmt, false ),
				'isPartOf'      => array( '@id' => home_url( '/#website' ) ),
				'inLanguage'    => get_bloginfo( 'language' ),
			);

			$description = self::derived_description( $post );
			if ( '' !== $description ) {
				$page['description'] = $description;
			}

			if ( isset( $data['BreadcrumbList']['@id'] ) ) {
				$page['breadcrumb'] = array( '@id' => $data['BreadcrumbList']['@id'] );
			}

			$data['epicWebPage'] = $page;
		}

		if ( is_singular( 'epic_event' ) ) {
			$event = self::event_schema( Epic_Item::from_post( get_queried_object() ) );
			if ( $event ) {
				$data['epicEvent'] = $event;
			}
		}

		if ( is_singular( 'epic_career' ) ) {
			$job = self::job_schema( Epic_Item::from_post( get_queried_object() ) );
			if ( $job ) {
				$data['epicJob'] = $job;
			}
		}

		return $data;
	}

	/** @return array<string,mixed>|null */
	private static function event_schema( Epic_Item $event ): ?array {
		if ( ! $event->starts_at ) {
			return null;
		}

		$online = 'Online' === $event->mode;
		$modes  = array(
			'In-Person'    => 'https://schema.org/OfflineEventAttendanceMode',
			'Online'       => 'https://schema.org/OnlineEventAttendanceMode',
			'Hybrid Event' => 'https://schema.org/MixedEventAttendanceMode',
		);

		$schema = array(
			'@type'               => 'Event',
			'name'                => $event->title,
			'url'                 => $event->url,
			'startDate'           => $event->starts_at->format( 'c' ),
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'eventAttendanceMode' => $modes[ $event->mode ] ?? $modes['In-Person'],
			'description'         => epic_summarise( $event->excerpt ?: $event->body, 300 ),
			'organizer'           => array(
				'@type' => 'Organization',
				'name'  => (string) epic_setting( 'site_name_full', 'Economic Policy and Innovation Centre (EPIC)' ),
				'url'   => home_url( '/' ),
			),
		);

		if ( $event->ends_at ) {
			$schema['endDate'] = $event->ends_at->format( 'c' );
		}

		if ( $event->image ) {
			$schema['image'] = array( $event->image->url );
		}

		if ( $online ) {
			$schema['location'] = array(
				'@type' => 'VirtualLocation',
				'url'   => $event->registration_url ?: $event->url,
			);
		} elseif ( $event->location || $event->city ) {
			$schema['location'] = array(
				'@type'   => 'Place',
				'name'    => (string) ( $event->location ?: $event->city ),
				'address' => array(
					'@type'           => 'PostalAddress',
					'addressLocality' => (string) $event->city,
					'addressCountry'  => 'PK',
				),
			);
		}

		if ( $event->registration_url ) {
			$schema['offers'] = array(
				'@type'         => 'Offer',
				'url'           => $event->registration_url,
				'price'         => '0',
				'priceCurrency' => 'PKR',
				'availability'  => 'https://schema.org/InStock',
			);
		}

		return $schema;
	}

	/** @return array<string,mixed>|null */
	private static function job_schema( Epic_Item $job ): ?array {
		if ( ! $job->is_open ) {
			return null;
		}

		$types = array(
			'Full Time'   => 'FULL_TIME',
			'Part Time'   => 'PART_TIME',
			'Internship'  => 'INTERN',
			'Consultancy' => 'CONTRACTOR',
			'Volunteer'   => 'VOLUNTEER',
			'Fellowship'  => 'OTHER',
		);

		$schema = array(
			'@type'              => 'JobPosting',
			'title'              => $job->title,
			'description'        => epic_content( $job->body ) . ( $job->requirements ? epic_rich( $job->requirements ) : '' ),
			'datePosted'         => mysql2date( 'c', $job->post_date, false ),
			'employmentType'     => $types[ $job->type ] ?? 'OTHER',
			'hiringOrganization' => array(
				'@type'  => 'Organization',
				'name'   => (string) epic_setting( 'site_name_full', 'Economic Policy and Innovation Centre (EPIC)' ),
				'sameAs' => home_url( '/' ),
				'logo'   => epic_site_logo(),
			),
			'directApply'        => (bool) ( $job->apply_url ),
		);

		if ( $job->deadline ) {
			$schema['validThrough'] = $job->deadline->setTime( 23, 59, 59 )->format( 'c' );
		}

		$location = (string) $job->location;
		if ( '' !== $location && false !== stripos( $location, 'remote' ) ) {
			$schema['jobLocationType']             = 'TELECOMMUTE';
			$schema['applicantLocationRequirements'] = array(
				'@type' => 'Country',
				'name'  => 'Pakistan',
			);
		} elseif ( '' !== $location ) {
			$schema['jobLocation'] = array(
				'@type'   => 'Place',
				'address' => array(
					'@type'           => 'PostalAddress',
					'addressLocality' => $location,
					'addressCountry'  => 'PK',
				),
			);
		}

		return $schema;
	}

	/* -------------------------------------------------------- when Rank Math is off --- */

	public static function fallback_title( $title ) {
		if ( self::rank_math_active() || is_admin() ) {
			return $title;
		}

		$site = (string) epic_setting( 'site_name', 'EPIC' );

		if ( is_front_page() ) {
			$custom = is_singular() ? (string) get_post_meta( get_queried_object_id(), 'rank_math_title', true ) : '';

			return '' !== $custom ? self::plain_title( $custom ) : (string) epic_setting( 'site_name', 'EPIC — Economic Policy and Innovation Centre' );
		}

		if ( is_singular() ) {
			$custom = (string) get_post_meta( get_queried_object_id(), 'rank_math_title', true );
			$name   = '' !== $custom ? self::plain_title( $custom ) : get_the_title();

			return $name . ' | ' . $site;
		}

		if ( is_404() ) {
			return 'Page not found | ' . $site;
		}

		return $title;
	}

	/** Rank Math stores titles with its variables; show them as plain text. */
	private static function plain_title( string $template ): string {
		return trim( str_replace( array( '%sep%', '%sitename%' ), array( '|', (string) epic_setting( 'site_name', 'EPIC' ) ), $template ) );
	}

	public static function fallback_head(): void {
		if ( self::rank_math_active() || is_admin() ) {
			return;
		}

		$site = (string) epic_setting( 'site_name', 'EPIC' );
		$desc = (string) epic_setting( 'site_description', '' );
		$img  = epic_site_logo();
		$url  = epic_current_url();

		if ( is_singular() ) {
			$post   = get_queried_object();
			$custom = (string) get_post_meta( $post->ID, 'rank_math_description', true );
			$desc   = '' !== $custom ? $custom : ( self::derived_description( $post ) ?: $desc );
			$thumb  = get_post_thumbnail_id( $post );
			$img    = $thumb ? (string) wp_get_attachment_url( $thumb ) : $img;
			$url    = get_permalink( $post );
		}

		$title = wp_get_document_title();

		echo '<meta name="description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:type" content="website">' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( $site ) . '">' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $title ) . '">' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $desc ) . '">' . "\n";
		echo '<meta property="og:image" content="' . esc_url( $img ) . '">' . "\n";
		echo '<meta property="og:url" content="' . esc_url( $url ) . '">' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image">' . "\n";
		if ( ! is_singular() ) {
			echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n";
		}
	}

	/* ------------------------------------------------------------- configure --- */

	/**
	 * Set Rank Math up for this site: separator and templates, organisation details,
	 * sitemap contents, modules. Existing editor choices in other keys are kept.
	 *
	 * @return string[] what was done, for the CLI to print
	 */
	public static function configure(): array {
		$log = array();

		if ( ! self::rank_math_active() ) {
			return array( 'Rank Math is not active: activate the plugin, then run this again.' );
		}

		$site_name = (string) epic_setting( 'site_name', get_bloginfo( 'name' ) );
		$full_name = (string) epic_setting( 'site_name_full', $site_name );
		$logo_id   = (int) epic_setting( 'logo', '0' );
		$logo_url  = $logo_id ? (string) wp_get_attachment_url( $logo_id ) : epic_asset( 'images/logo.png' );

		/* ---- Titles & meta ------------------------------------------------ */
		$titles = (array) get_option( 'rank-math-options-titles', array() );

		$home       = Epic_Data::page( 'home' );
		$home_title = $home->id ? (string) get_post_meta( $home->id, 'rank_math_title', true ) : '';

		$titles = array_merge(
			$titles,
			array(
				'title_separator'          => '|',
				'capitalize_titles'        => 'off',
				'twitter_card_type'        => 'summary_large_image',
				'knowledgegraph_type'      => 'company',
				'knowledgegraph_name'      => $full_name,
				'website_name'             => $site_name,
				'knowledgegraph_logo'      => $logo_url,
				'knowledgegraph_logo_id'   => $logo_id,
				'local_business_type'      => 'Organization',
				'homepage_title'           => '' !== $home_title ? self::plain_title( $home_title ) : $site_name,
				'homepage_description'     => (string) epic_setting( 'site_description', '' ),
				'homepage_custom_robots'   => 'off',
				'disable_author_archives'  => 'on',
				'disable_date_archives'    => 'on',
				'noindex_search'           => 'on',
				'open_graph_image'         => $logo_url,
				'open_graph_image_id'      => $logo_id,
				'url_author_base'          => 'author',
			)
		);

		$article_types = array(
			'epic_publication' => 'Article',
			'epic_post'        => 'BlogPosting',
			'epic_project'     => 'Article',
			'epic_podcast'     => 'Article',
		);

		$public = array( 'page' => 'off' );
		foreach ( Epic_Schema::types() as $name => $type ) {
			if ( ! empty( $type['public'] ) ) {
				$public[ $name ] = $article_types[ $name ] ?? 'off';
			}
		}

		foreach ( $public as $post_type => $snippet ) {
			$titles[ 'pt_' . $post_type . '_title' ]               = '%title% %sep% %sitename%';
			$titles[ 'pt_' . $post_type . '_description' ]         = '%excerpt%';
			$titles[ 'pt_' . $post_type . '_robots' ]              = array();
			$titles[ 'pt_' . $post_type . '_custom_robots' ]       = 'off';
			$titles[ 'pt_' . $post_type . '_add_meta_box' ]        = 'on';
			$titles[ 'pt_' . $post_type . '_bulk_editing' ]        = 'editing';
			$titles[ 'pt_' . $post_type . '_link_suggestions' ]    = 'on';
			$titles[ 'pt_' . $post_type . '_ls_use_fk' ]           = 'titles';
			$titles[ 'pt_' . $post_type . '_default_rich_snippet' ] = 'off' === $snippet ? 'off' : 'article';
			$titles[ 'pt_' . $post_type . '_default_article_type' ] = 'off' === $snippet ? 'Article' : $snippet;
			$titles[ 'pt_' . $post_type . '_default_snippet_name' ] = '%seo_title%';
			$titles[ 'pt_' . $post_type . '_default_snippet_desc' ] = '%seo_description%';
		}

		// Types that only hold form submissions or building blocks are not pages anyone searches for.
		foreach ( Epic_Schema::types() as $name => $type ) {
			if ( empty( $type['public'] ) ) {
				$titles[ 'pt_' . $name . '_add_meta_box' ] = 'off';
			}
		}

		update_option( 'rank-math-options-titles', $titles );
		$log[] = 'Titles: separator "|", "Title | Site" templates, organisation details and default social image set.';

		/* ---- General ------------------------------------------------------ */
		$general = (array) get_option( 'rank-math-options-general', array() );
		$general = array_merge(
			$general,
			array(
				'breadcrumbs'                => 'on',
				'breadcrumbs_home_label'     => 'Home',
				'attachment_redirect_urls'   => 'on',
				'attachment_redirect_default' => home_url( '/' ),
				'add_img_alt'                => 'on',
				'img_alt_format'             => '%title% %count(alt)%',
				'add_img_title'              => 'off',
				'new_window_external_links'  => 'on',
				'nofollow_external_links'    => 'off',
				'404_monitor_mode'           => 'simple',
				'redirections_header_code'   => '301',
				'setup_mode'                 => 'advanced',
			)
		);
		update_option( 'rank-math-options-general', $general );
		$log[] = 'General: breadcrumb data, image alt text, 301 redirections, 404 monitor on.';

		/* ---- Sitemap ------------------------------------------------------ */
		$sitemap = (array) get_option( 'rank-math-options-sitemap', array() );
		$sitemap = array_merge(
			$sitemap,
			array(
				'items_per_page'         => 200,
				'include_images'         => 'on',
				'include_featured_image' => 'on',
				'html_sitemap'           => 'on',
				'authors_sitemap'        => 'off',
				'pt_attachment_sitemap'  => 'off',
				'tax_category_sitemap'   => 'off',
			)
		);

		foreach ( array_keys( $public ) as $post_type ) {
			$sitemap[ 'pt_' . $post_type . '_sitemap' ] = 'on';
		}
		foreach ( Epic_Schema::types() as $name => $type ) {
			if ( empty( $type['public'] ) ) {
				$sitemap[ 'pt_' . $name . '_sitemap' ] = 'off';
			}
		}

		update_option( 'rank-math-options-sitemap', $sitemap );
		$log[] = 'Sitemap: pages, publications, events, projects, careers, posts, podcast and albums included; /sitemap_index.xml.';

		/* ---- Modules ------------------------------------------------------ */
		$modules = array( 'link-counter', 'analytics', 'seo-analysis', 'sitemap', 'rich-snippet', 'local-seo', 'redirections', '404-monitor', 'image-seo', 'instant-indexing' );
		update_option( 'rank_math_modules', $modules );

		// Modules switched on after activation need their database tables created.
		if ( class_exists( '\\RankMath\\Installer' ) && method_exists( '\\RankMath\\Installer', 'create_tables' ) ) {
			\RankMath\Installer::create_tables( $modules );
		}

		// The setup wizard is replaced by this configuration. Rank Math keeps its SEO features switched
		// off until the "connect your free account" step is connected or skipped, so it is skipped
		// here; connecting later (Rank Math → Dashboard) unlocks Analytics and Content AI and changes
		// nothing else.
		update_option( 'rank_math_is_configured', true );
		update_option( 'rank_math_registration_skip', true );
		delete_transient( '_rank_math_activation_redirect' );
		$log[] = 'Modules: sitemap, schema, redirections, 404 monitor, image SEO, local SEO, link counter, analytics, SEO analysis.';
		$log[] = 'Rank Math account step skipped, so SEO is active now. Connect a free account later under Rank Math → Dashboard to add Search Console analytics.';

		if ( class_exists( '\RankMath\Helper' ) && method_exists( '\RankMath\Helper', 'clear_cache' ) ) {
			\RankMath\Helper::clear_cache( 'epic-configure' );
		}

		return $log;
	}
}
