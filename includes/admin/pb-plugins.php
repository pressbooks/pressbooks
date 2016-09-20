<?php
/**
 * Control access to plugins.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Admin\Plugins;

/**
 * Hide plugins that aren't prefixed with `pressbooks-` (only applies to books).
 * To show all plugins to all users, place the following in a plugin that loads before Pressbooks:
 * `remove_filter( 'all_plugins', '\Pressbooks\Admin\Plugins\filter_plugins', 10 );`
 *
 * @param array $plugins
 * @return array $plugins
 */

function filter_plugins( $plugins ) {
	if ( ! is_super_admin() ) {
		$slugs = [ 'hypothesis' ];
		$approved = [];
		foreach ( $slugs as $slug ) {
			$approved[] = $slug . '/' . $slug . '.php';
		}
		foreach ( $plugins as $slug => $value ) {
			if ( false === strpos( $slug, 'pressbooks-' ) && ! in_array( $slug, $approved ) )
				unset( $plugins[$slug] );
		}
	}

	return $plugins;
}

/**
 * Add a 'Pressbooks' tab to the plugin installer.
 *
 * @param array $tabs
 * @return array $tabs
 */

function filter_install_plugins_tabs( $tabs ) {
	$tabs = array_merge( array( 'pressbooks' => __( 'Pressbooks', 'pressbooks' ) ), $tabs );
	return $tabs;
}

/**
 * Set Plugin Installer API query arguments for the 'Pressbooks' tab of the plugin installer.
 *
 * @param array $args
 * @return array $args
 */

function install_plugins_table_api_args_pressbooks( $args ) {
	$args = array(
		'page' => 1,
		'per_page' => 30,
		'fields' => array(
			'last_updated' => true,
			'icons' => true,
			'active_installs' => true
		),
		'locale' => get_locale(),
		'tag' => 'pressbooks'
	);

	return $args;
}

/**
 * Output header text and display table for the 'Pressbooks' tab of the plugin installer.
 */
function install_plugins() {
	global $wp_list_table;

	echo '<p>' . __( 'These plugins extend the functionality of Pressbooks.', 'pressbooks' ) . '</p>';
	$wp_list_table->display();
}
