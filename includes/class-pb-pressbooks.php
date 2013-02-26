<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks;


class PressBooks {

	/**
	 * Constructor.
	 */
	function __construct() {

		$this->registerThemeDirectories();

		do_action( 'pressbooks_loaded' );
	}


	/**
	 * Register theme directories, set a filter that hides themes under certain conditions
	 */
	function registerThemeDirectories() {

		register_theme_directory( PB_PLUGIN_DIR . 'themes-root/' );
		register_theme_directory( PB_PLUGIN_DIR . 'themes-book/' );

		if ( is_admin() ) {
			if ( \PressBooks\Book::isBook() ) {
				add_filter( 'allowed_themes', array( $this, 'allowedBookThemes' ) );
			} else {
				add_filter( 'allowed_themes', array( $this, 'allowedRootThemes' ) );
			}
		}
	}


	/**
	 * Used by add_filter( 'allowed_themes' ) Will hide any theme not in ./themes-book/*
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
	 * for 'pressbooks-root' and PB_ROOT_THEME constant.
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

		$compare = search_theme_directories();
		foreach ( $compare as $key => $val ) {
			if ( ! in_array( $key, $exceptions ) && untrailingslashit( $val['theme_root'] ) != PB_PLUGIN_DIR . 'themes-root' ) {
				unset ( $themes[$key] );
			}
		}

		return $themes;
	}

}