<?php
/**
 * Control access to plugins.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Plugins;

/**
 * Hide plugins that aren't prefixed with `pressbooks-` (only applies to books).
 * To show all plugins to all users, place the following in a plugin that loads before Pressbooks:
 * `remove_filter( 'all_plugins', '\Pressbooks\Admin\Plugins\filter_plugins', 10 );`
 *
 * @param array $plugins
 *
 * @return array $plugins
 */

function filter_plugins( $plugins ) {
	if ( ! is_super_admin() ) {
		$slugs = [
			'h5p',
			'hypothesis',
			'parsedown-party',
			'tablepress',
			'wp-quicklatex',
		];
		$approved = [];
		foreach ( $slugs as $slug ) {
			$approved[] = $slug . '/' . $slug . '.php';
		}
		foreach ( $plugins as $slug => $value ) {
			if ( false === strpos( $slug, 'pressbooks-' ) && ! in_array( $slug, $approved, true ) ) {
				unset( $plugins[ $slug ] );
			}
		}
	}

	return $plugins;
}
