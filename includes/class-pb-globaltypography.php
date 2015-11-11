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
		
		$fullpath = get_stylesheet_directory() . '/_mixins.scss';
				
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
				
		$scss = "// Global Typography\n";
		
		$font_stacks = \PressBooks\GlobalTypography::getThemeFontStacks();

		$sans = $serif = false;
				
		if ( strpos( $font_stacks, '$sans-serif' ) !== false ) {
			$global_font_stack_sans_epub = '$sans-serif-epub: ';
			$global_font_stack_sans_prince = '$sans-serif-prince: ';
			$global_font_stack_sans_web = '$sans-serif-web: ';
			$sans = true;
		} else {
			$global_font_stack_sans_epub = '$sans-serif-epub: ';
			$global_font_stack_sans_prince = '$sans-serif-prince: ';
			$global_font_stack_sans_web = '$sans-serif-web: ';
		}
		 
		if ( strpos( $font_stacks, '$serif' ) !== false ) {
			$global_font_stack_serif_epub = '$serif-epub: ';
			$global_font_stack_serif_prince = '$serif-prince: ';
			$global_font_stack_serif_web = '$serif-web: ';
			$serif = true;
		} else {
			$global_font_stack_serif_epub = '$serif-epub: ';
			$global_font_stack_serif_prince = '$serif-prince: ';
			$global_font_stack_serif_web = '$serif-web: ';
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
		
		$includes_epub = array();
		$includes_prince = array();
		$includes_web = array();
				
		if ( !empty( $languages ) ) {
			foreach ( $languages as $language )	{
				switch ( $language ) {
					case 'grc': // Ancient Greek
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_epub[] = 'SBLGreekFont';
								$includes_prince[] = 'SBLGreekFont';
								$includes_web[] = 'SBLGreekFont';
								$global_font_stack_sans_epub .= "'SBL Greek', ";
								$global_font_stack_sans_prince .= "'SBL Greek', ";
								$global_font_stack_sans_web .= "'SBL Greek', ";
							}
							if ( $serif ) {
								$includes_epub[] = 'SBLGreekFont';
								$includes_prince[] = 'SBLGreekFont';
								$includes_web[] = 'SBLGreekFont';
								$global_font_stack_serif_epub .= "'SBL Greek', ";
								$global_font_stack_serif_prince .= "'SBL Greek', ";
								$global_font_stack_serif_web .= "'SBL Greek', ";
							}
						}
						break;
					case 'ar': // Arabic
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_epub[] = 'NotoKufiArabicFont';
								$includes_prince[] = 'NotoKufiArabicFont';
								$includes_web[] = 'NotoKufiArabicFont';
								$global_font_stack_sans_epub .= "'Noto Kufi Arabic', ";
								$global_font_stack_sans_prince .= "'Noto Kufi Arabic', ";
								$global_font_stack_sans_web .= "'Noto Kufi Arabic', ";
							}
							if ( $serif ) {
								$includes_epub[] = 'NotoNaskhArabicFont';
								$includes_prince[] = 'NotoNaskhArabicFont';
								$includes_web[] = 'NotoNaskhArabicFont';
								$global_font_stack_serif_epub .= "'Noto Naskh Arabic', ";
								$global_font_stack_serif_prince .= "'Noto Naskh Arabic', ";
								$global_font_stack_serif_web .= "'Noto Naskh Arabic', ";
							}
						}
						break;
					case 'he': // Biblical Hebrew
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_epub[] = 'SBLHebrewFont';
								$includes_prince[] = 'SBLHebrewFont';
								$includes_web[] = 'SBLHebrewFont';
								$global_font_stack_sans_epub .= "'SBL Hebrew', ";
								$global_font_stack_sans_prince .= "'SBL Hebrew', ";
								$global_font_stack_sans_web .= "'SBL Hebrew', ";
							}
							if ( $serif ) {
								$includes_epub[] = 'SBLHebrewFont';
								$includes_prince[] = 'SBLHebrewFont';
								$includes_web[] = 'SBLHebrewFont';
								$global_font_stack_serif_epub .= "'SBL Hebrew', ";
								$global_font_stack_serif_prince .= "'SBL Hebrew', ";
								$global_font_stack_serif_web .= "'SBL Hebrew', ";
							}
						}
						break;
					case 'zh_HANS': // Chinese (Simplified)
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_prince[] = 'NotoSansCJKSCFont';
								$includes_web[] = 'NotoSansCJKSCFont';
								$global_font_stack_sans_prince .= "'Noto Sans CJK SC', ";
								$global_font_stack_sans_web .= "'Noto Sans CJK SC', ";
							}
							if ( $serif ) {
								$includes_prince[] = 'NotoSansCJKSCFont';
								$includes_web[] = 'NotoSansCJKSCFont';
								$global_font_stack_serif_prince .= "'Noto Sans CJK SC', ";
								$global_font_stack_serif_web .= "'Noto Sans CJK SC', ";
							}
						}
						break;
					case 'zh_HANT': // Chinese (Simplified)
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_prince[] = 'NotoSansCJKTCFont';
								$includes_web[] = 'NotoSansCJKTCFont';
								$global_font_stack_sans_prince .= "'Noto Sans CJK TC', ";
								$global_font_stack_sans_web .= "'Noto Sans CJK TC', ";
							}
							if ( $serif ) {
								$includes_prince[] = 'NotoSansCJKTCFont';
								$includes_web[] = 'NotoSansCJKTCFont';
								$global_font_stack_serif_prince .= "'Noto Sans CJK TC', ";
								$global_font_stack_serif_web .= "'Noto Sans CJK TC', ";
							}
						}
						break;
					case 'cop': // Coptic
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_epub[] = 'NotoSansCopticFont';
								$includes_prince[] = 'NotoSansCopticFont';
								$includes_web[] = 'NotoSansCopticFont';
								$global_font_stack_sans_epub .= "'Noto Sans Coptic', ";
								$global_font_stack_sans_prince .= "'Noto Sans Coptic', ";
								$global_font_stack_sans_web .= "'Noto Sans Coptic', ";
							}
							if ( $serif ) {
								$includes_epub[] = 'AntinoouFont';
								$includes_prince[] = 'AntinoouFont';
								$includes_web[] = 'AntinoouFont';
								$global_font_stack_serif_epub .= "'Antinoou', ";
								$global_font_stack_serif_prince .= "'Antinoou', ";
								$global_font_stack_serif_web .= "'Antinoou', ";
							}
						}
						break;
					case 'gu': // Gujarati
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_epub[] = 'NotoSansGujaratiFont';
								$includes_prince[] = 'NotoSansGujaratiFont';
								$includes_web[] = 'NotoSansGujaratiFont';
								$global_font_stack_sans_epub .= "'Noto Sans Gujarati', ";
								$global_font_stack_sans_prince .= "'Noto Sans Gujarati', ";
								$global_font_stack_sans_web .= "'Noto Sans Gujarati', ";
							}
							if ( $serif ) {
								$includes_epub[] = 'EkatraFont';
								$includes_prince[] = 'EkatraFont';
								$includes_web[] = 'EkatraFont';
								$global_font_stack_serif_epub .= "'Ekatra', ";
								$global_font_stack_serif_prince .= "'Ekatra', ";
								$global_font_stack_serif_web .= "'Ekatra', ";
							}
						}
						break;
					case 'ja': // Japanese
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_prince[] = 'NotoSansCJKJPFont';
								$includes_web[] = 'NotoSansCJKJPFont';
								$global_font_stack_sans_prince .= "'Noto Sans CJK JP', ";
								$global_font_stack_sans_web .= "'Noto Sans CJK JP', ";
							}
							if ( $serif ) {
								$includes_prince[] = 'NotoSansCJKJPFont';
								$includes_web[] = 'NotoSansCJKJPFont';
								$global_font_stack_serif_prince .= "'Noto Sans CJK JP', ";
								$global_font_stack_serif_web .= "'Noto Sans CJK JP', ";
							}
						}
						break;
					case 'ko': // Korean
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_prince[] = 'NotoSansCJKKRFont';
								$includes_web[] = 'NotoSansCJKKRFont';
								$global_font_stack_sans_prince .= "'Noto Sans CJK KR', ";
								$global_font_stack_sans_web .= "'Noto Sans CJK KR', ";
							}
							if ( $serif ) {
								$includes_prince[] = 'NotoSansCJKKRFont';
								$includes_web[] = 'NotoSansCJKKRFont';
								$global_font_stack_serif_prince .= "'Noto Sans CJK KR', ";
								$global_font_stack_serif_web .= "'Noto Sans CJK KR', ";
							}
						}
						break;
					case 'syr': // Syriac
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_epub[] = 'NotoSansSyriacFont';
								$includes_prince[] = 'NotoSansSyriacFont';
								$includes_web[] = 'NotoSansSyriacFont';
								$global_font_stack_sans_epub .= "'Noto Sans Syriac', ";
								$global_font_stack_sans_prince .= "'Noto Sans Syriac', ";
								$global_font_stack_sans_web .= "'Noto Sans Syriac', ";
							}
							if ( $serif ) {
								$includes_epub[] = 'NotoSansSyriacFont';
								$includes_prince[] = 'NotoSansSyriacFont';
								$includes_web[] = 'NotoSansSyriacFont';
								$global_font_stack_serif_epub .= "'Noto Sans Syriac', ";
								$global_font_stack_serif_prince .= "'Noto Sans Syriac', ";
								$global_font_stack_serif_web .= "'Noto Sans Syriac', ";
							}
						}
						break;
					case 'ta': // Tamil
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_epub[] = 'NotoSansTamilFont';
								$includes_prince[] = 'NotoSansTamilFont';
								$includes_web[] = 'NotoSansTamilFont';
								$global_font_stack_sans_epub .= "'Noto Sans Tamil', ";
								$global_font_stack_sans_prince .= "'Noto Sans Tamil', ";
								$global_font_stack_sans_web .= "'Noto Sans Tamil', ";
							}
							if ( $serif ) {
								$includes_epub[] = 'NotoSansTamilFont';
								$includes_prince[] = 'NotoSansTamilFont';
								$includes_web[] = 'NotoSansTamilFont';
								$global_font_stack_serif_epub .= "'Noto Sans Tamil', ";
								$global_font_stack_serif_prince .= "'Noto Sans Tamil', ";
								$global_font_stack_serif_web .= "'Noto Sans Tamil', ";
							}
						}
						break;
					case 'bo': // Tibetan
						if ( !in_array( $language, $already_supported_languages ) ) {
							if ( $sans ) {
								$includes_epub[] = 'NotoSansTibetanFont';
								$includes_prince[] = 'NotoSansTibetanFont';
								$includes_web[] = 'NotoSansTibetanFont';
								$global_font_stack_sans_epub .= "'Noto Sans Tibetan', ";
								$global_font_stack_sans_prince .= "'Noto Sans Tibetan', ";
								$global_font_stack_sans_web .= "'Noto Sans Tibetan', ";
							}
							if ( $serif ) {
								$includes_epub[] = 'NotoSansTibetanFont';
								$includes_prince[] = 'NotoSansTibetanFont';
								$includes_web[] = 'NotoSansTibetanFont';
								$global_font_stack_serif_epub .= "'Noto Sans Tibetan', ";
								$global_font_stack_serif_prince .= "'Noto Sans Tibetan', ";
								$global_font_stack_serif_web .= "'Noto Sans Tibetan', ";
							}
						}
						break;
				}
			}
						
			$scss .= '@if $type == \'epub\' {' . "\n";
			
			$includes_epub = array_unique( $includes_epub );
			foreach ( $includes_epub as $include ) {
				$scss .= "@include $include;\n";
			}
			
			$scss .= '} @else if $type == \'prince\' {' . "\n";
						
			$includes_prince = array_unique( $includes_prince );
			foreach ( $includes_prince as $include ) {
				$scss .= "@include $include;\n";
			}

			$scss .= '} @else if $type == \'web\' {' . "\n";
			
			$includes_web = array_unique( $includes_web );
			foreach ( $includes_web as $include ) {
				$scss .= "@include $include;\n";
			}
			
			$scss .= "}\n";

		}
		
		$global_font_stack_sans_epub .= "sans-serif;\n";
		$global_font_stack_sans_prince .= "sans-serif;\n";
		$global_font_stack_sans_web .= "sans-serif;\n";
		$global_font_stack_serif_epub .= "serif;\n";
		$global_font_stack_serif_prince .= "serif;\n";
		$global_font_stack_serif_web .= "serif;\n";

		$scss .= $global_font_stack_sans_epub;
		$scss .= $global_font_stack_sans_prince;
		$scss .= $global_font_stack_sans_web;
		$scss .= $global_font_stack_serif_epub;
		$scss .= $global_font_stack_serif_prince;
		$scss .= $global_font_stack_serif_web;
		
		$wp_upload_dir = wp_upload_dir();

		$upload_dir = $wp_upload_dir['basedir'] . '/css/scss';

		if ( ! is_dir( $upload_dir ) ) {
			mkdir( $upload_dir, 0777, true );
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
	 * @throws \Exception
	 * @return void
	 */
	static function updateWebBookStyleSheet( $pid = null, $post = null ) {
		
		if ( isset( $post ) && 'metadata' !== $post->post_type )
			return; // Bail
				
		$path_to_style = realpath( get_stylesheet_directory() . '/style.scss' );
		
		if ( $path_to_style ) {
			$scss = file_get_contents( $path_to_style );
		} else {
			return;
		}
				
		$wp_upload_dir = wp_upload_dir();

		$upload_dir = $wp_upload_dir['basedir'] . '/css/scss';

		$css_file = $wp_upload_dir['basedir'] . '/css/style.css';

		// TODO: Catch exception, gracefully bail.
		// TODO: Consider moving this into SCSS module because includes are mostly known? We don't need to set them every time, just prepend the differences.
		$css = \PressBooks\SASS\compile( $scss, array( PB_PLUGIN_DIR . 'assets/scss/partials', $upload_dir, get_stylesheet_directory() ) );
		
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
			// TODO: This is used by 3 add_action() hooks, but a WP hook cannot catch an exception, so the app may crash
			throw new \Exception( 'Could not write webBook stylesheet.' );
		}
	
	}
	
}