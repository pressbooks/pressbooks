<?php
/**
 * Shortcuts for template designers who don't use real namespaces, and other helper functions.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

if ( ! function_exists( 'app' ) ) {
	/**
	 * Fake Laravel app() so we can use Blade @inject directive
	 *
	 * @see https://github.com/laravel/framework/blob/5.4/src/Illuminate/Foundation/helpers.php#L96
	 *
	 * @param  string $abstract
	 * @param  array $parameters
	 *
	 * @return mixed
	 */
	function app( $abstract = null, array $parameters = [] ) {
		if ( is_null( $abstract ) ) {
			return \Pressbooks\Container::getInstance();
		}
		return empty( $parameters )
			? \Pressbooks\Container::getInstance()->make( $abstract )
			: \Pressbooks\Container::getInstance()->makeWith( $abstract, $parameters );
	}
}
