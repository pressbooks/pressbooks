<?php
/**
 * Contains support for foreign language typography in editor, webBooks, EBOOK and PDF exports.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

class GlobalTypography {

	/**
	 * @var Sass
	 */
	protected $sass;

	/**
	 * @param Sass $sass
	 */
	public function __construct( $sass ) {
		$this->sass = $sass;
	}

	/**
	 * @return Sass
	 */
	public function getSass() {
		return $this->sass;
	}

	/**
	 * Get Pressbooks-supported languages.
	 *
	 * @return array
	 * @see \Pressbooks\Modules\ThemeOptions\GlobalOptions::renderLanguagesField
	 */
	function getSupportedLanguages() {
		return [
			'ff' => __( 'Adlam', 'pressbooks' ),
			'grc' => __( 'Ancient Greek', 'pressbooks' ),
			'hy' => __( 'Armenian', 'pressbooks' ),
			'ar' => __( 'Arabic', 'pressbooks' ),
			'bn' => __( 'Bengali', 'pressbooks' ),
			'he' => __( 'Biblical Hebrew', 'pressbooks' ),
			'cans' => __( 'Canadian Indigenous Syllabics', 'pressbooks' ),
			'chr' => __( 'Cherokee', 'pressbooks' ),
			'zh_HANS' => __( 'Chinese (Simplified)', 'pressbooks' ),
			'zh_HANT' => __( 'Chinese (Traditional)', 'pressbooks' ),
			'cop' => __( 'Coptic', 'pressbooks' ),
			'hi' => __( 'Devanagari (Hindi and Sanskrit)', 'pressbooks' ),
			'gu' => __( 'Gujarati', 'pressbooks' ),
			'pan' => __( 'Punjabi (Gurmukhi)', 'pressbooks' ),
			'ja' => __( 'Japanese', 'pressbooks' ),
			'kn' => __( 'Kannada', 'pressbooks' ),
			'ko' => __( 'Korean', 'pressbooks' ),
			'kr' => __( 'Khmer', 'pressbooks' ),
			'ml' => __( 'Malayalam', 'pressbooks' ),
			'music' => __( 'Musical Notation', 'pressbooks' ),
			'nqo' => __( 'N\'Ko', 'pressbooks' ),
			'or' => __( 'Odia', 'pressbooks' ),
			'syr' => __( 'Syriac', 'pressbooks' ),
			'ta' => __( 'Tamil', 'pressbooks' ),
			'te' => __( 'Telugu', 'pressbooks' ),
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

		$fullpath = $this->sass->pathToUserGeneratedSass() . "/_font-stack-{$type}.scss";

		if ( is_file( $fullpath ) ) {
			$return_value = \Pressbooks\Utility\get_contents( $fullpath );
		}

		return $return_value;
	}

	/**
	 * Get required languages for this book, excluding those supported by the theme.
	 *
	 * @return array
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
	 * Update and save user generated SCSS mixins:
	 * _font-stack-prince.scss, _font-stack-epub.scss, _font-stack-web.scss, ...
	 * Creates the necessary @import statements and variables, for foreign language support
	 * (CSS fallback font stacks, for unknown characters)
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
			case 'bn': // Bengali
				$lang = 'bn';
				break;
			case 'he': // Biblical Hebrew
				$lang = 'he';
				break;
			case 'hi': // Hindi
			case 'sa': // Sanskrit
				$lang = 'hi';
				break;
			case 'kn': // Kannada
				$lang = 'kn';
				break;
			case 'ml': // Malayalam
				$lang = 'ml';
				break;
			case 'or': // Odia
				$lang = 'or';
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
			case 'te': // Telugu
				$lang = 'te';
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
	 * @see \Pressbooks\Modules\ThemeOptions\GlobalOptions::renderLanguagesField
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
				if ( file_exists( $this->sass->pathToFonts() . "/_{$i}.scss" ) ) {
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

		$dir = $this->sass->pathToUserGeneratedSass();
		$file = $dir . "/_font-stack-{$type}.scss";
		\Pressbooks\Utility\put_contents( $file, $scss );
	}

	/**
	 * Array driven development :(
	 *
	 * @return array
	 */
	function fontPacks() {
		$fontpacks = [
			'bn' => [
				'baseurl' => 'https://github.com/notofonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansBengali-Bold.otf' => 'NotoSansBengali/NotoSansBengali-Bold.otf',
					'NotoSansBengali-Regular.otf' => 'NotoSansBengali/NotoSansBengali-Regular.otf',
					'NotoSerifBengali-Bold.otf' => 'NotoSerifBengali/NotoSerifBengali-Bold.otf',
					'NotoSerifBengali-Regular.otf' => 'NotoSerifBengali/NotoSerifBengali-Regular.otf',
				],
			],
			'cans' => [
				'baseurl' => 'https://github.com/notofonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansCanadianAboriginal-Regular.otf' => 'NotoSansCanadianAboriginal/NotoSansCanadianAboriginal-Regular.otf',
					'NotoSansCanadianAboriginal-Bold.otf' => 'NotoSansCanadianAboriginal/NotoSansCanadianAboriginal-Bold.otf',
				],
			],
			'chr' => [
				'baseurl' => 'https://github.com/notofonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansCherokee-Regular.otf' => 'NotoSansCherokee/NotoSansCherokee-Regular.otf',
					'NotoSansCherokee-Bold.otf' => 'NotoSansCherokee/NotoSansCherokee-Bold.otf',
				],
			],
			'ff' => [
				'baseurl' => 'https://github.com/notofonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansAdlam-Regular.otf' => 'NotoSansAdlam/NotoSansAdlam-Regular.otf',
					'NotoSansAdlam-Bold.otf' => 'NotoSansAdlam/NotoSansAdlam-Bold.otf',
					'NotoSansAdlamUnjoined-Regular.otf' => 'NotoSansAdlamUnjoined/NotoSansAdlamUnjoined-Regular.otf',
					'NotoSansAdlamUnjoined-Bold.otf' => 'NotoSansAdlamUnjoined/NotoSansAdlamUnjoined-Bold.otf',
				],
			],
			'hi' => [
				'baseurl' => 'https://github.com/notofonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansDevanagari-Regular.otf' => 'NotoSansDevanagari/NotoSansDevanagari-Regular.otf',
					'NotoSansDevanagari-Bold.otf' => 'NotoSansDevanagari/NotoSansDevanagari-Bold.otf',
					'NotoSerifDevanagari-Bold.otf' => 'NotoSerifDevanagari/NotoSerifDevanagari-Bold.otf',
					'NotoSerifDevanagari-Regular.otf' => 'NotoSerifDevanagari/NotoSerifDevanagari-Regular.otf',
				],
			],
			'hy' => [
				'baseurl' => 'https://github.com/notofonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansArmenian-Regular.otf' => 'NotoSansArmenian/NotoSansArmenian-Regular.otf',
					'NotoSansArmenian-Bold.otf' => 'NotoSansArmenian/NotoSansArmenian-Bold.otf',
					'NotoSerifArmenian-Regular.otf' => 'NotoSerifArmenian/NotoSerifArmenian-Regular.otf',
					'NotoSerifArmenian-Bold.otf' => 'NotoSerifArmenian/NotoSerifArmenian-Bold.otf',
				],
			],
			'ja' => [
				'baseurl' => 'https://github.com/googlefonts/noto-cjk/raw/main/Sans/OTF/Japanese/',
				'files' => [
					'NotoSansCJKjp-Light.otf' => 'NotoSansCJKjp-Light.otf',
					'NotoSansCJKjp-Regular.otf' => 'NotoSansCJKjp-Regular.otf',
					'NotoSansCJKjp-Bold.otf' => 'NotoSansCJKjp-Bold.otf',
				],
			],
			'kn' => [
				'baseurl' => 'https://github.com/notofonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansKannada-Bold.otf' => 'NotoSansKannada/NotoSansKannada-Bold.otf',
					'NotoSansKannada-Regular.otf' => 'NotoSansKannada/NotoSansKannada-Regular.otf',
					'NotoSerifKannada-Bold.otf' => 'NotoSerifKannada/NotoSerifKannada-Bold.otf',
					'NotoSerifKannada-Regular.otf' => 'NotoSerifKannada/NotoSerifKannada-Regular.otf',
				],
			],
			'ko' => [
				'baseurl' => 'https://github.com/googlefonts/noto-cjk/raw/main/Sans/OTF/Korean/',
				'files' => [
					'NotoSansCJKkr-Regular.otf' => 'NotoSansCJKkr-Regular.otf',
					'NotoSansCJKkr-Bold.otf' => 'NotoSansCJKkr-Bold.otf',
				],
			],
			'kr' => [
				'baseurl' => 'https://github.com/googlefonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansKhmer-Regular.otf' => 'NotoSansKhmer/NotoSansKhmer-Regular.otf',
					'NotoSansKhmer-Bold.otf' => 'NotoSansKhmer/NotoSansKhmer-Bold.otf',
					'NotoSerifKhmer-Regular.otf' => 'NotoSerifKhmer/NotoSerifKhmer-Regular.otf',
					'NotoSerifKhmer-Bold.otf' => 'NotoSerifKhmer/NotoSerifKhmer-Bold.otf',
				],
			],
			'ml' => [
				'baseurl' => 'https://github.com/notofonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansMalayalam-Bold.otf' => 'NotoSansMalayalam/NotoSansMalayalam-Bold.otf',
					'NotoSansMalayalam-Regular.otf' => 'NotoSansMalayalam/NotoSansMalayalam-Regular.otf',
					'NotoSerifMalayalam-Bold.otf' => 'NotoSerifMalayalam/NotoSerifMalayalam-Bold.otf',
					'NotoSerifMalayalam-Regular.otf' => 'NotoSerifMalayalam/NotoSerifMalayalam-Regular.otf',
				],
			],
			'music' => [
				'baseurl' => 'https://github.com/steinbergmedia/bravura/raw/master/redist/otf/',
				'files' => [
					'BravuraText.otf' => 'BravuraText.otf',
				],
			],
			'nqo' => [
				'baseurl' => 'https://github.com/notofonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansNKo-Regular.otf' => 'NotoSansNKo/NotoSansNKo-Regular.otf',
				],
			],
			'or' => [
				'baseurl' => 'https://github.com/notofonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansOriya-Bold.otf' => 'NotoSansOriya/NotoSansOriya-Bold.otf',
					'NotoSansOriya-Regular.otf' => 'NotoSansOriya/NotoSansOriya-Regular.otf',
					'NotoSerifOriya-Bold.otf' => 'NotoSerifOriya/NotoSerifOriya-Bold.otf',
					'NotoSerifOriya-Regular.otf' => 'NotoSerifOriya/NotoSerifOriya-Regular.otf',
				],
			],
			'te' => [
				'baseurl' => 'https://github.com/notofonts/noto-fonts/raw/main/unhinted/otf/',
				'files' => [
					'NotoSansTelugu-Bold.otf' => 'NotoSansTelugu/NotoSansTelugu-Bold.otf',
					'NotoSansTelugu-Regular.otf' => 'NotoSansTelugu/NotoSansTelugu-Regular.otf',
					'NotoSerifTelugu-Bold.otf' => 'NotoSerifTelugu/NotoSerifTelugu-Bold.otf',
					'NotoSerifTelugu-Regular.otf' => 'NotoSerifTelugu/NotoSerifTelugu-Regular.otf',
				],
			],
			'zh_HANS' => [
				'baseurl' => 'https://github.com/googlefonts/noto-cjk/raw/main/Sans/OTF/SimplifiedChinese/',
				'files' => [
					'NotoSansCJKsc-Regular.otf' => 'NotoSansCJKsc-Regular.otf',
					'NotoSansCJKsc-Bold.otf' => 'NotoSansCJKsc-Bold.otf',
				],
			],
			'zh_HANT' => [
				'baseurl' => 'https://github.com/googlefonts/noto-cjk/raw/main/Sans/OTF/TraditionalChinese/',
				'files' => [
					'NotoSansCJKtc-Light.otf' => 'NotoSansCJKtc-Light.otf',
					'NotoSansCJKtc-Regular.otf' => 'NotoSansCJKtc-Regular.otf',
					'NotoSansCJKtc-Bold.otf' => 'NotoSansCJKtc-Bold.otf',
				],
			],
		];
		return $fontpacks;
	}

	/**
	 * Check for absent font files and download if necessary.
	 *
	 * @param array $languages
	 *
	 * @return bool
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
		$fontpacks = $this->fontPacks();
		$language_names = $this->getSupportedLanguages();

		foreach ( $fontpacks as $language => $val ) {
			if ( in_array( $language, $languages, true ) ) {
				foreach ( $val['files'] as $font => $font_url ) {
					if ( ! file_exists( $basepath . $font ) ) {
						if ( ! function_exists( 'download_url' ) ) {
							require_once( ABSPATH . 'wp-admin/includes/file.php' );
						}

						$result = download_url( $val['baseurl'] . $font_url );
						if ( is_wp_error( $result ) ) {
							$_SESSION['pb_errors'][] = sprintf( __( 'Your %1$s font could not be downloaded from %2$s.', 'pressbooks' ), $language_names[ $language ], '<code>' . $val['baseurl'] . $font_url . '</code>' ) . '<br /><pre>' . $result->get_error_message() . '</pre>';
							return false;
						} else {
							copy( $result, $basepath . $font );
							unlink( $result );
						}
					}
				}
			}
		}

		return true;
	}
}
