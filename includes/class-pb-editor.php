<?php
/**
 * Contains Pressbooks-specific additions to TinyMCE, specifically custom CSS classes.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks;


class Editor {	

	/**
	 * Ensure that Word formatting that we like doesn't get filtered out.
	 */
	
	static function mceValidWordElements( $init_array ) {

		$init_array['paste_word_valid_elements'] = '@[class],p,h3,h4,h5,h6,a[href|target],strong/b,em/i,div[align],br,table,tbody,thead,tr,td,ul,ol,li,img[src]';
		
		return $init_array;
		
	}

	/**
	 * Adds style select dropdown to MCE buttons array.
	 */
	static function mceButtons( $buttons ) {

		$p = array_search( 'formatselect', $buttons );
		array_splice( $buttons, $p + 1, 0, 'styleselect' );

		return $buttons;
	}

	/**
	 * Updates custom stylesheet for MCE previewing.
	 */
	static function updateEditorStyle() {
		
		$scss = "/* Editor Styles */\n";
				
		$body_font_stack = 'body { font-family: Georgia, "Times New Roman", "Bitstream Charter", Times, $foreign-language-fonts, serif; }';
		
		$foreign_languages = get_option( 'pressbooks_foreign_language_typography' );
		
		$foreign_language_fonts = '$foreign-language-fonts: ';
		
		if ( !isset( $foreign_languages ) ) {
			$foreign_languages = array();
		}
		foreach ( $foreign_languages as $language )	{
			switch ( $language ) {
				case 'ar': // Arabic
					$scss .= "@import 'foreign-language-fonts';
					@include LangFontArabicKufi;
					@include LangFontArabicNaskh;\n";
					$foreign_language_fonts .= "'Noto Kufi Arabic', 'Noto Naskh Arabic', ";
					break;
				case 'he': // Biblical Hebrew
					$scss .= "@import 'foreign-language-fonts';
					@include LangFontHebrewBiblical;\n";
					$foreign_language_fonts .= "'SBL Hebrew', ";
					break;
				case 'zh_HANS': // Chinese (Simplified)
					$scss .= "@import 'foreign-language-fonts';
					@include LangFontChineseSimplified;\n";
					$foreign_language_fonts .= "'Noto CJK SC', ";
					break;
				case 'zh_HANT': // Chinese (Simplified)
					$scss .= "@import 'foreign-language-fonts';
					@include LangFontChineseTraditional;\n";
					$foreign_language_fonts .= "'Noto CJK TC', ";
					break;
				case 'grc': // Classical Greek
					$scss .= "@import 'foreign-language-fonts';
					@include LangFontGreekAncient;\n";
					$foreign_language_fonts .= "'SBL Greek', ";
					break;
				case 'cop': // Coptic
					$scss .= "@import 'foreign-language-fonts';
					@include LangFontCoptic;\n";
					$foreign_language_fonts .= "'Antinoou', ";
					break;
				case 'ja': // Japanese
					$scss .= "@import 'foreign-language-fonts';
					@include LangFontJapanese;\n";
					$foreign_language_fonts .= "'Noto CJK JP', ";
					break;
				case 'ko': // Korean
					$scss .= "@import 'foreign-language-fonts';
					@include LangFontKorean;\n";
					$foreign_language_fonts .= "'Noto CJK KR', ";
					break;
				case 'syr': // Syriac
					$scss .= "@import 'foreign-language-fonts';
					@include LangFontSyriac;\n";
					$foreign_language_fonts .= "'Noto Sans Syriac', ";
					break;
				case 'ta': // Tamil
					$scss .= "@import 'foreign-language-fonts';
					@include LangFontTamil;\n";
					$foreign_language_fonts .= "'Noto Sans Tamil', ";
					break;
				case 'bo': // Tibetan
					$scss .= "@import 'foreign-language-fonts';
					@include LangFontTibetan;\n";
					$foreign_language_fonts .= "'Noto Sans Tibetan', ";
					break;
			}
		}
		
		$book_lang = \PressBooks\Book::getBookInformation();
		$book_lang = @$book_lang['pb_language'];
		
		switch ( $book_lang ) {
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
				$scss .= "";
				break;
			case 'he': // Biblical Hebrew
				$scss .= "";
				break;
			case 'zh': // Chinese
			case 'zh-hk':
			case 'zh-cn':
			case 'zh-sg':
			case 'zh-tw':
				$scss .= "";
				break;
			case 'ja': // Japanese
				$scss .= "";
				break;
			case 'ta': // Tamil
				$scss .= "";
				break;
		}
		
		$foreign_language_fonts = rtrim( $foreign_language_fonts, ', ' );
		$foreign_language_fonts .= ";\n";
		$scss .= $foreign_language_fonts;
		$scss .= $body_font_stack;
		$scss .= "@import '" . PB_PLUGIN_DIR . "assets/css/sass/_editor';\n";
						
		$wp_upload_dir = wp_upload_dir();

		$upload_dir = $wp_upload_dir['basedir'] . '/editor';

		if ( ! is_dir( $upload_dir ) ) {
			mkdir( $upload_dir );
		}
		
		if ( ! is_dir( $upload_dir ) ) {
			throw new \Exception( 'Could not create stylesheet directory.' );
		}
					
		$css_file = $upload_dir . '/editor.css';

		$css = \PressBooks\SASS\compile( $scss );
						
		if ( ! file_put_contents( $css_file, $css ) ) {
			throw new \Exception( 'Could not write custom CSS file.' );
		}
		
	}	

	/**
	 * Adds stylesheet for MCE previewing.
	 */
	static function addEditorStyle() {
		
		$wp_upload_dir = wp_upload_dir();
		
		$path = $wp_upload_dir['basedir'] . '/editor/editor.css';
		$uri = $wp_upload_dir['baseurl'] . '/editor/editor.css';

		if ( !is_file( $path ) ) {
			\PressBooks\Editor::updateEditorStyle();
		}
		
		add_editor_style( $uri );
		
	}


	/**
	 * Adds Pressbooks custom CSS classes to the style select dropdown initiated above.
	 */
	static function mceBeforeInitInsertFormats( $init_array ) {

		$style_formats = array(
			array(
				'title' => 'Indent',
				'block' => 'p',
				'classes' => 'indent',
				'wrapper' => false,
			),
			array(
				'title' => 'Hanging indent',
				'block' => 'p',
				'classes' => 'hanging-indent',
				'wrapper' => false,
			),
			array(
				'title' => 'No indent',
				'block' => 'p',
				'classes' => 'no-indent',
				'wrapper' => false,
			),
			array(
				'title' => 'Tight tracking',
				'block' => 'span',
				'classes' => 'tight',
				'wrapper' => false,
			),
			array(
				'title' => 'Very tight tracking',
				'block' => 'span',
				'classes' => 'very-tight',
				'wrapper' => false,
			),
			array(
				'title' => 'Loose tracking',
				'block' => 'span',
				'classes' => 'loose',
				'wrapper' => false,
			),
			array(
				'title' => 'Very loose tracking',
				'block' => 'span',
				'classes' => 'very-loose',
				'wrapper' => false,
			),
			array(
				'title' => 'Text box',
				'block' => 'div',
				'classes' => 'textbox',
				'wrapper' => false,
			),
			array(
				'title' => 'Text box (shaded)',
				'block' => 'div',
				'classes' => 'textbox shaded',
				'wrapper' => false,
			),	
			array(
				'title' => 'Pullquote (left)',
				'inline' => 'span',
				'classes' => 'pullquote-left',
				'wrapper' => false,
			),
			array(
				'title' => 'Pullquote (right)',
				'inline' => 'span',
				'classes' => 'pullquote-right',
				'wrapper' => false,
			),
		);

		$init_array['style_formats'] = json_encode( $style_formats );

		return $init_array;
	}


	/**
	 * We don't support "the kitchen sink" when using the custom metadata plugin,
	 * render the WYSIWYG editor accordingly.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	static function metadataManagerDefaultEditorArgs( $args ) {

		// Precedence when using the + operator to merge arrays is from left to right

		$args = array(
			'media_buttons' => false,
			'tinymce' => array(
				'theme_advanced_buttons1' => 'bold,italic,underline,strikethrough,|,link,unlink,|,numlist,bullist,|,undo,redo,pastetext,pasteword,|',
				'theme_advanced_buttons2' => '',
				'theme_advanced_buttons3' => ''
			)
		) + $args;

		return $args;
	}

}
