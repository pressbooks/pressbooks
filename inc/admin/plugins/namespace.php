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
 * @return array
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
		foreach ( $plugins as $slug => $value ) {
			if ( strpos( $slug, 'pressbooks-' ) !== false || in_array( explode( '/', $slug )[0], $slugs, true ) ) {
				$approved[ $slug ] = $value;
			}
		}
		return $approved;
	}

	return $plugins;
}

/**
 * @param array $plugins
 *
 * @return array
 */
function hide_gutenberg( $plugins ) {
	unset( $plugins['gutenberg/gutenberg.php'] );
	return $plugins;
}
