<?php
/**
 * One piece of content, shaped like the Laravel models the templates were written
 * against: `$event->title`, `$event->starts_at->format('d M Y')`, `$post->image`.
 *
 * Reading a property that does not exist gives null, as an Eloquent model does, so
 * a template never has to guard against a field an older record lacks.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Item {

	/** @var int */
	public $id = 0;

	/** @var string */
	public $title = '';

	/** @var string */
	public $slug = '';

	/** @var string Raw body as stored (post content). */
	public $body = '';

	/** @var string */
	public $excerpt = '';

	/** @var string Public address. */
	public $url = '';

	/** @var int */
	public $sort = 0;

	/** @var bool False when the item is saved as a draft (hidden). */
	public $is_published = true;

	/** @var int */
	public $parent_id = 0;

	/** @var string The WordPress post type, e.g. epic_event. (A field called "type" is a normal attribute.) */
	public $post_type = '';

	/** @var Epic_Item[] Active content blocks of a page, in order. */
	public $sections = array();

	/** @var array<string,mixed> */
	private $attrs = array();

	public function __construct( array $attrs = array() ) {
		foreach ( $attrs as $key => $value ) {
			$this->__set( $key, $value );
		}
	}

	public function __get( $key ) {
		return $this->attrs[ $key ] ?? null;
	}

	public function __set( $key, $value ) {
		if ( property_exists( $this, $key ) && 'attrs' !== $key ) {
			$this->$key = $value;

			return;
		}

		$this->attrs[ $key ] = $value;
	}

	public function __isset( $key ) {
		return isset( $this->attrs[ $key ] );
	}

	/** The title used in page headings: the hero heading, else the page title. */
	public function hero_heading(): string {
		return (string) ( $this->hero_title ?: $this->title );
	}

	/**
	 * One of a page's content blocks by type, always an item so a template can read
	 * ->heading or ->link_text without checking.
	 */
	public function section( string $type ): Epic_Item {
		foreach ( $this->sections as $section ) {
			if ( $section->type === $type ) {
				return $section;
			}
		}

		return new Epic_Item( array( 'type' => $type ) );
	}

	/* ------------------------------------------------------------ hydrate --- */

	/** Build an item from a post, reading every field its type declares. */
	public static function from_post( WP_Post $post ): Epic_Item {
		$types  = Epic_Schema::types();
		$is_page = 'page' === $post->post_type;
		$fields = $is_page ? Epic_Schema::page_fields() : ( $types[ $post->post_type ]['fields'] ?? array() );

		$item = new self(
			array(
				'id'            => (int) $post->ID,
				'title'         => (string) $post->post_title,
				'slug'          => (string) $post->post_name,
				'body'          => (string) $post->post_content,
				'excerpt'       => (string) $post->post_excerpt,
				'sort'          => (int) $post->menu_order,
				'is_published'  => 'publish' === $post->post_status,
				'parent_id'     => (int) $post->post_parent,
				'post_type'     => $post->post_type,
				'url'           => '' === $post->post_name && 'publish' !== $post->post_status ? '' : (string) get_permalink( $post ),
				'post_date'     => $post->post_date,
				'updated_at'    => $post->post_modified,
			)
		);

		foreach ( $fields as $field ) {
			if ( 'section' === $field['type'] ) {
				continue;
			}

			$item->{$field['key']} = self::read_field( $post, $field );
		}

		// Types whose Laravel column was called something other than "title" / "body".
		if ( $is_page ) {
			$item->key = (string) get_post_meta( $post->ID, '_epic_key', true );
		}

		self::derive( $item, $post );

		return $item;
	}

	/** The value of one field, typed the way templates expect. */
	private static function read_field( WP_Post $post, array $field ) {
		$store = $field['store'] ?? 'meta';

		switch ( $store ) {
			case 'excerpt':
				return (string) $post->post_excerpt;
			case 'content':
				return (string) $post->post_content;
			case 'menu_order':
				return (int) $post->menu_order;
			case 'parent':
				return (int) $post->post_parent;
		}

		$meta_key = 'epic_' . $field['key'];
		$exists   = metadata_exists( 'post', $post->ID, $meta_key );
		$raw      = $exists ? get_post_meta( $post->ID, $meta_key, true ) : null;

		switch ( $field['type'] ) {
			case 'checkbox':
				if ( ! $exists ) {
					return ! empty( $field['default'] );
				}

				return in_array( $raw, array( 1, '1', true ), true );

			case 'number':
				return (int) $raw;

			case 'date':
			case 'datetime':
				return self::to_date( $raw );

			case 'image':
				return Epic_Pic::make( $raw, get_post_meta( $post->ID, $meta_key . '__pic', true ) );

			case 'file':
				if ( empty( $raw ) ) {
					return null;
				}

				return is_numeric( $raw ) ? ( wp_get_attachment_url( (int) $raw ) ?: null ) : (string) $raw;

			case 'gallery':
				return self::to_gallery( $raw );

			default:
				if ( null === $raw ) {
					return isset( $field['default'] ) ? $field['default'] : null;
				}

				return is_scalar( $raw ) ? (string) $raw : '';
		}
	}

	/** A stored Y-m-d or Y-m-d H:i:s as a date in the site's timezone. */
	public static function to_date( $raw ): ?DateTimeImmutable {
		if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
			return null;
		}

		try {
			return new DateTimeImmutable( $raw, wp_timezone() );
		} catch ( Exception $e ) {
			return null;
		}
	}

	/**
	 * @param mixed $raw The stored list of photos.
	 * @return Epic_Pic[]
	 */
	private static function to_gallery( $raw ): array {
		$photos = array();

		foreach ( is_array( $raw ) ? $raw : array() as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$pic = Epic_Pic::make( $entry['id'] ?? ( $entry['url'] ?? '' ), $entry );

			if ( $pic ) {
				$pic->caption = (string) ( $entry['caption'] ?? '' );
				$photos[]     = $pic;
			}
		}

		return $photos;
	}

	/** The computed values the Laravel models exposed as accessors. */
	private static function derive( Epic_Item $item, WP_Post $post ): void {
		switch ( $post->post_type ) {
			case 'epic_event':
				$now                = current_datetime()->setTime( 0, 0 );
				$item->day          = $item->starts_at ? $item->starts_at->format( 'd' ) : '--';
				$item->month_year   = $item->starts_at ? mb_strtoupper( $item->starts_at->format( 'M Y' ) ) : '';
				$item->is_upcoming  = ! $item->starts_at || $item->starts_at >= $now;
				break;

			case 'epic_member':
				$parts          = array_slice( array_filter( explode( ' ', trim( $item->title ) ) ), 0, 2 );
				$item->initials = implode( '', array_map( static fn( $p ) => mb_strtoupper( mb_substr( $p, 0, 1 ) ), $parts ) );
				break;

			case 'epic_post':
				$item->summary        = '' !== trim( (string) $item->excerpt ) ? $item->excerpt : epic_limit( wp_strip_all_tags( (string) $item->body ), 160 );
				$item->category_label = Epic_Schema::POST_CATEGORIES[ $item->category ] ?? ucfirst( (string) $item->category );
				break;

			case 'epic_publication':
				$item->download_url = $item->file_path ?: ( $item->external_url ?: null );
				break;

			case 'epic_video':
				$id                 = self::youtube_id( (string) $item->youtube_url );
				$item->youtube_id   = $id;
				$item->embed_url    = $id ? 'https://www.youtube.com/embed/' . $id : null;
				$item->watch_url    = $id ? 'https://www.youtube.com/watch?v=' . $id : $item->youtube_url;
				$item->poster       = $item->thumbnail ? $item->thumbnail->url : ( $id ? 'https://i.ytimg.com/vi/' . $id . '/hqdefault.jpg' : null );
				break;

			case 'epic_album':
				$images       = $item->images ?: array();
				$item->cover  = $item->cover_image ?: ( $images[0] ?? null );
				$item->description = $item->excerpt;
				break;
		}
	}

	/** Pull the video id out of any common YouTube address shape. */
	public static function youtube_id( string $url ): ?string {
		if ( '' === $url ) {
			return null;
		}

		if ( preg_match( '~(?:youtu\.be/|youtube\.com/(?:watch\?(?:.*&)?v=|embed/|shorts/|live/))([A-Za-z0-9_-]{6,})~', $url, $m ) ) {
			return $m[1];
		}

		return preg_match( '~^[A-Za-z0-9_-]{6,}$~', $url ) ? $url : null;
	}
}
