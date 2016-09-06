<?php
/**
 * Contains support for foreign language typography in editor, webBooks, EBOOK and PDF exports.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks;


class GlobalTypography {

	/**
	 * Get Pressbooks-supported languages.
	 *
	 * @return array
	 */
	function getSupportedLanguages() {
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
			'tr' => __( 'Turkish', 'pressbooks' ),
		);
	}


	/**
	 * Get the current theme's font stacks.
	 *
	 * @param string $type (prince, epub, web ...)
	 * @return string
	 */
	function getThemeFontStacks( $type ) {

		$return_value = '';

		$fullpath = Container::get( 'Sass' )->pathToUserGeneratedSass() . "/_font-stack-{$type}.scss";

		if ( is_file( $fullpath ) ) {
			$return_value = file_get_contents( $fullpath );
		}

		return $return_value;
	}


	/**
	 * Update and save the SCSS mixin which assigns the $global-typography variable.
	 */
	function updateGlobalTypographyMixin() {

		// Get languages

		$languages = get_option( 'pressbooks_global_typography', array() );

		if ( $book_lang = $this->_getBookLanguage() ) {
			$languages[] = $book_lang;
		}

		if ( is_array( $this->getThemeSupportedLanguages() ) ) {
			$languages = array_unique(
				array_merge( $languages, $this->getThemeSupportedLanguages() )
			);
		}

		// Auto-create SCSS files

		// TODO: Use self::getThemeFontStacks() to parse if stack has $serif or $sans-serif strings

		foreach ( [ 'prince', 'epub', 'web' ] as $type ) {
			$this->_sassify( $type, $languages );
		}

	}


	/**
	 * @return string
	 */
	protected function _getBookLanguage() {

		$lang = '';
		$book_lang = Book::getBookInformation();
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
			case 'tr': // Turkish
				$lang = 'tr';
				break;
		}

		return $lang;
	}


	/**
	 * Get the current theme's supported languages.
	 *
	 * @return array
	 */
	function getThemeSupportedLanguages() {

		$return_value = false;

		$fullpath = get_stylesheet_directory() . '/theme-information.php';

		if ( is_file( $fullpath ) ) {

			require( $fullpath );

			if ( isset( $supported_languages ) && ! empty( $supported_languages ) ) {
				$return_value = $supported_languages;
			}
		} else {
			$return_value = get_theme_support( 'pressbooks_global_typography' );
		}

		return $return_value;
	}


	/**
	 * @param $type
	 * @param array $languages
	 * @return void
	 */
	protected function _sassify( $type, array $languages ) {

		// Create Scss

		$scss = "// Global Typography \n";

		foreach ( $languages as $lang ) {

			// Find scss font template in order of priority
			foreach ( [ "fonts-{$lang}-{$type}", "fonts-{$lang}" ] as $i ) {
				if ( file_exists( Container::get( 'Sass' )->pathToFonts() . "/_{$i}.scss" ) ) {
					$import = $i;
					break;
				}

			}
			// Import the font template we find
			if ( isset( $import ) )
				$scss .= "@import '{$import}'; \n";

			// Add a Sass !default in-case the template doesn't contain our variable
			$scss .= "\$sans-serif-{$type}-{$lang}: null !default; \n";
			$scss .= "\$serif-{$type}-{$lang}: null !default; \n";
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
	 * Update and save the supplementary webBook stylesheet which adds global typography support.
	 *
	 * @return void
	 */
	function updateWebBookStyleSheet() {

		$sass = Container::get( 'Sass' );

		if ( $sass->isCurrentThemeCompatible( 1 ) ) {
			$path_to_style = realpath( get_stylesheet_directory() . '/style.scss' );
			// Populate $url-base variable so that links to images and other assets remain intact
			$scss = '$url-base: \'' . get_stylesheet_directory_uri() . "/';\n";

			$scss .= file_get_contents( $path_to_style );
			$css = $sass->compile( $scss, [
				$sass->pathToUserGeneratedSass(),
				$sass->pathToPartials(),
				$sass->pathToFonts(),
				get_stylesheet_directory(),
			] );

		} elseif ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$path_to_style = realpath( get_stylesheet_directory() . '/assets/styles/web/style.scss' );

			// Populate $url-base variable so that links to images and other assets remain intact
			$scss = '$url-base: \'' . get_stylesheet_directory_uri() . "/';\n";

			$scss .= file_get_contents( $path_to_style );
			$css = $sass->compile( $scss, $sass->defaultIncludePaths( 'web' ) );
		} else {
			return;
		}

		$css = $this->fixWebFonts( $css );

		$css_file = $sass->pathToUserGeneratedCss() . '/style.css';
		file_put_contents( $css_file, $css );
	}


	/**
	 * Fix relative/ambiguous URLs to web fonts
	 *
	 * @param $css
	 * @return mixed
	 */
	function fixWebFonts( $css ) {

		// Search for url("*"), url('*'), and url(*)
		$url_regex = '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i';
		$css = preg_replace_callback( $url_regex, function ( $matches ) {

			$url = $matches[3];
			$filename = sanitize_file_name( basename( $url ) );

			// Look for themes-book/pressbooks-book/fonts/*.otf (or .woff, or .ttf), update URL
			if ( preg_match( '#^themes-book/pressbooks-book/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url ) ) {
				return "url(" . PB_PLUGIN_URL . "themes-book/pressbooks-book/fonts/$filename)";
			}

			return $matches[0]; // No change

		}, $css );

		return $css;
	}


}
