<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Sitemap;

/**
 * Add the diagnostics menu (with parent page set to null)
 */

function add_menu() {
	add_submenu_page(
		'options.php',
		__( 'Sitemap', 'pressbooks' ),
		__( 'Sitemap', 'pressbooks' ),
		'edit_posts',
		'pressbooks_sitemap',
		__NAMESPACE__ . '\render_page'
	);
}

/**
 * Render the diagnostics page (adapted from https://github.com/WordImpress/Give/blob/master/includes/admin/system-info.php)
 */
function render_page() {
	global $menu, $submenu;

	$coolname = [];

	foreach ($menu as $arr1) {
		if (empty($arr1[0])) {
			continue;
		}
		echo "+ {$arr1[0]} <br>";
		foreach ($submenu as $key2 => $arr2) {
			if ($key2 === $arr1[2] ) {
				foreach ($arr2 as $arr3) {
					echo "++ {$arr3[0]} <br>";
				}
				continue;
			}
		}
	}

	$blade = \Pressbooks\Container::get( 'Blade' );
	echo $blade->render(
		'admin.sitemap',
		[
			'output' => $menuItems
		]
	);
}

