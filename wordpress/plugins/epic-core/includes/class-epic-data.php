<?php
/**
 * Everything the templates ask the database for, in the order and with the
 * filters the Laravel controllers used. Each method returns Epic_Item objects.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Pager {

	/** @var Epic_Item[] */
	public $items = array();
	public $total = 0;
	public $per_page = 9;
	public $current = 1;
	public $last = 1;

	public function has_pages(): bool {
		return $this->last > 1;
	}

	public function count(): int {
		return count( $this->items );
	}

	/** Address of page $n of this list, keeping every other query argument. */
	public function url( int $n ): string {
		$url = remove_query_arg( 'page' );

		return $n > 1 ? add_query_arg( 'page', $n, $url ) : $url;
	}
}

class Epic_Data {

	/** @var array<string,int>|null page key => post id */
	private static $page_ids = null;

	/** @var array<string,Epic_Item> */
	private static $pages = array();

	/* --------------------------------------------------------------- pages --- */

	/** The page number asked for in ?page=N (the Laravel convention, kept so old links work). */
	public static function current_page_number(): int {
		return max( 1, (int) ( $GLOBALS['epic_pg'] ?? 1 ) );
	}

	/** @return array<string,int> */
	private static function page_ids(): array {
		if ( null !== self::$page_ids ) {
			return self::$page_ids;
		}

		global $wpdb;

		$rows = $wpdb->get_results(
			"SELECT m.meta_value AS k, p.ID AS id FROM {$wpdb->postmeta} m
			 JOIN {$wpdb->posts} p ON p.ID = m.post_id
			 WHERE m.meta_key = '_epic_key' AND m.meta_value <> '' AND p.post_type = 'page' AND p.post_status <> 'trash'
			 ORDER BY p.ID ASC"
		);

		self::$page_ids = array();
		foreach ( (array) $rows as $row ) {
			if ( ! isset( self::$page_ids[ $row->k ] ) ) {
				self::$page_ids[ $row->k ] = (int) $row->id;
			}
		}

		return self::$page_ids;
	}

	public static function flush_pages(): void {
		self::$page_ids = null;
		self::$pages    = array();
	}

	/** The id of a built-in page, or 0. */
	public static function page_id( string $key ): int {
		return self::page_ids()[ $key ] ?? 0;
	}

	public static function page_title_by_key( string $key ): string {
		$id = self::page_id( $key );

		return $id ? (string) get_the_title( $id ) : '';
	}

	/**
	 * Fetch one of the pages the website itself depends on, by its stable key. The
	 * key never changes, so an editor can retitle or re-address a page without the
	 * site losing it. Always returns an item, so a template never breaks if the
	 * page was deleted.
	 */
	public static function page( string $key ): Epic_Item {
		if ( isset( self::$pages[ $key ] ) ) {
			return self::$pages[ $key ];
		}

		$id   = self::page_id( $key );
		$post = $id ? get_post( $id ) : null;

		if ( $post instanceof WP_Post ) {
			$page = self::hydrate_page( $post );
		} else {
			$page = new Epic_Item(
				array(
					'title' => ucwords( str_replace( '-', ' ', $key ) ),
					'key'   => $key,
				)
			);
		}

		return self::$pages[ $key ] = $page;
	}

	/** A page, with its active content blocks. */
	public static function hydrate_page( WP_Post $post ): Epic_Item {
		$page           = Epic_Item::from_post( $post );
		$page->sections = self::sections( $post->ID );

		return $page;
	}

	/** The page being shown, as an item. */
	public static function current_page(): Epic_Item {
		$post = get_queried_object();

		return $post instanceof WP_Post ? self::hydrate_page( $post ) : new Epic_Item();
	}

	/** @return Epic_Item[] */
	public static function sections( int $page_id ): array {
		return self::query(
			'epic_section',
			array(
				'post_parent'    => $page_id,
				'orderby'        => array(
					'menu_order' => 'ASC',
					'ID'         => 'ASC',
				),
			)
		);
	}

	/* ------------------------------------------------------------- generic --- */

	/**
	 * @param array<string,mixed> $args WP_Query arguments.
	 * @return Epic_Item[]
	 */
	public static function query( string $type, array $args = array() ): array {
		return self::run( $type, $args )->items;
	}

	/** Run a query and return the page of results with paging details. */
	public static function paginate( string $type, array $args, int $per_page ): Epic_Pager {
		$args['posts_per_page'] = $per_page;
		$args['paged']          = self::current_page_number();

		return self::run( $type, $args );
	}

	private static function run( string $type, array $args ): Epic_Pager {
		$defaults = array(
			'post_type'              => $type,
			'post_status'            => 'publish',
			'posts_per_page'         => -1,
			'no_found_rows'          => false,
			'ignore_sticky_posts'    => true,
			'suppress_filters'       => false,
			'update_post_term_cache' => false,
		);

		$schema = Epic_Schema::types()[ $type ] ?? array();
		if ( ! isset( $args['orderby'] ) && ! empty( $schema['orderby'] ) ) {
			$args = array_merge( $args, self::order_args( $schema['orderby'] ) );
		}

		$query = new WP_Query( $args + $defaults );

		$pager           = new Epic_Pager();
		$pager->per_page = (int) $query->get( 'posts_per_page' );
		$pager->total    = (int) $query->found_posts;
		$pager->current  = max( 1, (int) $query->get( 'paged' ) );
		$pager->last     = $pager->per_page > 0 ? max( 1, (int) ceil( $pager->total / $pager->per_page ) ) : 1;

		foreach ( $query->posts as $post ) {
			$pager->items[] = Epic_Item::from_post( $post );
		}

		return $pager;
	}

	/**
	 * Turn a schema `orderby` (column => direction, where epic_* names a field) into
	 * WP_Query arguments. A single field can be ordered by value; the rest are
	 * native columns.
	 *
	 * @param array<string,string> $orderby
	 * @return array<string,mixed>
	 */
	private static function order_args( array $orderby ): array {
		$args   = array( 'orderby' => array() );
		$native = array(
			'menu_order'  => 'menu_order',
			'ID'          => 'ID',
			'title'       => 'title',
			'date'        => 'date',
			'post_parent' => 'parent',
		);

		foreach ( $orderby as $column => $direction ) {
			if ( isset( $native[ $column ] ) ) {
				$args['orderby'][ $native[ $column ] ] = $direction;
			} elseif ( ! isset( $args['meta_key'] ) ) {
				// A field: order by its value.
				$args['meta_key']          = $column;
				$args['orderby']['meta_value'] = $direction;
			}
		}

		return $args;
	}

	/** Default ordering of a type, as WP_Query arguments. */
	public static function ordering( string $type ): array {
		$schema = Epic_Schema::types()[ $type ] ?? array();

		return self::order_args( $schema['orderby'] ?? array() );
	}

	/** A meta filter for WP_Query. */
	private static function meta( string $key, $value, string $compare = '=', string $type = 'CHAR' ): array {
		return array(
			'key'     => 'epic_' . $key,
			'value'   => $value,
			'compare' => $compare,
			'type'    => $type,
		);
	}

	/* --------------------------------------------------------------- lists --- */

	/** Items of a managed content list (principles, strengths, themes...). @return Epic_Item[] */
	public static function list_items( string $group ): array {
		return self::query(
			'epic_item',
			array(
				'meta_query' => array( self::meta( 'group', $group ) ),
				'orderby'    => array(
					'menu_order' => 'ASC',
					'ID'         => 'ASC',
				),
			)
		);
	}

	/** @return Epic_Item[] */
	public static function focus_areas(): array {
		return self::query( 'epic_focus' );
	}

	/** @return Epic_Item[] */
	public static function stats(): array {
		return self::query( 'epic_stat' );
	}

	/** @return Epic_Item[] */
	public static function team( string $category ): array {
		return self::query( 'epic_member', array( 'meta_query' => array( self::meta( 'category', $category ) ) ) );
	}

	/** @return Epic_Item[] */
	public static function partners( string $type ): array {
		return self::query( 'epic_partner', array( 'meta_query' => array( self::meta( 'type', $type ) ) ) );
	}

	/** @return Epic_Item[] */
	public static function chapters(): array {
		return self::query( 'epic_chapter' );
	}

	/* -------------------------------------------------------- publications --- */

	public static function publications( string $collection, ?string $type = null, int $per_page = 9 ): Epic_Pager {
		$meta = array( self::meta( 'collection', $collection ) );
		if ( $type ) {
			$meta[] = self::meta( 'type', $type );
		}

		return self::paginate( 'epic_publication', array( 'meta_query' => $meta ), $per_page );
	}

	/** Featured publications for the home page, falling back to the newest. @return Epic_Item[] */
	public static function featured_publications( int $count = 5 ): array {
		$featured = self::query(
			'epic_publication',
			array(
				'posts_per_page' => $count,
				'meta_query'     => array( self::meta( 'is_featured', '1' ) ),
			)
		);

		return $featured ?: self::query( 'epic_publication', array( 'posts_per_page' => $count ) );
	}

	/** The publication types in use in the main collection. @return string[] */
	public static function publication_types(): array {
		global $wpdb;

		return array_values(
			array_filter(
				(array) $wpdb->get_col(
					"SELECT DISTINCT t.meta_value FROM {$wpdb->postmeta} t
					 JOIN {$wpdb->posts} p ON p.ID = t.post_id
					 JOIN {$wpdb->postmeta} c ON c.post_id = p.ID AND c.meta_key = 'epic_collection' AND c.meta_value = 'collection'
					 WHERE t.meta_key = 'epic_type' AND t.meta_value <> '' AND p.post_type = 'epic_publication' AND p.post_status = 'publish'
					 ORDER BY t.meta_value ASC"
				)
			)
		);
	}

	/** Other items from the same collection. @return Epic_Item[] */
	public static function related_publications( Epic_Item $publication, int $count = 4 ): array {
		return self::query(
			'epic_publication',
			array(
				'posts_per_page' => $count,
				'post__not_in'   => array( $publication->id ),
				'meta_query'     => array( self::meta( 'collection', (string) $publication->collection ) ),
			)
		);
	}

	/* --------------------------------------------------------------- posts --- */

	/** @param string[] $categories */
	public static function posts( array $categories, int $per_page = 9 ): Epic_Pager {
		return self::paginate( 'epic_post', array( 'meta_query' => array( self::meta( 'category', $categories, 'IN' ) ) ), $per_page );
	}

	/** @param string[] $categories @return Epic_Item[] */
	public static function related_posts( Epic_Item $post, array $categories, int $count = 3 ): array {
		return self::query(
			'epic_post',
			array(
				'posts_per_page' => $count,
				'post__not_in'   => array( $post->id ),
				'meta_query'     => array( self::meta( 'category', $categories, 'IN' ) ),
			)
		);
	}

	/* -------------------------------------------------------------- events --- */

	private static function today(): string {
		return current_datetime()->setTime( 0, 0 )->format( 'Y-m-d H:i:s' );
	}

	/** Events with no date, or from today onwards, soonest first. */
	private static function upcoming_meta(): array {
		return array(
			'relation' => 'OR',
			self::meta( 'starts_at', '' ),
			self::meta( 'starts_at', self::today(), '>=', 'DATETIME' ),
		);
	}

	private static function past_meta(): array {
		return array(
			'relation' => 'AND',
			self::meta( 'starts_at', '', '!=' ),
			self::meta( 'starts_at', self::today(), '<', 'DATETIME' ),
		);
	}

	public static function events_upcoming( int $per_page = 9 ): Epic_Pager {
		return self::paginate(
			'epic_event',
			array(
				'meta_query' => self::upcoming_meta(),
				'meta_key'   => 'epic_starts_at',
				'orderby'    => array( 'meta_value' => 'ASC' ),
			),
			$per_page
		);
	}

	public static function events_past( int $per_page = 9 ): Epic_Pager {
		return self::paginate(
			'epic_event',
			array(
				'meta_query' => self::past_meta(),
				'meta_key'   => 'epic_starts_at',
				'orderby'    => array( 'meta_value' => 'DESC' ),
			),
			$per_page
		);
	}

	public static function count_events( bool $upcoming ): int {
		$query = new WP_Query(
			array(
				'post_type'      => 'epic_event',
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_query'     => $upcoming ? self::upcoming_meta() : self::past_meta(),
			)
		);

		return (int) $query->found_posts;
	}

	/** The next events for the home page, falling back to the latest. @return Epic_Item[] */
	public static function home_events( int $count = 3 ): array {
		$events = self::query(
			'epic_event',
			array(
				'posts_per_page' => $count,
				'meta_query'     => self::upcoming_meta(),
				'meta_key'       => 'epic_starts_at',
				'orderby'        => array( 'meta_value' => 'ASC' ),
			)
		);

		return $events ?: self::query( 'epic_event', array( 'posts_per_page' => $count ) );
	}

	/** @return Epic_Item[] */
	public static function other_events( Epic_Item $event, int $count = 3 ): array {
		return self::query(
			'epic_event',
			array(
				'posts_per_page' => $count,
				'post__not_in'   => array( $event->id ),
			)
		);
	}

	/* ------------------------------------------------------------ projects --- */

	/** @return Epic_Item[] */
	public static function other_projects( Epic_Item $project, int $count = 3 ): array {
		return self::query(
			'epic_project',
			array(
				'posts_per_page' => $count,
				'post__not_in'   => array( $project->id ),
			)
		);
	}

	/* ------------------------------------------------------------- careers --- */

	/** @return Epic_Item[] */
	public static function careers( bool $open, int $limit = -1, ?Epic_Item $except = null ): array {
		$args = array(
			'posts_per_page' => $limit,
			'meta_query'     => array( self::meta( 'is_open', $open ? '1' : '0' ) ),
		);

		if ( $except ) {
			$args['post__not_in'] = array( $except->id );
		}

		if ( ! $open ) {
			$args['orderby'] = array( 'ID' => 'DESC' );
		}

		return self::query( 'epic_career', $args );
	}

	/* --------------------------------------------------------------- media --- */

	/** @return Epic_Item[] */
	public static function other_podcasts( Epic_Item $episode, int $count = 4 ): array {
		return self::query(
			'epic_podcast',
			array(
				'posts_per_page' => $count,
				'post__not_in'   => array( $episode->id ),
			)
		);
	}

	public static function featured_video(): ?Epic_Item {
		$videos = self::query(
			'epic_video',
			array(
				'posts_per_page' => 1,
				'meta_query'     => array( self::meta( 'is_featured', '1' ) ),
			)
		);

		return $videos[0] ?? null;
	}

	/** @return Epic_Item[] */
	public static function other_albums( Epic_Item $album, int $count = 3 ): array {
		return self::query(
			'epic_album',
			array(
				'posts_per_page' => $count,
				'post__not_in'   => array( $album->id ),
			)
		);
	}

	/* -------------------------------------------------------------- search --- */

	/**
	 * Publications, posts, events and projects that contain the phrase, as the
	 * Laravel search did (the whole phrase, up to 12 of each).
	 *
	 * @return array<int,array{title:string,type:string,summary:string,url:string,date:?DateTimeInterface}>
	 */
	public static function search( string $term ): array {
		if ( mb_strlen( $term ) < 2 ) {
			return array();
		}

		$results = array();
		$types   = array(
			'epic_publication' => static fn( Epic_Item $i ) => array( (string) $i->type, $i->excerpt ?: $i->body, $i->published_at ),
			'epic_post'        => static fn( Epic_Item $i ) => array( (string) $i->category_label, $i->summary, $i->published_at ),
			'epic_event'       => static fn( Epic_Item $i ) => array( 'Event', $i->excerpt ?: $i->body, $i->starts_at ),
			'epic_project'     => static fn( Epic_Item $i ) => array( 'Project', $i->excerpt ?: $i->body, $i->started_at ),
		);

		foreach ( $types as $type => $describe ) {
			$items = self::query(
				$type,
				array(
					's'              => $term,
					'sentence'       => true,
					'posts_per_page' => 12,
				)
			);

			foreach ( $items as $item ) {
				list( $label, $text, $date ) = $describe( $item );

				$results[] = array(
					'title'   => $item->title,
					'type'    => $label,
					'summary' => epic_summarise( (string) $text, 170 ),
					'url'     => $item->url,
					'date'    => $date,
				);
			}
		}

		return $results;
	}
}
