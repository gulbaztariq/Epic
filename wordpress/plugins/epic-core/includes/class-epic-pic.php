<?php
/**
 * One editable picture: where it is, and how the editor chose to fit it in its frame.
 *
 * An editor picks, per picture, a fit (whole picture, fill and crop, stretch...), a
 * focal point and a zoom. They reach the page as CSS custom properties set inline
 * on the <img>, which the stylesheet applies to every frame in one place and which
 * combine with hover effects instead of fighting them.
 *
 * "Automatic" sets nothing at all, so each frame's own default applies: the whole
 * picture for content, and filling the frame for full-bleed page headers.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Pic {

	/** Value => label, in the order the edit screen offers them. */
	const FITS = array(
		'auto'       => 'Automatic',
		'contain'    => 'Whole picture, fit inside',
		'cover'      => 'Fill the frame, crop edges',
		'fill'       => 'Stretch to fill',
		'scale-down' => 'Whole picture, never enlarge',
		'none'       => 'Original size',
	);

	const MIN_ZOOM = 100;
	const MAX_ZOOM = 400;

	/** @var string Public address of the picture. */
	public $url = '';

	/** @var int Media library id, or 0 for an external address. */
	public $id = 0;

	/** @var string|null A key of self::FITS, or null for automatic. */
	public $fit = null;

	/** @var int Focal point, percent from the left. */
	public $x = 50;

	/** @var int Focal point, percent from the top. */
	public $y = 50;

	/** @var int Zoom, percent. */
	public $zoom = 100;

	/** @var string Optional caption (gallery photos). */
	public $caption = '';

	/**
	 * @param int|string|null $value    A media library id, or an address.
	 * @param array|null      $settings fit, x, y, zoom as saved by the edit screen.
	 */
	public static function make( $value, $settings = null ): ?Epic_Pic {
		$value = is_string( $value ) ? trim( $value ) : $value;

		if ( empty( $value ) ) {
			return null;
		}

		$pic = new self();

		if ( is_numeric( $value ) ) {
			$pic->id  = (int) $value;
			$pic->url = (string) wp_get_attachment_url( $pic->id );
		} else {
			$pic->url = (string) $value;
		}

		if ( '' === $pic->url ) {
			return null;
		}

		$pic->apply( is_array( $settings ) ? $settings : array() );

		return $pic;
	}

	/** Take editor choices, clamping anything out of range and ignoring anything unknown. */
	public function apply( array $settings ): void {
		$fit       = $settings['fit'] ?? null;
		$this->fit = is_string( $fit ) && 'auto' !== $fit && isset( self::FITS[ $fit ] ) ? $fit : null;
		$this->x   = self::clamp( $settings['x'] ?? 50, 0, 100, 50 );
		$this->y   = self::clamp( $settings['y'] ?? 50, 0, 100, 50 );
		$this->zoom = self::clamp( $settings['zoom'] ?? 100, self::MIN_ZOOM, self::MAX_ZOOM, 100 );
	}

	/** Normalise raw form input into the shape that is stored. */
	public static function sanitize( $input ): array {
		$pic = new self();
		$pic->apply( is_array( $input ) ? $input : array() );

		return array(
			'fit'  => $pic->fit ?? 'auto',
			'x'    => $pic->x,
			'y'    => $pic->y,
			'zoom' => $pic->zoom,
		);
	}

	/** True when the picture has any choice other than the defaults. */
	public function adjusted(): bool {
		return null !== $this->fit || 50 !== $this->x || 50 !== $this->y || 100 !== $this->zoom;
	}

	/** The CSS declarations, e.g. "--fit:cover;--pos:30% 60%;--zoom:1.4". Empty when nothing changed. */
	public function declarations(): string {
		$parts = array();

		if ( $this->fit ) {
			$parts[] = '--fit:' . $this->fit;
		}

		if ( 50 !== $this->x || 50 !== $this->y ) {
			$parts[] = '--pos:' . $this->x . '% ' . $this->y . '%';
		}

		if ( 100 !== $this->zoom ) {
			$parts[] = '--zoom:' . rtrim( rtrim( number_format( $this->zoom / 100, 2, '.', '' ), '0' ), '.' );
		}

		return implode( ';', $parts );
	}

	/**
	 * A complete style="" attribute (or nothing) for an <img>, merged with any
	 * declarations the template already wanted on it.
	 */
	public function style( string $extra = '' ): string {
		$css = trim( implode( ';', array_filter( array( trim( $extra, "; \t\n" ), $this->declarations() ) ) ) );

		return '' === $css ? '' : ' style="' . esc_attr( $css ) . '"';
	}

	public function __toString(): string {
		return $this->url;
	}

	private static function clamp( $value, int $min, int $max, int $default ): int {
		return is_numeric( $value ) ? max( $min, min( $max, (int) round( (float) $value ) ) ) : $default;
	}
}
