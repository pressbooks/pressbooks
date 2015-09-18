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
	 * Get supported languages.
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
	 * Get the path to the SCSS mixin which assigns the $global-typography variable.
	 *
	 * @return string
	 */
	 
	function getGlobalTypographyMixin() {
		
/*
		$wp_upload_dir = wp_upload_dir();

		$fullpath = $wp_upload_dir['basedir'] . '/global-typography/global-typography.scss';
						
		return $fullpath;
*/
	}


	/**
	 * Update and save the SCSS mixin which assigns the $global-typography variable.
	 */
	 
	static function updateGlobalTypographyMixin() {
				
		$languages = get_option( 'pressbooks_global_typography' );
		
		$scss = "// Global Typography\n";
		$scss .= "@import 'global-fonts';\n";
		
		$global_typography = '$global-font-stack: ';
		
		if ( $languages && is_array( $languages ) ) {
			foreach ( $languages as $language )	{
				switch ( $language ) {
					case 'grc': // Ancient Greek
						$scss .= "@include LangFontGreekAncient;\n";
						$global_typography .= "'SBL Greek', ";
						break;
					case 'ar': // Arabic
						$scss .= "@include LangFontArabicKufi;\n";
						$scss .= "@include LangFontArabicNaskh;\n";
						$global_typography .= "'Noto Kufi Arabic', 'Noto Naskh Arabic', ";
						break;
					case 'he': // Biblical Hebrew
						$scss .= "@include LangFontHebrewBiblical;\n";
						$global_typography .= "'SBL Hebrew', ";
						break;
					case 'zh_HANS': // Chinese (Simplified)
						$scss .= "@include LangFontChineseSimplified;\n";
						$global_typography .= "'Noto CJK SC', ";
						break;
					case 'zh_HANT': // Chinese (Simplified)
						$scss .= "@include LangFontChineseTraditional;\n";
						$global_typography .= "'Noto CJK TC', ";
						break;
					case 'cop': // Coptic
						$scss .= "@include LangFontCoptic;\n";
						$global_typography .= "'Antinoou', ";
						break;
					case 'ja': // Japanese
						$scss .= "@include LangFontJapanese;\n";
						$global_typography .= "'Noto CJK JP', ";
						break;
					case 'ko': // Korean
						$scss .= "@include LangFontKorean;\n";
						$global_typography .= "'Noto CJK KR', ";
						break;
					case 'syr': // Syriac
						$scss .= "@include LangFontSyriac;\n";
						$global_typography .= "'Noto Sans Syriac', ";
						break;
					case 'ta': // Tamil
						$scss .= "@include LangFontTamil;\n";
						$global_typography .= "'Noto Sans Tamil', ";
						break;
					case 'bo': // Tibetan
						$scss .= "@include LangFontTibetan;\n";
						$global_typography .= "'Noto Sans Tibetan', ";
						break;
				}
			}
			$global_typography = rtrim( $global_typography, ', ' );
			$global_typography .= ";\n";
		} else {
			$global_typography .= 'null;';
		}

		$scss .= $global_typography;
		
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
	
}