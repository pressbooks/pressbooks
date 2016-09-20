<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks;



class Pressbooks {

	/**
	 * Constructor.
	 */
	function __construct() {

		/**
		 * Memcached Object Cache v2.0.2 doesn't like when we loop on switch_to_blog()
		 * We "fix" this by storing our cached items in global group 'pb'
		 */
		wp_cache_add_global_groups( array( 'pb' ) );

		$this->registerThemeDirectories();

		do_action( 'pressbooks_loaded' );
	}


	/**
	 * Register theme directories, set a filter that hides themes under certain conditions
	 */
	function registerThemeDirectories() {

		// No trailing slash, otherwise we get a double slash bug
		// @see \Pressbooks\Metadata::fixDoubleSlashBug
		register_theme_directory( PB_PLUGIN_DIR . 'themes-root' );
		register_theme_directory( PB_PLUGIN_DIR . 'themes-book' );

		do_action('pressbooks_register_theme_directory');

		// Check for local themes-root directory
		if ( realpath ( WP_CONTENT_DIR . '/themes-root' ) ) :
			register_theme_directory( WP_CONTENT_DIR . '/themes-root' );
		endif;

		if ( is_admin() ) {
			if ( Book::isBook() ) {
				add_filter( 'allowed_themes', array( $this, 'allowedBookThemes' ) );
			} elseif ( ! is_network_admin() ) {
				add_filter( 'allowed_themes', array( $this, 'allowedRootThemes' ) );
			}
		}
	}


	/**
	 * Used by add_filter( 'allowed_themes' ) Will hide any theme not in ./themes-book/* with exceptions
	 * for the PB_BOOK_THEME constant, and $GLOBALS['PB_SECRET_SAUCE']['BOOK_THEMES'][]
	 *
	 * @param array $themes
	 *
	 * @return array
	 */
	function allowedBookThemes( $themes ) {

		$exceptions = array();

		if ( defined( 'PB_BOOK_THEME' ) ) {
			$exceptions[] = PB_BOOK_THEME;
		}

		if ( isset( $GLOBALS['PB_SECRET_SAUCE']['BOOK_THEMES'] ) ) {
			if ( is_array( $GLOBALS['PB_SECRET_SAUCE']['BOOK_THEMES'] ) ) {
				$exceptions = array_merge( $exceptions, $GLOBALS['PB_SECRET_SAUCE']['BOOK_THEMES'] );
			} else {
				$exceptions[] = $GLOBALS['PB_SECRET_SAUCE']['BOOK_THEMES'];
			}
		}

		$compare = search_theme_directories();
		foreach ( $compare as $key => $val ) {
			if ( ! in_array( $key, $exceptions ) && untrailingslashit( $val['theme_root'] ) != PB_PLUGIN_DIR . 'themes-book' ) {
				unset ( $themes[$key] );
			}
		}

		return $themes;
	}


	/**
	 * Used by add_filter( 'allowed_themes' ) Will hide any theme not in ./themes-root/* with exceptions
	 * for 'pressbooks-root', the PB_ROOT_THEME constant, and $GLOBALS['PB_SECRET_SAUCE']['ROOT_THEMES'][]
	 *
	 * @param array $themes
	 *
	 * @return array
	 */
	function allowedRootThemes( $themes ) {

		$exceptions = array( 'pressbooks-root' );

		if ( defined( 'PB_ROOT_THEME' ) ) {
			$exceptions[] = PB_ROOT_THEME;
		}

		if ( isset( $GLOBALS['PB_SECRET_SAUCE']['ROOT_THEMES'] ) ) {
			if ( is_array( $GLOBALS['PB_SECRET_SAUCE']['ROOT_THEMES'] ) ) {
				$exceptions = array_merge( $exceptions, $GLOBALS['PB_SECRET_SAUCE']['ROOT_THEMES'] );
			} else {
				$exceptions[] = $GLOBALS['PB_SECRET_SAUCE']['ROOT_THEMES'];
			}
		}

		$compare = search_theme_directories();
		foreach ( $compare as $key => $val ) {
			if ( ! in_array( $key, $exceptions ) && untrailingslashit( $val['theme_root'] ) != PB_PLUGIN_DIR . 'themes-root' ) {
				unset ( $themes[$key] );
			}
		}

		return $themes;
	}

}
