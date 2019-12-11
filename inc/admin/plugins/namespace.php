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

/**
 * Remove ability to disable file extension check when using H5P plugin
 * Hooked into `user_has_cap`
 *
 * @param bool[] $allcaps An array of all the user's capabilities.
 * @param string[] $caps Required primitive capabilities for the requested capability.
 * @param array $args {
 *     Arguments that accompany the requested capability check.
 *
 *     @type string    $0 Requested capability.
 *     @type int       $1 Concerned user ID.
 *     @type mixed  ...$2 Optional second and further parameters, typically object ID.
 * }
 *
 * @return array
 */
function disable_h5p_security( $allcaps, $caps, $args ) {
	if ( isset( $args[0] ) && $args[0] === 'disable_h5p_security' ) {
		$allcaps['disable_h5p_security'] = false;
	}
	return $allcaps;
}

/**
 * Remove super admins ability to disable file extension check when using H5P plugin
 * Hooked into `map_meta_cap`
 *
 * @param array $caps
 * @param string $cap
 *
 * @return array
 */
function disable_h5p_security_superadmin( $caps, $cap ) {
	if ( $cap === 'disable_h5p_security' ) {
		$caps[] = 'do_not_allow';
	}
	return $caps;
}
