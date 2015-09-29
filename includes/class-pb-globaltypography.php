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
			'grc'		=> __( 'Ancient Greek', 'pressbooks' ),
			'ar'		=> __( 'Arabic', 'pressbooks' ),
			'he'		=> __( 'Biblical Hebrew', 'pressbooks' ),
			'zh_HANS'	=> __( 'Chinese (Simplified)', 'pressbooks' ),
			'zh_HANT'	=> __( 'Chinese (Traditional)', 'pressbooks' ),
			'cop'		=> __( 'Coptic', 'pressbooks' ),
			'gu'		=> __( 'Gujarati', 'pressbooks' ),
			'ja'		=> __( 'Japanese', 'pressbooks' ),
			'ko'		=> __( 'Korean', 'pressbooks' ),
			'syr'		=> __( 'Syriac', 'pressbooks' ),
			'ta'		=> __( 'Tamil', 'pressbooks' ),
			'bo'		=> __( 'Tibetan', 'pressbooks' ),
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
		
		if ( is_file( $fullpath ) ) require_once( $fullpath );
				
		if ( @$supported_languages ) $return_value = $supported_languages;
						
		return $return_value;
	}

	/**
	 * Get the current theme's font stacks.
	 *
	 * @return array
	 */
	 
	static function getThemeFontStacks() {
		
		$return_value = array();
		
		$fullpath = get_stylesheet_directory() . '/theme-information.php';
		
		if ( is_file( $fullpath ) ) require_once( $fullpath );
				
		if ( @$font_stacks ) $return_value = $font_stacks;
						
		return $return_value;
	}

	/**
	 * Update and save the SCSS mixin which assigns the $global-typography variable.
	 *
	 * @param int $pid
	 * @param \WP_Post $post
	 */
	 
	static function updateGlobalTypographyMixin( $pid = null, $post = null ) {
		
		if ( isset( $post ) && 'metadata' !== $post->post_type )
			return; // Bail
				
		$scss = "// Global Typography\n";
		$scss .= "@import 'global-fonts';\n";
		
		$font_stacks = \PressBooks\GlobalTypography::getThemeFontStacks();
		
		if ( in_array( 'sans', $font_stacks ) ) {
			$global_font_stack_sans = '$global-font-stack-sans: ';
			$sans = true;
		} else {
			$global_font_stack_sans = '$global-font-stack-sans: null';
		}
		 
		if ( in_array( 'serif', $font_stacks ) ) { 
			$global_font_stack_serif = '$global-font-stack-serif: ';
			$serif = true;
		} else {
			$global_font_stack_serif = '$global-font-stack-serif: null';
		}

		$languages = get_option( 'pressbooks_global_typography' );

		$already_supported_languages = \PressBooks\GlobalTypography::getThemeSupportedLanguages();
		
		$book_lang = \PressBooks\Book::getBookInformation();
		$book_lang = @$book_lang['pb_language'];
		
		if ( !is_array( $languages ) ) {
			$languages = array();
		}
		
		switch ( $book_lang ) {
			case 'el': // Ancient Greek
				$languages[] = 'grc';
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
				$languages[] = 'ar';
				break;
			case 'he': // Biblical Hebrew
				$languages[] = 'he';
				break;
			case 'zh': // Chinese (Simplified)
			case 'zh-cn':
			case 'zh-sg':
				$languages[] = 'zh_HANS';
				break;
			case 'zh-hk': // Chinese (Traditional)
			case 'zh-tw':
				$languages[] = 'zh_HANT';
				break;
			case 'gu': // Gujarati
				$languages[] = 'gu';
				break;
			case 'ja': // Japanese
				$languages[] = 'ja';
				break;
			case 'ko': // Korean
				$languages[] = 'ko';
				break;
			case 'ta': // Tamil
				$languages[] = 'ta';
				break;
		}
		
		$includes = array();
				
		if ( !empty( $languages ) ) {
			foreach ( $languages as $language )	{
				switch ( $language ) {
					case 'grc': // Ancient Greek
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontGreekAncient';
								$global_font_stack_sans .= "'SBL Greek', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontGreekAncient';
								$global_font_stack_serif .= "'SBL Greek', ";
							}
						}
						break;
					case 'ar': // Arabic
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontArabicKufi';
								$global_font_stack_sans .= "'Noto Kufi Arabic', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontArabicNaskh';
								$global_font_stack_serif .= "'Noto Naskh Arabic', ";
							}
						}
						break;
					case 'he': // Biblical Hebrew
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontHebrewBiblical';
								$global_font_stack_sans .= "'SBL Hebrew', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontHebrewBiblical';
								$global_font_stack_serif .= "'SBL Hebrew', ";
							}
						}
						break;
					case 'zh_HANS': // Chinese (Simplified)
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontChineseSimplified';
								$global_font_stack_sans .= "'Noto CJK SC', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontChineseSimplified';
								$global_font_stack_serif .= "'Noto CJK SC', ";
							}
						}
						break;
					case 'zh_HANT': // Chinese (Simplified)
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontChineseTraditional';
								$global_font_stack_sans .= "'Noto CJK TC', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontChineseTraditional';
								$global_font_stack_serif .= "'Noto CJK TC', ";
							}
						}
						break;
					case 'cop': // Coptic
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontCoptic';
								$global_font_stack_sans .= "'Antinoou', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontCoptic';
								$global_font_stack_serif .= "'Antinoou', ";
							}
						}
						break;
					case 'gu': // Gujarati
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontGujarati';
								$global_font_stack_sans .= "'Ekatra', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontGujarati';
								$global_font_stack_serif .= "'Ekatra', ";
							}
						}
						break;
					case 'ja': // Japanese
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontJapanese';
								$global_font_stack_sans .= "'Noto CJK JP', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontJapanese';
								$global_font_stack_serif .= "'Noto CJK JP', ";
							}
						}
						break;
					case 'ko': // Korean
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontKorean';
								$global_font_stack_sans .= "'Noto CJK KR', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontKorean';
								$global_font_stack_serif .= "'Noto CJK KR', ";
							}
						}
						break;
					case 'syr': // Syriac
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontSyriac';
								$global_font_stack_sans .= "'Noto Sans Syriac', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontSyriac';
								$global_font_stack_serif .= "'Noto Sans Syriac', ";
							}
						}
						break;
					case 'ta': // Tamil
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontTamil';
								$global_font_stack_sans .= "'Noto Sans Tamil', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontTamil';
								$global_font_stack_serif .= "'Noto Sans Tamil', ";
							}
						}
						break;
					case 'bo': // Tibetan
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( @$sans ) {
								$includes[] = 'LangFontTibetan';
								$global_font_stack_sans .= "'Noto Sans Tibetan', ";
							}
							if ( @$serif ) {
								$includes[] = 'LangFontTibetan';
								$global_font_stack_serif .= "'Noto Sans Tibetan', ";
							}
						}
						break;
				}
			}
			
			$includes = array_unique( $includes );
			
			foreach ( $includes as $include ) {
				$scss .= "@include $include;\n";
			}
						
			$global_font_stack_sans = rtrim( $global_font_stack_sans, ', ' );
			$global_font_stack_sans .= ";\n";
			$global_font_stack_serif = rtrim( $global_font_stack_serif, ', ' );
			$global_font_stack_serif .= ";\n";
		} else {
			$global_font_stack_sans .= 'null;';
			$global_font_stack_serif .= 'null;';
		}

		$scss .= $global_font_stack_sans;
		$scss .= $global_font_stack_serif;
		
		$wp_upload_dir = wp_upload_dir();

		$upload_dir = $wp_upload_dir['basedir'] . '/global-typography';

		if ( ! is_dir( $upload_dir ) ) {
			mkdir( $upload_dir );
		}
		
		if ( ! is_dir( $upload_dir ) ) {
			throw new \Exception( 'Could not create mixin directory.' );
		}
					
		$scss_file = $upload_dir . '/_global-font-stack.scss';
						
		if ( ! file_put_contents( $scss_file, $scss ) ) {
			throw new \Exception( 'Could not write mixin file.' );
		}
		
	}
	
	/**
	 * Update and save the supplementary webBook stylesheet which adds global typography support.
	 *
	 * @param int $pid
	 * @param \WP_Post $post
	 */
	 
	static function updateWebBookStyleSheet( $pid = null, $post = null ) {
		
		if ( isset( $post ) && 'metadata' !== $post->post_type )
			return; // Bail
				
		$scss = "@import 'mixins';\n";

		$scss .= 'body { font-family: $body-font-stack-web; }';
		
		$wp_upload_dir = wp_upload_dir();

		$upload_dir = $wp_upload_dir['basedir'] . '/global-typography';

		$css_file = $upload_dir . '/global-typography.css';

		$css = \PressBooks\SASS\compile( $scss, array( 'load_paths' => array( PB_PLUGIN_DIR . 'assets/css/sass', PB_PLUGIN_DIR . 'assets/export/', $upload_dir, get_stylesheet_directory() ) ) );
		
		// Search for url("*"), url('*'), and url(*)
		$url_regex = '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i';
		$css = preg_replace_callback( $url_regex, function ( $matches ) use ( $upload_dir ) {

			$url = $matches[3];
			$filename = sanitize_file_name( basename( $url ) );

			if ( preg_match( '#^themes-book/pressbooks-book/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url ) ) {

				// Look for themes-book/pressbooks-book/fonts/*.otf (or .woff, or .ttf), update URL

				return "url(" . site_url( '/' ) . "themes-book/pressbooks-book/fonts/$filename)";

			}

			return $matches[0]; // No change

		}, $css );
		
		if ( ! file_put_contents( $css_file, $css ) ) {
			throw new \Exception( 'Could not write webBook stylesheet.' );
		}
	
	}
	
}