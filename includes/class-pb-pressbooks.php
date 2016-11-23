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

		/**
		 * Register additional theme directories.
		 *
		 * Additional theme directories (e.g. those within another plugin, or custom
		 * subdirectories of wp-content) may be registered via this action hook.
		 *
		 * @since 3.8.0
		 */
		do_action( 'pressbooks_register_theme_directory' );

		if ( is_admin() ) {
			if ( Book::isBook() ) {
				add_filter( 'allowed_themes', array( $this, 'allowedBookThemes' ) );
			} elseif ( ! is_network_admin() ) {
				add_filter( 'allowed_themes', array( $this, 'allowedRootThemes' ) );
			}
		}
	}


	/**
	 * Used by add_filter( 'allowed_themes' ). Will hide any non-book themes.
	 *
	 * @param array $themes
	 *
	 * @return array
	 */
	function allowedBookThemes( $themes ) {
		error_log( print_r( $themes, true ) );

		$compare = search_theme_directories();

		foreach ( $compare as $key => $val ) {
			$stylesheet = str_replace( 'style.css', '', $val['theme_file'] );
			$theme = wp_get_theme( $stylesheet, $val['theme_root'] );
			// Hide any available non-book themes, as identified by checking to see if they are either pressbooks-book or a child theme of pressbooks-book.
			if ( 'pressbooks-book/style.css' != $val['theme_file'] && 'pressbooks-book' != $theme->get( 'Template' ) ) {
				unset( $themes[ $key ] );
			}
		}

		error_log( print_r( $themes, true ) );

		return $themes;
	}


	/**
	 * Used by add_filter( 'allowed_themes' ). Will hide any book themes.
	 *
	 * @param array $themes
	 *
	 * @return array
	 */
	function allowedRootThemes( $themes ) {
		error_log( print_r( $themes, true ) );
		$compare = search_theme_directories();

		foreach ( $compare as $key => $val ) {
			$stylesheet = str_replace( 'style.css', '', $val['theme_file'] );
			$theme = wp_get_theme( $stylesheet, $val['theme_root'] );
			// Hide any available book themes, as identified by checking to see if they are either pressbooks-book or a child theme of pressbooks-book.
			if ( 'pressbooks-book/style.css' == $val['theme_file'] || 'pressbooks-book' == $theme->get( 'Template' ) ) {
				unset( $themes[ $key ] );
			}
		}

		error_log( print_r( $themes, true ) );
		return $themes;
	}

}
