<?php
/**
 * Contains support for foreign language typography in editor, webBooks, EBOOK and PDF exports.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks;


class GlobalTypography {

	/**
	 * Get Pressbooks-supported languages.
	 *
	 * @return array
	 */
	static function getSupportedLanguages() {
		return array(
			'grc' => __( 'Ancient Greek', 'pressbooks' ),
			'ar' => __( 'Arabic', 'pressbooks' ),
			'he' => __( 'Biblical Hebrew', 'pressbooks' ),
			'zh_HANS' => __( 'Chinese (Simplified)', 'pressbooks' ),
			'zh_HANT' => __( 'Chinese (Traditional)', 'pressbooks' ),
			'cop' => __( 'Coptic', 'pressbooks' ),
			'gu' => __( 'Gujarati', 'pressbooks' ),
			'ja' => __( 'Japanese', 'pressbooks' ),
			'ko' => __( 'Korean', 'pressbooks' ),
			'syr' => __( 'Syriac', 'pressbooks' ),
			'ta' => __( 'Tamil', 'pressbooks' ),
			'bo' => __( 'Tibetan', 'pressbooks' ),
		);
	}

	/**
	 * Get the current theme's supported languages.
	 *
	 * @return array
	 */
	static function getThemeSupportedLanguages() {

		$return_value = array();

		$fullpath = get_stylesheet_directory() . '/theme-information.php';

		if ( is_file( $fullpath ) ) {

			require( $fullpath );

			if ( isset( $supported_languages ) && ! empty( $supported_languages ) ) {
				$return_value = $supported_languages;
			}
		}

		return $return_value;
	}


	/**
	 * Get the current theme's font stacks.
	 *
	 * @return string
	 */
	static function getThemeFontStacks() {

		$return_value = '';

		$fullpath = get_stylesheet_directory() . '/_mixins.scss'; // TODO: Change to _fonts-{$type}.scss

		if ( is_file( $fullpath ) ) {
			$return_value = file_get_contents( $fullpath );
		}

		return $return_value;
	}

	/**
	 * TODO: Change this to SCSS files
	 * TODO: Move code to SCSS module
	 *
	 * Update and save the SCSS mixin which assigns the $global-typography variable.
	 *
	 * @param int $pid
	 * @param \WP_Post $post
	 * @throws \Exception
	 */
	static function updateGlobalTypographyMixin( $pid = null, $post = null ) {

		if ( isset( $post ) && 'metadata' !== $post->post_type )
			return; // Bail

		// Get languages

		$languages = get_option( 'pressbooks_global_typography', array() );

		if ( $book_lang = static::_getBookLanguage() ) {
			$languages[] = $book_lang;
		}

		$languages = array_unique(
			array_merge( $languages, static::getThemeSupportedLanguages() )
		);


		// Auto-create SCSS files

		// TODO: Use self::getThemeFontStacks() to parse if stack has $serif or $sans-serif strings
		// TODO: Don't put @font-face in CSS when not necessary

		foreach ( [ 'prince', 'epub', 'web' ] as $type ) {
			static::_sassify( $type, $languages );
		}

	}

	/**
	 * @param $type
	 * @param array $languages
	 * @return void
	 */
	static protected function _sassify( $type, array $languages ) {

		// Create Scss

		$scss = "// Global Typography \n";

		foreach ( $languages as $lang ) {
			$scss .= "@import 'fonts-{$lang}'; \n";
			$scss .= "\$sans-serif-{$type}-{$lang}: false !default; \n";
			$scss .= "\$serif-{$type}-{$lang}: false !default; \n";
		}

		$scss .= "\$sans-serif-{$type}: ";
		foreach ( $languages as $lang ) {
			$scss .= "\$sans-serif-{$type}-{$lang}, ";
		}
		$scss .= "sans-serif; \n";

		$scss .= "\$serif-{$type}: ";
		foreach ( $languages as $lang ) {
			$scss .= "\$serif-{$type}-{$lang}, ";
		}
		$scss .= "serif; \n";

		// Save file

		$dir = Container::get( 'Sass' )->pathToUserGeneratedSass();
		$file = $dir . "/_font-stack-{$type}.scss";
		file_put_contents( $file, $scss );
	}


	/**
	 * @return string
	 */
	static protected function _getBookLanguage() {

		$lang = '';
		$book_lang = \PressBooks\Book::getBookInformation();
		$book_lang = @$book_lang['pb_language'];

		switch ( $book_lang ) {
			case 'el': // Ancient Greek
				$lang = 'grc';
				break;
			case 'ar': // Arabic
			case 'ar-dz':
			case 'ar-bh':
			case 'ar-eg':
			case 'ar-jo':
			case 'ar-kw':
			case 'ar-lb':
			case 'ar-ma':
			case 'ar-om':
			case 'ar-qa':
			case 'ar-sa':
			case 'ar-sy':
			case 'ar-tn':
			case 'ar-ae':
			case 'ar-ye':
				$lang = 'ar';
				break;
			case 'he': // Biblical Hebrew
				$lang = 'he';
				break;
			case 'zh': // Chinese (Simplified)
			case 'zh-cn':
			case 'zh-sg':
				$lang = 'zh_HANS';
				break;
			case 'zh-hk': // Chinese (Traditional)
			case 'zh-tw':
				$lang = 'zh_HANT';
				break;
			case 'gu': // Gujarati
				$lang = 'gu';
				break;
			case 'ja': // Japanese
				$lang = 'ja';
				break;
			case 'ko': // Korean
				$lang = 'ko';
				break;
			case 'ta': // Tamil
				$lang = 'ta';
				break;
		}

		return $lang;
	}


	/**
	 * Update and save the supplementary webBook stylesheet which adds global typography support.
	 *
	 * @param int $pid
	 * @param \WP_Post $post
	 * @return void
	 */
	static function updateWebBookStyleSheet( $pid = null, $post = null ) {

		if ( isset( $post ) && 'metadata' !== $post->post_type )
			return; // Bail

		$path_to_style = realpath( get_stylesheet_directory() . '/style.scss' );
		if ( ! $path_to_style ) {
			return;
		}

		$sass = Container::get( 'Sass' );

		$scss = file_get_contents( $path_to_style );
		$css = $sass->compile( $scss );

		// Search for url("*"), url('*'), and url(*)
		$url_regex = '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i';
		$css = preg_replace_callback( $url_regex, function ( $matches ) {

			$url = $matches[3];
			$filename = sanitize_file_name( basename( $url ) );

			// TODO: How does this work if the theme is not "pressbooks-book" ?
			// TODO: Currently hundreds of fonts in pressbooks-book/fonts/, can we not move these?

			// Look for themes-book/pressbooks-book/fonts/*.otf (or .woff, or .ttf), update URL
			if ( preg_match( '#^themes-book/pressbooks-book/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url ) ) {
				return "url(" . site_url( '/' ) . "themes-book/pressbooks-book/fonts/$filename)";
			}

			return $matches[0]; // No change

		}, $css );

		$css_file = $sass->pathToUserGeneratedCss() . '/style.css';
		file_put_contents( $css_file, $css );
	}

}