<?php
/**
 * Contains PressBooks-specific additions to TinyMCE, specifically custom CSS classes.
 *
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks;


class Editor {

	/**
	 * Adds style select dropdown to MCE buttons array.
	 */
	static function mceButtons( $buttons ) {

		$p = array_search( 'formatselect', $buttons );
		array_splice( $buttons, $p + 1, 0, 'styleselect' );

		return $buttons;
	}


	/**
	 * Adds stylesheet for MCE previewing.
	 */
	static function addEditorStyle() {
		add_editor_style();
	}


	/**
	 * Adds PressBooks custom CSS classes to the style select dropdown initiated above.
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
				'title' => 'Hanging Indent',
				'block' => 'p',
				'classes' => 'hanging-indent',
				'wrapper' => false,
			),
			array(
				'title' => 'No Indent',
				'block' => 'p',
				'classes' => 'no-indent',
				'wrapper' => false,
			),
			array(
				'title' => 'Text Box',
				'block' => 'div',
				'classes' => 'textbox',
				'wrapper' => false,
			),
			array(
				'title' => 'Text Box (Shaded)',
				'block' => 'div',
				'classes' => 'textbox shaded',
				'wrapper' => false,
			),
			array(
				'title' => 'Text Box (Caption)',
				'block' => 'div',
				'classes' => 'textbox-caption',
				'wrapper' => false,
			),
			array(
				'title' => 'Pullquote',
				'inline' => 'span',
				'classes' => 'pullquote',
				'wrapper' => false,
			),
			array(
				'title' => 'Pullquote (Left)',
				'inline' => 'span',
				'classes' => 'pullquote-left',
				'wrapper' => false,
			),
			array(
				'title' => 'Pullquote (Right)',
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
