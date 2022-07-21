<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped

namespace Pressbooks\Admin;

use function Pressbooks\Utility\str_ends_with;

class SiteMap {

	/**
	 * HTML List we build when hooked into `wp_before_admin_bar_render`
	 *
	 * @var string
	 */
	private string $adminBarForSiteMap = '';

	/**
	 * @var SiteMap
	 */
	private static ?\Pressbooks\Admin\SiteMap $instance = null;

	/**
	 * @return SiteMap
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	public static function hooks( SiteMap $obj ) {
		add_action( 'admin_menu', [ $obj, 'addMenu' ], 30 );
		add_action( 'wp_before_admin_bar_render', [ $obj, 'adminBar' ] );
	}

	/**
	 *
	 */
	public function __construct() {
	}

	/**
	 *
	 */
	public function addMenu() {
		add_submenu_page(
			'options.php',
			__( 'Sitemap', 'pressbooks' ),
			__( 'Sitemap', 'pressbooks' ),
			'edit_posts',
			'pressbooks_sitemap',
			[ $this, 'renderPage' ]
		);
	}

	/**
	 *
	 */
	public function renderPage() {
		ob_start();
		$this->printMenuTree();
		$menu_for_sitemap = ob_get_clean();

		$blade = \Pressbooks\Container::get( 'Blade' );
		echo $blade->render(
			'admin.sitemap',
			[
				'menu' => $menu_for_sitemap,
				'admin_bar' => $this->adminBarForSiteMap,
			]
		);
	}

	/**
	 * Create links from Admin Bar
	 *
	 * @see \WP_Admin_Bar
	 */
	public function adminBar() {
		global $wp_admin_bar;

		if ( ! $wp_admin_bar instanceof \WP_Admin_Bar ) {
			return;
		}
		$nodes = $wp_admin_bar->get_nodes();
		if ( ! is_iterable( $nodes ) ) {
			return;
		}

		// First initialize the array of child/parent pairs:
		$relationships = [];
		foreach ( $nodes as $id => $node ) {
			$parent = ! empty( $node->parent ) ? $node->parent : 'root';
			// Child : Parent
			$relationships[ $id ] = $parent;
		}
		$relationships['root'] = null;

		$tree = $this->parseAdminBarTree( $relationships );

		ob_start();
		$this->printAdminBarTree( $tree[0]['children'], $nodes );
		$html = ob_get_clean();

		$this->adminBarForSiteMap = $html;
	}

	/**
	 * Parse Admin Bar tree (recursive)
	 * Children nodes without a parent node will be dropped
	 *
	 * @see \WP_Admin_Bar
	 *
	 * @param array $tree
	 * @param mixed $root
	 *
	 * @return array|null
	 */
	private function parseAdminBarTree( $tree, $root = null ) {
		$return = [];
		foreach ( $tree as $child => $parent ) {
			if ( $parent === $root ) {
				//  Remove item from tree (we don't need to traverse this again)
				unset( $tree[ $child ] );
				// Append the child into result array and parse its children
				$return[] = [
					'name' => $child,
					'children' => $this->parseAdminBarTree( $tree, $child ),
				];
			}
		}
		return empty( $return ) ? null : $return;
	}

	/**
	 * Print Admin Bar tree (recursive)
	 *
	 * @param array $tree
	 * @param \stdClass[] $nodes
	 * @param bool $ul
	 *
	 * @see \WP_Admin_Bar
	 */
	private function printAdminBarTree( $tree, $nodes, $ul = true ) {
		if ( is_countable( $tree ) && count( $tree ) > 0 ) {
			if ( $ul ) {
				echo '<ul class="ul-disc">';
			}
			foreach ( $tree as $node ) {
				$title = trim( wp_strip_all_tags( html_entity_decode( $nodes[ $node['name'] ]->title ) ) );
				$href = $nodes[ $node['name'] ]->href;
				if ( ! empty( $title ) && $href !== '#' ) {
					echo "<li><a href='{$href}'>{$title}</a>";
					$this->printAdminBarTree( $node['children'], $nodes, true );
					echo '</li>';
				} else {
					$this->printAdminBarTree( $node['children'], $nodes, false );
				}
			}
			if ( $ul ) {
				echo '</ul>';
			}
		}
	}

	/**
	 * Print Menu & Submenu Tree
	 */
	private function printMenuTree() {
		/*
		* The elements in the array are :
		*   0: Menu item name
		*   1: Minimum level or capability required.
		*   2: The URL of the item's file
		*   3: Class
		*   4: ID
		*   5: Icon for top level menu
		*/
		global $menu, $submenu;
		if ( ! is_iterable( $menu ) ) {
			return;
		}

		echo '<ul class="ul-disc">';
		foreach ( $menu as $arr1 ) {
			if ( empty( $arr1[0] ) ) {
				continue;
			}
			$menu_hook = get_plugin_page_hook( $arr1[2], null );
			if ( $menu_hook ) {
				$href = 'admin.php?page=' . $arr1[2];
			} else {
				$href = $arr1[2];
			}
			echo "<li><a href='{$href}'>{$arr1[0]}</a>";
			foreach ( $submenu as $k => $arr2 ) {
				if ( $k === $arr1[2] ) {
					echo '<ul class="ul-disc">';
					foreach ( $arr2 as $arr3 ) {
						$menu_hook = get_plugin_page_hook( $arr3[2], $k );
						if ( $menu_hook ) {
							if ( str_ends_with( $k, '.php' ) ) {
								$href = "{$k}?page={$arr3[2]}";
							} else {
								$href = "admin.php?page={$arr3[2]}";
							}
						} else {
							$href = $arr3[2];
						}
						echo "<li><a href='{$href}'>{$arr3[0]}</a>";
					}
					echo '</ul>';
					continue;
				}
			}
		}
		echo '</ul>';
	}

}
