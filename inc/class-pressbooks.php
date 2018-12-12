<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
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
		wp_cache_add_global_groups( [ 'pb' ] );

		$this->registerThemeDirectories();

		/**
		 * @since 4.3.0
		 */
		do_action( 'pb_loaded' );

		/**
		 * @deprecated 4.3.0 Use pb_loaded instead.
		 */
		do_action( 'pressbooks_loaded' );
	}


	/**
	 * Register theme directories, set a filter that hides themes under certain conditions
	 */
	function registerThemeDirectories() {

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
				add_filter( 'allowed_themes', [ $this, 'allowedBookThemes' ] );
			} elseif ( ! is_network_admin() ) {
				add_filter( 'allowed_themes', [ $this, 'allowedRootThemes' ] );
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
		$compare = search_theme_directories();
		$themes = array_intersect_key( $themes, $compare );
		foreach ( $compare as $key => $val ) {
			$stylesheet = str_replace( 'style.css', '', $val['theme_file'] );
			$theme = wp_get_theme( $stylesheet, $val['theme_root'] );
			// Hide any available non-book themes, as identified by checking to see if they are either pressbooks-book or a child theme of pressbooks-book.
			if ( 'pressbooks-book/style.css' !== $val['theme_file'] && 'pressbooks-book' !== $theme->get( 'Template' ) ) {
				unset( $themes[ $key ] );
			}
		}

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
		$compare = search_theme_directories();
		$themes = array_intersect_key( $themes, $compare );
		foreach ( $compare as $key => $val ) {
			$stylesheet = str_replace( 'style.css', '', $val['theme_file'] );
			$theme = wp_get_theme( $stylesheet, $val['theme_root'] );
			// Hide any available book themes, as identified by checking to see if they are either pressbooks-book or a child theme of pressbooks-book.
			if ( 'pressbooks-book/style.css' === $val['theme_file'] || 'pressbooks-book' === $theme->get( 'Template' ) ) {
				unset( $themes[ $key ] );
			}
		}

		return $themes;
	}

}
