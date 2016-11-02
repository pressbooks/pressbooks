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

		do_action( 'pressbooks_register_theme_directory' );

		// Check for local themes-root directory
		if ( realpath( WP_CONTENT_DIR . '/themes-root' ) ) :
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
		if ( isset( $GLOBALS['PB_SECRET_SAUCE']['BOOK_THEMES'] ) ) {
			if ( is_array( $GLOBALS['PB_SECRET_SAUCE']['BOOK_THEMES'] ) ) {
				$whitelist = $GLOBALS['PB_SECRET_SAUCE']['BOOK_THEMES'];
			} else {
				$whitelist = array( $GLOBALS['PB_SECRET_SAUCE']['BOOK_THEMES'] );
			}
		} else {
			$whitelist = array();
		}

		$blacklist = apply_filters( 'pressbooks_disallowed_book_themes', array() );

		$compare = search_theme_directories();

		foreach ( $compare as $key => $val ) {
			if ( ! empty( $whitelist ) ) {
				// Hide themes which are not whitelisted
				if ( untrailingslashit( $val['theme_root'] ) != PB_PLUGIN_DIR . 'themes-book' && ( ! in_array( $key, $whitelist ) && PB_BOOK_THEME != $key ) ) {
					unset( $themes[ $key ] );
				}
			} elseif ( ! empty( $blacklist ) ) {
				$theme = wp_get_theme( str_replace( 'style.css', '', $val['theme_file'] ), $val['theme_root'] );
				// Hide themes which are not book themes and book themes which are blacklisted
				if ( ( 'pressbooks-book/style.css' != $val['theme_file'] && 'pressbooks-book' != $theme->get( 'Template' ) ) || in_array( $key, $blacklist ) ) {
					unset( $themes[ $key ] );
				}
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
		if ( isset( $GLOBALS['PB_SECRET_SAUCE']['ROOT_THEMES'] ) ) {
			if ( is_array( $GLOBALS['PB_SECRET_SAUCE']['ROOT_THEMES'] ) ) {
				$whitelist = $GLOBALS['PB_SECRET_SAUCE']['ROOT_THEMES'];
			} else {
				$whitelist = array( $GLOBALS['PB_SECRET_SAUCE']['ROOT_THEMES'] );
			}
		} else {
			$whitelist = array();
		}

		$blacklist = apply_filters( 'pressbooks_disallowed_book_themes', array() );

		$compare = search_theme_directories();

		foreach ( $compare as $key => $val ) {
			if ( ! empty( $whitelist ) ) {
				// Hide themes which are not whitelisted
				if ( untrailingslashit( $val['theme_root'] ) != PB_PLUGIN_DIR . 'themes-root' && ( ! in_array( $key, $whitelist ) && PB_ROOT_THEME != $key ) ) {
					unset( $themes[ $key ] );
				}
			} elseif ( ! empty( $blacklist ) ) {
				$theme = wp_get_theme( str_replace( 'style.css', '', $val['theme_file'] ), $val['theme_root'] );
				// Hide book themes and themes which are blacklisted
				if ( 'pressbooks-book/style.css' == $val['theme_file'] || 'pressbooks-book' == $theme->get( 'Template' ) || in_array( $key, $blacklist ) ) {
					unset( $themes[ $key ] );
				}
			}
		}

		return $themes;
	}

}
