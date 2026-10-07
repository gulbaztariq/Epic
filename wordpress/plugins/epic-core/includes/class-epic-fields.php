<?php
/**
 * The edit screens: one box of fields per content type (and one for pages), drawn
 * and saved from the schema. Pictures get the same fit, focal point and zoom
 * controls the Laravel dashboard had.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Fields {

	const NONCE = 'epic_nonce';

	/** @var bool Whether an icon field was drawn (so the icon set is printed once). */
	private static $needs_icons = false;

	public static function init(): void {
		add_action( 'add_meta_boxes', array( __CLASS__, 'add_boxes' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_filter( 'wp_insert_post_data', array( __CLASS__, 'filter_post_data' ), 10, 2 );
		add_action( 'save_post', array( __CLASS__, 'save' ), 10, 2 );
		add_action( 'admin_footer', array( __CLASS__, 'print_icons' ) );
	}

	/* --------------------------------------------------------------- boxes --- */

	public static function add_boxes( string $post_type, $post ): void {
		$types = Epic_Schema::types();

		if ( isset( $types[ $post_type ] ) ) {
			$type = $types[ $post_type ];
			remove_meta_box( 'postexcerpt', $post_type, 'normal' );
			remove_meta_box( 'slugdiv', $post_type, 'normal' );
			add_meta_box( 'epic_fields', $type['singular'] . ' details', array( __CLASS__, 'render_type_box' ), $post_type, 'normal', 'high' );

			return;
		}

		if ( 'page' === $post_type ) {
			add_meta_box( 'epic_page_fields', 'Page header & introduction', array( __CLASS__, 'render_page_box' ), 'page', 'normal', 'high' );
			add_meta_box( 'epic_page_sections', 'Page sections', array( __CLASS__, 'render_sections_box' ), 'page', 'normal', 'default' );
			add_meta_box( 'epic_page_key', 'EPIC page', array( __CLASS__, 'render_key_box' ), 'page', 'side', 'default' );
		}
	}

	public static function render_type_box( WP_Post $post ): void {
		$type = Epic_Schema::types()[ $post->post_type ];

		if ( ! empty( $type['form'] ) ) {
			echo '<p class="description">Saved from the website form on ' . esc_html( get_the_date( 'j F Y, H:i', $post ) ) . '.</p>';
		}

		self::render_fields( $post, $type['fields'] );
	}

	public static function render_page_box( WP_Post $post ): void {
		self::render_fields( $post, Epic_Schema::page_fields() );
		echo '<p class="description epic-seo-note">Search engine title and description are edited in the <strong>Rank Math SEO</strong> box below.</p>';
	}

	/** Sections attached to this page, with a link to add another. */
	public static function render_sections_box( WP_Post $post ): void {
		$sections = get_posts(
			array(
				'post_type'      => 'epic_section',
				'post_status'    => array( 'publish', 'draft' ),
				'post_parent'    => $post->ID,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'ID'         => 'ASC',
				),
				'posts_per_page' => -1,
			)
		);

		echo '<p class="description">Extra content blocks that appear on this page after its main content, in the order you set.</p>';

		if ( $sections ) {
			echo '<table class="widefat striped epic-sections"><thead><tr><th>Heading</th><th>Type</th><th>Order</th><th>Status</th></tr></thead><tbody>';
			foreach ( $sections as $section ) {
				$type = get_post_meta( $section->ID, 'epic_type', true );
				printf(
					'<tr><td><a href="%s">%s</a></td><td>%s</td><td>%d</td><td>%s</td></tr>',
					esc_url( get_edit_post_link( $section->ID ) ),
					esc_html( $section->post_title ?: '(no heading)' ),
					esc_html( Epic_Schema::SECTION_TYPES[ $type ] ?? $type ),
					(int) $section->menu_order,
					'publish' === $section->post_status ? 'Visible' : 'Hidden'
				);
			}
			echo '</tbody></table>';
		}

		printf(
			'<p><a class="button" href="%s">Add a section to this page</a></p>',
			esc_url( add_query_arg( array( 'post_type' => 'epic_section', 'epic_page' => $post->ID ), admin_url( 'post-new.php' ) ) )
		);
	}

	public static function render_key_box( WP_Post $post ): void {
		$key = get_post_meta( $post->ID, '_epic_key', true );
		$def = $key ? ( Epic_Schema::builtin_pages()[ $key ] ?? null ) : null;

		if ( $def ) {
			printf(
				'<p><strong>Built-in page.</strong> The website finds this page by its key, <code>%s</code>, so you can retitle it and rewrite it freely.</p><p>Its address, <code>/%s</code>, is fixed. To change what the menu says, edit the menu item under Appearance → Menus.</p>',
				esc_html( $key ),
				esc_html( $def['path'] )
			);
		} else {
			echo '<p>A custom page. It uses the standard layout: header, introduction, main content and any sections you add.</p>';
		}
	}

	/* -------------------------------------------------------------- render --- */

	/**
	 * @param array<int,array<string,mixed>> $fields
	 */
	public static function render_fields( WP_Post $post, array $fields ): void {
		wp_nonce_field( 'epic_save_' . $post->ID, self::NONCE );

		echo '<div class="epic-fields">';

		foreach ( $fields as $field ) {
			if ( 'section' === $field['type'] ) {
				echo '<h3 class="epic-section">' . esc_html( $field['label'] ) . '</h3>';
				continue;
			}

			if ( 'content' === ( $field['store'] ?? 'meta' ) ) {
				continue;
			}

			$value = self::value( $post, $field );
			$col   = (int) ( $field['col'] ?? 12 );
			$id    = 'epic-' . $field['key'];

			echo '<div class="epic-field" style="--col:' . (int) $col . '">';

			if ( 'checkbox' !== $field['type'] ) {
				echo '<label class="epic-label" for="' . esc_attr( $id ) . '">' . esc_html( $field['label'] ) . '</label>';
			}

			self::control( $post, $field, $value, $id );

			if ( ! empty( $field['hint'] ) ) {
				echo '<p class="description">' . esc_html( $field['hint'] ) . '</p>';
			}

			echo '</div>';
		}

		echo '</div>';
	}

	/** The stored value of a field, or its default on a new item. @return mixed */
	private static function value( WP_Post $post, array $field ) {
		switch ( $field['store'] ?? 'meta' ) {
			case 'excerpt':
				return $post->post_excerpt;
			case 'menu_order':
				return (int) $post->menu_order;
			case 'parent':
				if ( $post->post_parent ) {
					return (int) $post->post_parent;
				}

				return isset( $_GET['epic_page'] ) ? absint( $_GET['epic_page'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
		}

		$key = 'epic_' . $field['key'];

		if ( metadata_exists( 'post', $post->ID, $key ) ) {
			return get_post_meta( $post->ID, $key, true );
		}

		return $field['default'] ?? '';
	}

	private static function control( WP_Post $post, array $field, $value, string $id ): void {
		$name = 'epic[' . $field['key'] . ']';

		switch ( $field['type'] ) {
			case 'textarea':
				printf(
					'<textarea class="large-text" id="%s" name="%s" rows="%d"%s>%s</textarea>',
					esc_attr( $id ),
					esc_attr( $name ),
					(int) ( $field['rows'] ?? 4 ),
					self::placeholder( $field ),
					esc_textarea( (string) $value )
				);
				break;

			case 'richtext':
				wp_editor(
					(string) $value,
					'epic_rt_' . $field['key'],
					array(
						'textarea_name' => $name,
						'textarea_rows' => 10,
						'media_buttons' => true,
						'teeny'         => false,
					)
				);
				break;

			case 'select':
				$options = Epic_Schema::options( $field['options'] ?? array() );
				printf( '<select id="%s" name="%s">', esc_attr( $id ), esc_attr( $name ) );
				if ( ! empty( $field['placeholder'] ) ) {
					printf( '<option value="">%s</option>', esc_html( $field['placeholder'] ) );
				}
				foreach ( $options as $option => $label ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( $option ), selected( (string) $value, (string) $option, false ), esc_html( $label ) );
				}
				echo '</select>';
				break;

			case 'checkbox':
				printf(
					'<label class="epic-check"><input type="checkbox" id="%s" name="%s" value="1"%s> %s</label>',
					esc_attr( $id ),
					esc_attr( $name ),
					checked( in_array( $value, array( 1, '1', true ), true ), true, false ),
					esc_html( $field['label'] )
				);
				break;

			case 'number':
				printf( '<input type="number" class="small-text" id="%s" name="%s" value="%s" step="1">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
				break;

			case 'date':
				printf( '<input type="date" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( substr( (string) $value, 0, 10 ) ) );
				break;

			case 'datetime':
				$date = Epic_Item::to_date( (string) $value );
				printf( '<input type="datetime-local" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( $date ? $date->format( 'Y-m-d\TH:i' ) : '' ) );
				break;

			case 'email':
				printf( '<input type="email" class="regular-text" id="%s" name="%s" value="%s">', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );
				break;

			case 'url':
				printf( '<input type="text" class="large-text" id="%s" name="%s" value="%s"%s>', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), self::placeholder( $field ) );
				break;

			case 'icon':
				self::$needs_icons = true;
				printf( '<span class="epic-icon-pick"><span class="epic-icon-preview" data-for="%s"></span><select id="%s" name="%s" data-epic-icon>', esc_attr( $id ), esc_attr( $id ), esc_attr( $name ) );
				foreach ( Epic_Icons::names() as $icon ) {
					printf( '<option value="%s"%s>%s</option>', esc_attr( $icon ), selected( (string) $value, $icon, false ), esc_html( $icon ) );
				}
				echo '</select></span>';
				break;

			case 'page':
				self::page_picker( $id, $name, (int) $value );
				break;

			case 'image':
				self::image_control( $post, $field, $value, $id );
				break;

			case 'file':
				self::file_control( $field, $value, $id );
				break;

			case 'gallery':
				self::gallery_control( $post, $field, $value );
				break;

			default:
				printf( '<input type="text" class="large-text" id="%s" name="%s" value="%s"%s>', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ), self::placeholder( $field ) );
		}
	}

	private static function placeholder( array $field ): string {
		return ! empty( $field['placeholder'] ) ? ' placeholder="' . esc_attr( $field['placeholder'] ) . '"' : '';
	}

	/** A dropdown of every page, indented to show the hierarchy. */
	private static function page_picker( string $id, string $name, int $selected ): void {
		$pages = get_pages(
			array(
				'sort_column' => 'menu_order,post_title',
				'post_status' => array( 'publish', 'draft', 'private' ),
			)
		);

		printf( '<select id="%s" name="%s"><option value="0">— Choose a page —</option>', esc_attr( $id ), esc_attr( $name ) );
		foreach ( $pages as $page ) {
			$depth = count( get_post_ancestors( $page ) );
			printf(
				'<option value="%d"%s>%s%s</option>',
				(int) $page->ID,
				selected( $selected, (int) $page->ID, false ),
				esc_html( str_repeat( '— ', $depth ) ),
				esc_html( $page->post_title )
			);
		}
		echo '</select>';
	}

	/* ------------------------------------------------------------ pictures --- */

	/** Picture chooser plus fit & crop controls with a live preview. */
	private static function image_control( WP_Post $post, array $field, $value, string $id ): void {
		$name     = 'epic[' . $field['key'] . ']';
		$pic      = Epic_Pic::make( $value, get_post_meta( $post->ID, 'epic_' . $field['key'] . '__pic', true ) );
		$adjust   = ! isset( $field['adjust'] ) || false !== $field['adjust'];
		$aspect   = $field['aspect'] ?? '16 / 9';
		$auto_fit = $field['auto'] ?? 'contain';

		echo '<div class="epic-image" data-epic-image>';
		printf( '<input type="hidden" id="%s" name="%s" value="%s" data-epic-image-value>', esc_attr( $id ), esc_attr( $name ), esc_attr( (string) $value ) );

		echo '<div class="epic-image-bar">';
		echo '<button type="button" class="button" data-epic-image-choose>' . ( $pic ? 'Change picture' : 'Choose picture' ) . '</button> ';
		echo '<button type="button" class="button-link button-link-delete" data-epic-image-remove' . ( $pic ? '' : ' hidden' ) . '>Remove</button>';
		echo '</div>';

		if ( $adjust ) {
			$fit = $pic && $pic->fit ? $pic->fit : 'auto';
			$x   = $pic ? $pic->x : 50;
			$y   = $pic ? $pic->y : 50;
			$z   = $pic ? $pic->zoom : 100;
			$pn  = 'epic_pic[' . $field['key'] . ']';

			printf( '<div class="epic-adjust" data-epic-adjust data-auto-fit="%s"%s>', esc_attr( $auto_fit ), $pic ? '' : ' hidden' );
			echo '<div class="epic-adjust-head"><strong>Fit &amp; crop</strong> <button type="button" class="button button-small" data-epic-reset>Reset</button></div>';
			echo '<div class="epic-adjust-body">';
			printf(
				'<div class="epic-frame" style="aspect-ratio:%s" data-epic-frame title="Click to choose the focal point"><img src="%s" alt="" data-epic-preview><span class="epic-dot" data-epic-dot></span></div>',
				esc_attr( $aspect ),
				esc_url( $pic ? $pic->url : '' )
			);
			echo '<div class="epic-adjust-controls">';

			echo '<label>How the picture fits<select name="' . esc_attr( $pn ) . '[fit]" data-epic-fit>';
			foreach ( Epic_Pic::FITS as $option => $label ) {
				printf( '<option value="%s"%s>%s</option>', esc_attr( $option ), selected( $fit, $option, false ), esc_html( $label ) );
			}
			echo '</select></label>';

			printf( '<label>Focal point, left to right <output data-epic-out="x">%d%%</output><input type="range" name="%s[x]" min="0" max="100" step="1" value="%d" data-epic-x></label>', (int) $x, esc_attr( $pn ), (int) $x );
			printf( '<label>Focal point, top to bottom <output data-epic-out="y">%d%%</output><input type="range" name="%s[y]" min="0" max="100" step="1" value="%d" data-epic-y></label>', (int) $y, esc_attr( $pn ), (int) $y );
			printf( '<label>Zoom in <output data-epic-out="zoom">%d%%</output><input type="range" name="%s[zoom]" min="%d" max="%d" step="5" value="%d" data-epic-zoom></label>', (int) $z, esc_attr( $pn ), Epic_Pic::MIN_ZOOM, Epic_Pic::MAX_ZOOM, (int) $z );

			echo '<p class="description"><strong>Automatic</strong> shows the whole picture (page headers fill their width). <strong>Fill</strong> crops the edges to cover the frame; <strong>Stretch</strong> can distort the picture. Click the preview to move the focal point, the part that stays in view when it is cropped or zoomed.</p>';
			echo '</div></div></div>';
		} else {
			printf( '<div class="epic-image-plain"><img src="%s" alt="" data-epic-preview%s></div>', esc_url( $pic ? $pic->url : '' ), $pic ? '' : ' hidden' );
		}

		echo '</div>';
	}

	private static function file_control( array $field, $value, string $id ): void {
		$name = 'epic[' . $field['key'] . ']';
		$url  = $value ? ( is_numeric( $value ) ? wp_get_attachment_url( (int) $value ) : (string) $value ) : '';
		$is_id = is_numeric( $value );

		echo '<div class="epic-file" data-epic-file>';
		printf( '<input type="hidden" id="%s" name="%s" value="%s" data-epic-file-value>', esc_attr( $id ), esc_attr( $name ), esc_attr( $is_id ? (string) $value : '' ) );
		printf(
			'<p class="epic-file-current" data-epic-file-current>%s</p>',
			$url ? '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener">' . esc_html( wp_basename( $url ) ) . '</a>' : '<em>No file chosen</em>'
		);
		echo '<button type="button" class="button" data-epic-file-choose>Choose file</button> ';
		echo '<button type="button" class="button-link button-link-delete" data-epic-file-remove' . ( $url ? '' : ' hidden' ) . '>Remove</button>';
		printf(
			'<label class="epic-file-link">…or paste a link <input type="text" class="large-text" name="epic[%s__url]" value="%s" placeholder="https://"></label>',
			esc_attr( $field['key'] ),
			esc_attr( $is_id ? '' : (string) $value )
		);
		echo '</div>';
	}

	/** The photos of an album: pick several, caption, reorder, adjust each. */
	private static function gallery_control( WP_Post $post, array $field, $value ): void {
		$photos = is_array( $value ) ? $value : array();

		echo '<div class="epic-gallery" data-epic-gallery data-name="epic[' . esc_attr( $field['key'] ) . ']">';
		echo '<div class="epic-gallery-bar"><button type="button" class="button button-primary" data-epic-gallery-add>Add photos</button> <span class="description">Drag a photo to reorder. The first photo is the album cover when no cover image is set.</span></div>';
		echo '<ul class="epic-gallery-list" data-epic-gallery-list>';

		foreach ( array_values( $photos ) as $i => $entry ) {
			$pic = Epic_Pic::make( $entry['id'] ?? ( $entry['url'] ?? '' ), $entry );
			if ( ! $pic ) {
				continue;
			}
			self::gallery_row( $field['key'], (string) $i, $pic, (string) ( $entry['caption'] ?? '' ), (string) ( $entry['id'] ?? '' ), (string) ( $entry['url'] ?? '' ) );
		}

		echo '</ul></div>';

		// A template for the rows the script adds.
		echo '<script type="text/template" id="epic-gallery-row-template">';
		self::gallery_row( $field['key'], '__INDEX__', new Epic_Pic(), '', '', '' );
		echo '</script>';
	}

	private static function gallery_row( string $key, string $index, Epic_Pic $pic, string $caption, string $id, string $url ): void {
		$base = 'epic[' . $key . '][' . $index . ']';
		$fit  = $pic->fit ?: 'auto';

		echo '<li class="epic-gallery-item" data-epic-gallery-item>';
		printf( '<img src="%s" alt="" data-epic-gallery-thumb>', esc_url( $pic->url ) );
		printf( '<input type="hidden" name="%s[id]" value="%s" data-field="id"><input type="hidden" name="%s[url]" value="%s" data-field="url">', esc_attr( $base ), esc_attr( $id ), esc_attr( $base ), esc_attr( $url ) );
		printf( '<input type="text" class="widefat" name="%s[caption]" value="%s" placeholder="Caption (optional)" data-field="caption">', esc_attr( $base ), esc_attr( $caption ) );
		echo '<details><summary>Fit &amp; crop</summary><div class="epic-gallery-adjust">';
		printf( '<select name="%s[fit]" data-field="fit">', esc_attr( $base ) );
		foreach ( Epic_Pic::FITS as $option => $label ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $option ), selected( $fit, $option, false ), esc_html( $label ) );
		}
		echo '</select>';
		printf( '<label>Left–right <input type="range" min="0" max="100" name="%s[x]" value="%d" data-field="x"></label>', esc_attr( $base ), (int) $pic->x );
		printf( '<label>Top–bottom <input type="range" min="0" max="100" name="%s[y]" value="%d" data-field="y"></label>', esc_attr( $base ), (int) $pic->y );
		printf( '<label>Zoom <input type="range" min="%d" max="%d" step="5" name="%s[zoom]" value="%d" data-field="zoom"></label>', Epic_Pic::MIN_ZOOM, Epic_Pic::MAX_ZOOM, esc_attr( $base ), (int) $pic->zoom );
		echo '</div></details>';
		echo '<button type="button" class="button-link button-link-delete" data-epic-gallery-remove>Remove</button>';
		echo '</li>';
	}

	/* ---------------------------------------------------------------- save --- */

	/** Whether this request carries our fields and is allowed to save them. */
	private static function authorised( int $post_id ): bool {
		if ( empty( $_POST[ self::NONCE ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ), 'epic_save_' . $post_id ) ) {
			return false;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		return current_user_can( 'edit_post', $post_id );
	}

	/** @return array<int,array<string,mixed>> */
	private static function fields_for( string $post_type ): array {
		if ( 'page' === $post_type ) {
			return Epic_Schema::page_fields();
		}

		return Epic_Schema::types()[ $post_type ]['fields'] ?? array();
	}

	/** Excerpt, order and parent live in the post row itself. */
	public static function filter_post_data( $data, $postarr ) {
		$post_id = (int) ( $postarr['ID'] ?? 0 );

		if ( ! $post_id || ! self::authorised( $post_id ) ) {
			return $data;
		}

		$input = isset( $_POST['epic'] ) ? (array) wp_unslash( $_POST['epic'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		foreach ( self::fields_for( (string) $data['post_type'] ) as $field ) {
			$key = $field['key'] ?? '';

			switch ( $field['store'] ?? 'meta' ) {
				case 'excerpt':
					$data['post_excerpt'] = isset( $input[ $key ] ) ? sanitize_textarea_field( (string) $input[ $key ] ) : '';
					break;
				case 'menu_order':
					$data['menu_order'] = isset( $input[ $key ] ) ? (int) $input[ $key ] : 0;
					break;
				case 'parent':
					$data['post_parent'] = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : 0;
					break;
			}
		}

		return $data;
	}

	public static function save( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || ! self::authorised( $post_id ) ) {
			return;
		}

		$input = isset( $_POST['epic'] ) ? (array) wp_unslash( $_POST['epic'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$pics  = isset( $_POST['epic_pic'] ) ? (array) wp_unslash( $_POST['epic_pic'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput

		foreach ( self::fields_for( $post->post_type ) as $field ) {
			if ( 'section' === $field['type'] || 'meta' !== ( $field['store'] ?? 'meta' ) ) {
				continue;
			}

			$key = $field['key'];
			$raw = $input[ $key ] ?? null;

			if ( 'file' === $field['type'] ) {
				$link = isset( $input[ $key . '__url' ] ) ? trim( (string) $input[ $key . '__url' ] ) : '';
				$raw  = '' !== $link ? $link : $raw;
			}

			update_post_meta( $post_id, 'epic_' . $key, self::sanitize( $field, $raw ) );

			if ( 'image' === $field['type'] && ( ! isset( $field['adjust'] ) || false !== $field['adjust'] ) ) {
				update_post_meta( $post_id, 'epic_' . $key . '__pic', Epic_Pic::sanitize( $pics[ $key ] ?? array() ) );
			}
		}
	}

	/**
	 * Clean a submitted value for storage.
	 *
	 * @param mixed $raw
	 * @return mixed
	 */
	public static function sanitize( array $field, $raw ) {
		switch ( $field['type'] ) {
			case 'checkbox':
				return empty( $raw ) ? '0' : '1';

			case 'number':
				return (string) (int) $raw;

			case 'email':
				return sanitize_email( (string) $raw );

			case 'url':
				return self::sanitize_link( (string) $raw );

			case 'textarea':
				return sanitize_textarea_field( (string) $raw );

			case 'richtext':
				return wp_kses_post( (string) $raw );

			case 'code':
				return current_user_can( 'unfiltered_html' ) ? (string) $raw : wp_kses_post( (string) $raw );

			case 'date':
				$raw = (string) $raw;

				return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $raw ) ? $raw : '';

			case 'datetime':
				$raw = str_replace( 'T', ' ', (string) $raw );
				if ( preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}(:\d{2})?$/', $raw ) ) {
					return strlen( $raw ) === 16 ? $raw . ':00' : $raw;
				}

				return '';

			case 'select':
				$options = Epic_Schema::options( $field['options'] ?? array() );
				$raw     = (string) $raw;

				return isset( $options[ $raw ] ) ? $raw : ( isset( $field['default'] ) ? (string) $field['default'] : '' );

			case 'icon':
				return Epic_Icons::has( (string) $raw ) ? (string) $raw : (string) ( $field['default'] ?? 'chart' );

			case 'image':
			case 'file':
				if ( is_numeric( $raw ) && (int) $raw > 0 ) {
					return (string) (int) $raw;
				}

				return self::sanitize_link( (string) $raw );

			case 'gallery':
				return self::sanitize_gallery( $raw );

			default:
				return sanitize_text_field( (string) $raw );
		}
	}

	/** Keep relative addresses (/contact, #top) and full ones; drop anything unsafe. */
	public static function sanitize_link( string $value ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '~^(/|#|\?|mailto:|tel:)~i', $value ) ) {
			return sanitize_text_field( $value );
		}

		return esc_url_raw( $value );
	}

	/** @param mixed $raw @return array<int,array<string,mixed>> */
	private static function sanitize_gallery( $raw ): array {
		$photos = array();

		foreach ( is_array( $raw ) ? $raw : array() as $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}

			$id  = isset( $entry['id'] ) ? absint( $entry['id'] ) : 0;
			$url = isset( $entry['url'] ) ? esc_url_raw( (string) $entry['url'] ) : '';

			if ( ! $id && '' === $url ) {
				continue;
			}

			$photos[] = array(
				'id'      => $id ?: '',
				'url'     => $id ? '' : $url,
				'caption' => sanitize_text_field( (string) ( $entry['caption'] ?? '' ) ),
			) + Epic_Pic::sanitize( $entry );
		}

		return $photos;
	}

	/* -------------------------------------------------------------- assets --- */

	public static function assets( string $hook ): void {
		$screen = get_current_screen();

		if ( ! $screen ) {
			return;
		}

		$ours = isset( Epic_Schema::types()[ $screen->post_type ] ) || 'page' === $screen->post_type || false !== strpos( $hook, 'epic' );

		if ( ! $ours ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_style( 'epic-admin', EPIC_CORE_URL . 'assets/admin.css', array(), EPIC_CORE_VERSION );
		wp_enqueue_script( 'epic-admin', EPIC_CORE_URL . 'assets/admin.js', array( 'jquery', 'jquery-ui-sortable' ), EPIC_CORE_VERSION, true );
	}

	/** The icon set, so the picker can preview the chosen icon. */
	public static function print_icons(): void {
		if ( ! self::$needs_icons ) {
			return;
		}

		$icons = array();
		foreach ( Epic_Icons::names() as $name ) {
			$icons[ $name ] = Epic_Icons::render( $name, 'icon' );
		}

		echo '<script>window.epicIcons = ' . wp_json_encode( $icons ) . ';</script>';
	}
}
