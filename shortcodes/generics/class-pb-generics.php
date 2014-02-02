<?php
/**
 * @author   PressBooks <code@pressbooks.com>
 * @license  GPLv2 (or any later version)
 */
namespace PressBooks\Shortcodes\Generics;

class Generics {

	/**
	 * @var Static property to hold our singleton instance
	 */
	static $instance = false;

	/**
	 * @array Protected array of generic shortcodes as a key => value pair,
	 where the key is the shortcode and the value is either a string (the tag)
	 or an array of two strings (tag and class, respectively).
	 */
	protected $generics = array(
		'blockquote' 	=> 'blockquote',
		'bold' 			=> 'strong',
		'code'			=> 'code',
		'em' 			=> 'em',
		'italics' 		=> 'em',
		'strong'		=> 'strong',
		'textbox'		=> array('div', 'textbox'),
	);
	
	/**
	 * This is our constructor, which is private to force the use of getInstance().
	 * We use an anonymous function to generate handler functions for all $generics.
	 */
	private function __construct() {
		
		foreach ( $this->generics as $shortcode => $tag ) {
			add_shortcode( $shortcode, function ( $atts, $content = '' ) use( $tag ) {
				if ( ! $content ) { return ''; }
				$class = '';
				if ( is_array( $tag ) ) {
					$class = ' class="' . $tag[1] . '"';
					$tag = $tag[0];
				}
				return '<' . $tag . $class . '>' . do_shortcode( $content ) . '</' . $tag . '>';
			} );
		}
	}

	/**
	 * Function to instantiate our class and make it a singleton
	 *
	 * @return Footnotes
	 */
	public static function getInstance() {
		if ( ! self::$instance )
			self::$instance = new self;

		return self::$instance;
	}

}