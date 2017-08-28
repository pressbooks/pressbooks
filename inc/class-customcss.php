<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

/**
 * @deprecated Leftover code from old Custom CSS Editor. Use Custom Styles instead.
 *
 * @see https://github.com/pressbooks/pressbooks-custom-css
 */
class CustomCss {

	/**
	 * Get the fullpath to the Custom CSS folder
	 * Create if not there.
	 *
	 * @return string fullpath
	 */
	static function getCustomCssFolder() {

		$path = \Pressbooks\Utility\get_media_prefix() . 'custom-css/';
		if ( ! file_exists( $path ) ) {
			mkdir( $path, 0775, true );
		}

		return $path;
	}


	/**
	 * Is the current theme the custom css theme?
	 *
	 * @return bool
	 */
	static function isCustomCss() {
		return ( 'pressbooks-custom-css' === get_stylesheet() );
	}


	/**
	 * Is the romanize parts option true?
	 *
	 * @return bool
	 */
	static function isRomanized() {

		$options = get_option( 'pressbooks_theme_options_pdf' );
		if ( isset( $options['pdf_romanize_parts'] ) ) {
			return (bool) ( $options['pdf_romanize_parts'] );
		}
		return false;
	}


	/**
	 * Determine base theme that was used for the selected Custom CSS.
	 *
	 * @param $slug string
	 *
	 * @return string
	 */
	static function getBaseTheme( $slug ) {
		$filename = static::getCustomCssFolder() . sanitize_file_name( $slug . '.css' );
		if ( ! file_exists( $filename ) ) {
			return false;
		}
		$theme = get_file_data( $filename, [ 'ThemeURI' => 'Theme URI' ] );
		$theme_slug = str_replace( [ 'http://pressbooks.com/themes/', 'https://pressbooks.com/themes/' ], [ '', '' ], $theme['ThemeURI'] );

		return untrailingslashit( $theme_slug );
	}

}
