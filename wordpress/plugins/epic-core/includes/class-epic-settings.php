<?php
/**
 * Site settings: one option holding every editable setting of the Laravel
 * dashboard's Settings screen, read through epic_setting().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Settings {

	const OPTION = 'epic_settings';

	/** @var array<string,string>|null */
	private static $values = null;

	/** @return array<string,string> */
	public static function all(): array {
		if ( null === self::$values ) {
			$stored       = get_option( self::OPTION, array() );
			self::$values = is_array( $stored ) ? $stored : array();
		}

		return self::$values;
	}

	/** A setting's value, or $default when it is empty (a stored "0" counts as a value). */
	public static function get( string $key, ?string $default = null ): ?string {
		$value = self::all()[ $key ] ?? null;

		return is_scalar( $value ) && '' !== trim( (string) $value ) ? (string) $value : $default;
	}

	public static function put( string $key, ?string $value ): void {
		self::put_many( array( $key => $value ) );
	}

	/** @param array<string,string|null> $values */
	public static function put_many( array $values ): void {
		$all = self::all();

		foreach ( $values as $key => $value ) {
			$all[ $key ] = null === $value ? '' : (string) $value;
		}

		update_option( self::OPTION, $all, true );
		self::$values = $all;
		self::sync_core( $values );
	}

	public static function flush(): void {
		self::$values = null;
	}

	/** The address of an image setting (a media library id or an address). */
	public static function image( string $key ): ?string {
		$value = self::get( $key );

		if ( null === $value ) {
			return null;
		}

		$url = is_numeric( $value ) ? wp_get_attachment_url( (int) $value ) : $value;

		return $url ?: null;
	}

	/** Keep WordPress' own site title and tagline in step, so SEO plugins use them. */
	private static function sync_core( array $changed ): void {
		if ( array_key_exists( 'site_name', $changed ) && '' !== (string) $changed['site_name'] ) {
			update_option( 'blogname', (string) $changed['site_name'] );
		}

		if ( array_key_exists( 'site_description', $changed ) ) {
			update_option( 'blogdescription', (string) $changed['site_description'] );
		}
	}
}
