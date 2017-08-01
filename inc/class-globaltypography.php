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
		return [
			'grc' => __( 'Ancient Greek', 'pressbooks' ),
			'ar' => __( 'Arabic', 'pressbooks' ),
			'he' => __( 'Biblical Hebrew', 'pressbooks' ),
			'cans' => __( 'Canadian Indigenous Syllabics', 'pressbooks' ),
			'hi' => __( 'Hindi', 'pressbooks' ),
			'zh_HANS' => __( 'Chinese (Simplified)', 'pressbooks' ),
			'zh_HANT' => __( 'Chinese (Traditional)', 'pressbooks' ),
			'cop' => __( 'Coptic', 'pressbooks' ),
			'gu' => __( 'Gujarati', 'pressbooks' ),
			'pan' => __( 'Punjabi (Gurmukhi)', 'pressbooks' ),
			'ja' => __( 'Japanese', 'pressbooks' ),
			'ko' => __( 'Korean', 'pressbooks' ),
			'syr' => __( 'Syriac', 'pressbooks' ),
			'ta' => __( 'Tamil', 'pressbooks' ),
			'bo' => __( 'Tibetan', 'pressbooks' ),
			'tr' => __( 'Turkish', 'pressbooks' ),
		];
	}


	/**
	 * Get the current theme's font stacks.
	 *
	 * @param string $type (prince, epub, web ...)
	 *
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
	 * Get required languages for this book, excluding those supported by the theme.
	 */
	function _getRequiredLanguages() {
		$languages = get_option( 'pressbooks_global_typography', [] );
		$book_lang = $this->_getBookLanguage();

		if ( ! empty( $book_lang ) ) {
			$languages[] = $book_lang;
		}

		if ( is_array( $this->getThemeSupportedLanguages() ) ) {
			$languages = array_unique(
				array_merge( $languages, $this->getThemeSupportedLanguages() )
			);
		}

		return $languages;
	}

	/**
	 * Update and save the SCSS mixin which assigns the $global-typography variable.
	 */
	function updateGlobalTypographyMixin() {

		$languages = $this->_getRequiredLanguages();

		// Auto-create SCSS files

		// TODO: Use self::getThemeFontStacks() to parse if stack has $serif or $sans-serif strings

		foreach ( [ 'prince', 'epub', 'web' ] as $type ) {
			$this->_sassify( $type, $languages );
		}

		$this->getFonts( $languages );

	}


	/**
	 * @return string
	 */
	protected function _getBookLanguage() {

		$lang = '';
		$metadata = Book::getBookInformation();
		$book_lang = ( isset( $metadata['pb_language'] ) ) ? $metadata['pb_language'] : 'en';

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
			case 'hi': // Biblical Hebrew
				$lang = 'hi';
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

			if ( ! empty( $supported_languages ) ) {
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
	 *
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
			if ( isset( $import ) ) {
				$scss .= "@import '{$import}'; \n";
			}

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
	 * Check for absent font files and download if necessary.
	 */

	function getFonts( $languages = null ) {
		if ( ! $languages ) {
			$languages = $this->_getRequiredLanguages();
		}
		$basepath = WP_CONTENT_DIR . '/uploads/assets/fonts/';
		if ( ! is_dir( $basepath ) ) {
			mkdir( $basepath, 0755, true );
		}

		// List fonts
		$fontpacks = [
			'cans' => [
				'baseurl' => 'https://github.com/googlei18n/noto-fonts/raw/master/unhinted/',
				'files' => [
					'NotoSansCanadianAboriginal-Regular.ttf',
				],
			],
			'hi' => [
				'baseurl' => 'https://github.com/googlei18n/noto-fonts/raw/master/unhinted/',
				'files' => [
					'NotoSansDevanagari-Regular.ttf',
					'NotoSansDevanagari-Bold.ttf',
					'NotoSerifDevanagari-Bold.ttf',
					'NotoSerifDevanagari-Regular.ttf',
				],
			],
			'ja' => [
				'baseurl' => 'https://github.com/googlei18n/noto-cjk/raw/master/',
				'files' => [
					'NotoSansCJKjp-Light.otf',
					'NotoSansCJKjp-Regular.otf',
					'NotoSansCJKjp-Bold.otf',
				],
			],
			'ko' => [
				'baseurl' => 'https://github.com/googlei18n/noto-cjk/raw/master/',
				'files' => [
					'NotoSansCJKkr-Regular.otf',
					'NotoSansCJKkr-Bold.otf',
				],
			],
			'zh_HANS' => [
				'baseurl' => 'https://github.com/googlei18n/noto-cjk/raw/master/',
				'files' => [
					'NotoSansCJKsc-Regular.otf',
					'NotoSansCJKsc-Bold.otf',
				],
			],
			'zh_HANT' => [
				'baseurl' => 'https://github.com/googlei18n/noto-cjk/raw/master/',
				'files' => [
					'NotoSansCJKtc-Light.otf',
					'NotoSansCJKtc-Regular.otf',
					'NotoSansCJKtc-Bold.otf',
				],
			],
		];

		$language_names = $this->getSupportedLanguages();

		foreach ( $fontpacks as $language => $val ) {
			if ( in_array( $language, $languages, true ) ) {
				foreach ( $val['files'] as $font ) {
					if ( ! file_exists( $basepath . $font ) ) {
						if ( ! function_exists( 'download_url' ) ) {
							require_once( ABSPATH . 'wp-admin/includes/file.php' );
						}
						$result = download_url( $val['baseurl'] . $font );
						if ( is_wp_error( $result ) ) {
							$_SESSION['pb_errors'][] = sprintf( __( 'Your %1$s font could not be downloaded from %2$s.', 'pressbooks' ), $language_names[ $language ], '<code>' . $val['baseurl'] . $font . '</code>' ) . '<br /><pre>' . $result->get_error_message() . '</pre>';
							return false;
						} else {
							rename( $result, $basepath . $font );
						}
					}
				}
			}
		}

		return true;
	}
}
