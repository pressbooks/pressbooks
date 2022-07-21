<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

/**
 * @deprecated Leftover code from old Custom CSS Editor. Use Custom Styles instead.
 *
 * @see Styles
 * @see https://github.com/pressbooks/pressbooks-custom-css
 */
class CustomCss {

	/**
	 * Get the fullpath to the Custom CSS folder
	 * Create if not there.
	 *
	 * @return string fullpath
	 */
	public static function getCustomCssFolder() {

		$path = \Pressbooks\Utility\get_media_prefix() . 'custom-css/';
		if ( ! file_exists( $path ) ) {
			wp_mkdir_p( $path );
		}

		return $path;
	}

	/**
	 * Is the current theme the custom css theme?
	 *
	 * @return bool
	 */
	public static function isCustomCss() {
		return ( 'pressbooks-custom-css' === get_stylesheet() );
	}

	/**
	 * Is the romanize parts option true?
	 *
	 * @return bool
	 */
	public static function isRomanized() {

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
	public static function getBaseTheme( $slug ) {
		$filename = static::getCustomCssFolder() . sanitize_file_name( $slug . '.css' );
		if ( ! file_exists( $filename ) ) {
			return false;
		}
		$theme = get_file_data(
			$filename, [
				'ThemeURI' => 'Theme URI',
			]
		);
		$theme_slug = str_replace( [ 'http://pressbooks.com/themes/', 'https://pressbooks.com/themes/' ], [ '', '' ], $theme['ThemeURI'] );

		return untrailingslashit( $theme_slug );
	}

	/**
	 * @deprecated Leftover code from old Custom CSS Editor. Use Custom Styles instead.
	 *
	 * @see https://github.com/pressbooks/pressbooks-custom-css
	 * @see \Pressbooks\Activation::wpmuActivate
	 */
	public static function upgradeCustomCss() {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$posts = [
			[
				'post_title' => __( 'Custom CSS for Ebook', 'pressbooks' ),
				'post_name' => 'epub',
				'post_type' => 'custom-css',
			],
			[
				'post_title' => __( 'Custom CSS for PDF', 'pressbooks' ),
				'post_name' => 'prince',
				'post_type' => 'custom-css',
			],
			[
				'post_title' => __( 'Custom CSS for Web', 'pressbooks' ),
				'post_name' => 'web',
				'post_type' => 'custom-css',
			],
		];

		$post = [
			'post_status' => 'publish',
			'post_author' => wp_get_current_user()->ID,
		];

		foreach ( $posts as $item ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_name = %s AND post_status = 'publish' ",
					[ $item['post_title'], $item['post_type'], $item['post_name'] ]
				)
			);
			if ( empty( $exists ) ) {
				$data = array_merge( $item, $post );
				wp_insert_post( $data );
			}
		}

	}

}
