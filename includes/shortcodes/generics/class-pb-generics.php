<?php
/**
 * @author   Pressbooks <code@pressbooks.com>
 * @license  GPLv2 (or any later version)
 */
namespace Pressbooks\Shortcodes\Generics;

class Generics {

	/**
	 * @var Generics - Static property to hold our singleton instance
	 */
	static $instance = false;

	/**
	 * @array Protected array of generic shortcodes as a key => value pair,
	 * where the key is the shortcode and the value is either a string (the tag)
	 * or an array of two strings (tag and class, respectively).
	 */
	protected $shortcodes = array(
		'blockquote' 	=> 'blockquote',
		'bold' 			=> 'strong',
		'code'			=> 'code',
		'em' 			=> 'em',
		'italics' 		=> 'em',
		'strong'		=> 'strong',
		'textbox'		=> array('div', 'textbox'),
	);

	function __construct() {}

	function shortcodeHandler( $atts, $content = '', $shortcode ) {
		$tag = $this->shortcodes[$shortcode];

		if ( ! $content ) { return ''; }
		$class = '';
		if ( is_array( $tag )  || ( is_array( $atts ) && array_key_exists('class', $atts) ) ) {
								$classnames = array();
								if( is_array( $tag ) ) {
										$classnames[] = $tag[1];
										$tag = $tag[0];
								}
								if( is_array( $atts ) && array_key_exists('class', $atts) ) {
										$classnames[] = $atts['class'];
								}
			$class = ' class="' . implode( ' ', $classnames ) . '"';
		}
		$content = wpautop( trim( $content ) );
		return '<' . $tag . $class . '>' . do_shortcode( $content ) . '</' . $tag . '>';
	}

	/**
	 * Adds shortcodes based on $self->generics.
	 */
	static function getInstance() {
		if ( ! self::$instance )
			self::$instance = new self;

		foreach ( self::$instance->shortcodes as $shortcode => $tag ) {
			add_shortcode( $shortcode, array( self::$instance, 'shortcodeHandler' ) );
		}

		return self::$instance;
	}


}
