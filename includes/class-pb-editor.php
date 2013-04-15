<?php
/**
 * Contains PressBooks-specific additions to TinyMCE, specifically custom CSS classes.
 *
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks;

class Editor {

	/**
	 * Adds style select dropdown to MCE buttons array.
	 */
	function mce_buttons( $buttons ) {
		array_unshift( $buttons, 'styleselect' );
		return $buttons;
	}

	/**
	 * Adds PressBooks custom CSS classes to the style select dropdown initiated above.
	 */
	function mce_before_init_insert_formats( $init_array ) {  
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
				'block' => 'p',  
				'classes' => 'textbox',
				'wrapper' => false,
			),
			array(  
				'title' => 'Text Box (Shaded)',  
				'block' => 'p',  
				'classes' => 'textbox shaded',
				'wrapper' => false,
			),
			array(  
				'title' => 'Text Box (Caption)',  
				'block' => 'p',  
				'classes' => 'textbox-caption',
				'wrapper' => false,
			),
			array(  
				'title' => 'Pullquote',  
				'block' => 'p',  
				'classes' => 'pullquote',
				'wrapper' => false,
			),
			array(  
				'title' => 'Pullquote (Left)',  
				'block' => 'p',  
				'classes' => 'pullquote-left',
				'wrapper' => false,
			),
			array(  
				'title' => 'Pullquote (Right)',  
				'block' => 'p',  
				'classes' => 'pullquote-right',
				'wrapper' => false,
			),
		);  
		$init_array['style_formats'] = json_encode( $style_formats );  
		return $init_array;
	}

}
