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
	 * @array Protected array of generic shortcodes.
	 */
	protected $generics = array( 'blockquote', 'em', 'sup' );

	/**
	 * This is our constructor, which is private to force the use of getInstance().
	 * We use an anonymous function to generate handler functions for all $generics.
	 */
	private function __construct() {
		
		foreach ( $this->generics as $shortcode )
			add_shortcode( $shortcode, function ( $atts, $content = '', $shortcode ) {
				if ( ! $content ) { return ''; }
				return '<' . $shortcode . '>' . do_shortcode( $content ) . '</' . $shortcode . '>';
			} );
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



