<?php
/**
 * The three menus (header, footer columns, footer legal links) are ordinary
 * WordPress menus, edited under Appearance → Menus. This turns one into the tree
 * the theme renders.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Epic_Menus {

	const LOCATIONS = array(
		'primary'      => 'Header menu',
		'footer'       => 'Footer columns',
		'footer_legal' => 'Footer legal links',
	);

	public static function init(): void {
		add_action( 'after_setup_theme', array( __CLASS__, 'register' ), 5 );
	}

	public static function register(): void {
		register_nav_menus( self::LOCATIONS );
	}

	/**
	 * Top-level items of a menu location, each with its children.
	 *
	 * @return Epic_Item[]
	 */
	public static function tree( string $location ): array {
		static $cache = array();

		if ( isset( $cache[ $location ] ) ) {
			return $cache[ $location ];
		}

		$locations = get_nav_menu_locations();
		$menu_id   = $locations[ $location ] ?? 0;
		$items     = $menu_id ? wp_get_nav_menu_items( $menu_id ) : array();

		$nodes = array();
		foreach ( (array) $items as $menu_item ) {
			$nodes[ (int) $menu_item->ID ] = new Epic_Item(
				array(
					'id'        => (int) $menu_item->ID,
					'label'     => (string) $menu_item->title,
					'url'       => epic_resolved_url( (string) $menu_item->url ),
					'target'    => $menu_item->target ?: '_self',
					'parent_id' => (int) $menu_item->menu_item_parent,
					'children'  => array(),
				)
			);
		}

		$tree = array();
		foreach ( $nodes as $node ) {
			if ( $node->parent_id && isset( $nodes[ $node->parent_id ] ) ) {
				$children             = $nodes[ $node->parent_id ]->children;
				$children[]           = $node;
				$nodes[ $node->parent_id ]->children = $children;
			} else {
				$tree[] = $node;
			}
		}

		return $cache[ $location ] = $tree;
	}
}
