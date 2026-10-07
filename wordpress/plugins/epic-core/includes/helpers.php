<?php
/**
 * Template helpers. They keep the names and behaviour of the Laravel helpers the
 * views were written with (setting(), icon(), rich(), summarise(), epic_image(),
 * pic_style()...), prefixed epic_ so they never clash with WordPress.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** An editable site setting. */
function epic_setting( string $key, ?string $default = null ): ?string {
	return Epic_Settings::get( $key, $default );
}

/** Inline SVG icon markup (safe: built from a fixed set). */
function epic_icon( ?string $name, string $class = 'icon', ?string $stroke_width = null ): string {
	return Epic_Icons::render( $name, $class, $stroke_width );
}

/** Echo an icon. */
function epic_the_icon( ?string $name, string $class = 'icon', ?string $stroke_width = null ): void {
	echo Epic_Icons::render( $name, $class, $stroke_width ); // phpcs:ignore WordPress.Security.EscapeOutput -- fixed markup.
}

/** Address of a file in the active theme's assets folder. */
function epic_asset( string $path ): string {
	return get_theme_file_uri( 'assets/' . ltrim( $path, '/' ) );
}

/**
 * Picture address with a branded placeholder fallback, so pages never show broken
 * images.
 *
 * @param Epic_Pic|string|int|null $pic
 */
function epic_image( $pic, string $shape = 'card' ): string {
	if ( $pic instanceof Epic_Pic ) {
		return $pic->url;
	}

	if ( ! empty( $pic ) ) {
		$pic = Epic_Pic::make( $pic );
		if ( $pic ) {
			return $pic->url;
		}
	}

	$placeholders = array(
		'wide'     => 'images/placeholder-wide.svg',
		'card'     => 'images/placeholder-card.svg',
		'portrait' => 'images/placeholder-portrait.svg',
		'square'   => 'images/placeholder-square.svg',
		'avatar'   => 'images/placeholder-avatar.svg',
	);

	return epic_asset( $placeholders[ $shape ] ?? $placeholders['card'] );
}

/**
 * The style="" attribute for a picture: the fit, focal point and zoom an editor
 * chose for it. Empty when they chose nothing, so the frame's default applies.
 * Pass declarations the <img> already needs as $extra to keep one style attribute.
 *
 * @param Epic_Pic|null $pic
 */
function epic_pic_style( $pic, string $extra = '' ): string {
	if ( $pic instanceof Epic_Pic ) {
		return $pic->style( $extra );
	}

	$extra = trim( $extra, "; \t\n" );

	return '' === $extra ? '' : ' style="' . esc_attr( $extra ) . '"';
}

/** Whether an editor has changed how a picture is fitted. */
function epic_pic_adjusted( $pic ): bool {
	return $pic instanceof Epic_Pic && $pic->adjusted();
}

/** Laravel's Str::limit(): cut to a display width and add an ellipsis. */
function epic_limit( string $value, int $limit = 100, string $end = '...' ): string {
	if ( mb_strwidth( $value, 'UTF-8' ) <= $limit ) {
		return $value;
	}

	return rtrim( mb_strimwidth( $value, 0, $limit, '', 'UTF-8' ) ) . $end;
}

/** Plain-text excerpt of some copy, collapsed to one line and trimmed. */
function epic_summarise( ?string $text, int $length = 150 ): string {
	$plain = trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $text, false ) ) );

	return epic_limit( $plain, $length );
}

/**
 * Render editor-entered body copy held outside the post body (introductions,
 * descriptions, requirements). Rich text already holds HTML; plain text becomes
 * paragraphs. Same rule as the Laravel site.
 */
function epic_rich( ?string $content ): string {
	$content = trim( (string) $content );

	if ( '' === $content ) {
		return '';
	}

	foreach ( array( '<p', '<ul', '<ol', '<h1', '<h2', '<h3', '<h4', '<div', '<br', '<blockquote', '<table' ) as $tag ) {
		if ( false !== strpos( $content, $tag ) ) {
			return do_shortcode( $content );
		}
	}

	$paragraphs = preg_split( '/\n\s*\n/', $content ) ?: array();
	$html       = '';

	foreach ( $paragraphs as $paragraph ) {
		$html .= '<p>' . nl2br( esc_html( trim( $paragraph ) ) ) . '</p>';
	}

	return $html;
}

/** The body of a post as HTML (editor content, shortcodes, embeds). */
function epic_content( ?string $content ): string {
	$content = (string) $content;

	if ( '' === trim( $content ) ) {
		return '';
	}

	// Keep what the editor typed: no curly quotes, dashes or smilies swapped in.
	add_filter( 'run_wptexturize', '__return_false' );
	$html = apply_filters( 'the_content', $content ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals -- core filter.
	remove_filter( 'run_wptexturize', '__return_false' );

	return $html;
}

/** Copy with its markup removed, trimmed (for fields that hold plain text but were saved by the rich editor). */
function epic_plain( ?string $html ): string {
	return trim( wp_strip_all_tags( (string) $html ) );
}

/** Plain text with line breaks kept: nl2br(e($text)). */
function epic_nl2br( ?string $text ): string {
	return nl2br( esc_html( (string) $text ) );
}

/** The full address being served, for matching menu items. */
function epic_current_url(): string {
	$host = isset( $_SERVER['HTTP_HOST'] ) ? wp_unslash( $_SERVER['HTTP_HOST'] ) : wp_parse_url( home_url(), PHP_URL_HOST ); // phpcs:ignore
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/'; // phpcs:ignore

	return ( is_ssl() ? 'https://' : 'http://' ) . $host . $uri;
}

/**
 * An address as the site stores it: "/contact" and "contact" become full addresses,
 * while http(s), mailto, tel and "#" are left alone.
 */
function epic_resolved_url( ?string $url ): string {
	$url = trim( (string) $url );

	if ( '' === $url || '#' === $url ) {
		return '#';
	}

	if ( preg_match( '~^(https?:|mailto:|tel:|//)~i', $url ) ) {
		return $url;
	}

	return home_url( '/' . ltrim( $url, '/' ) );
}

/** The web address of a built-in page, found by its key (never by its slug). */
function epic_page_url( string $key ): string {
	$id = Epic_Data::page_id( $key );

	if ( $id ) {
		$link = get_permalink( $id );
		if ( $link ) {
			return $link;
		}
	}

	$page = Epic_Schema::builtin_pages()[ $key ] ?? null;

	return home_url( '/' . ( $page['path'] ?? '' ) );
}

/** Configured social profiles, ready for the header and footer. @return array<int,array{icon:string,label:string,url:string}> */
function epic_social_links(): array {
	$map = array(
		'social_linkedin'  => array( 'icon' => 'linkedin', 'label' => 'LinkedIn' ),
		'social_x'         => array( 'icon' => 'x-social', 'label' => 'X' ),
		'social_youtube'   => array( 'icon' => 'youtube', 'label' => 'YouTube' ),
		'social_facebook'  => array( 'icon' => 'facebook', 'label' => 'Facebook' ),
		'social_instagram' => array( 'icon' => 'instagram', 'label' => 'Instagram' ),
	);

	$links = array();
	foreach ( $map as $key => $meta ) {
		$url = epic_setting( $key );
		if ( null !== $url ) {
			$links[] = $meta + array( 'url' => $url );
		}
	}

	return $links;
}

/** The site logo: the editor's upload, else the built-in EPIC logo. */
function epic_site_logo( bool $light = false ): string {
	$custom = Epic_Settings::image( $light ? 'logo_light' : 'logo' );

	return $custom ?: epic_asset( $light ? 'images/logo-white.png' : 'images/logo.png' );
}

/** The :root custom properties the Appearance settings control. */
function epic_theme_css(): string {
	$menu = (string) epic_setting( 'menu_background', '#e8f1fa' );
	$menu = preg_match( '/^#[0-9a-fA-F]{6}$/', $menu ) ? strtolower( $menu ) : '#e8f1fa';

	$overlay = epic_setting( 'page_header_overlay', '50' );
	$overlay = is_numeric( $overlay ) ? max( 0, min( 95, (int) round( (float) $overlay ) ) ) : 50;
	$value   = rtrim( rtrim( number_format( $overlay / 100, 2, '.', '' ), '0' ), '.' );

	return sprintf( '--menu-bg:%s;--hero-overlay:%s;', $menu, '' === $value ? '0' : $value );
}
