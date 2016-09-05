<?php
/**
 * @author   Pressbooks <code@pressbooks.com>
 * @license  GPLv2 (or any later version)
 */
namespace Pressbooks\Shortcodes\Generics;

class Generics {

	/**
	 * @array Protected array of generic shortcodes as a key => value pair,
	 * where the key is the shortcode and the value is either a string (the tag)
	 * or an array of two strings (tag and class, respectively).
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
	 * Adds shortcodes based on $self->generics.
	 */
	public static function init() {
		$self = new self();

		foreach ( $self->generics as $shortcode => $tag ) {
			add_shortcode( $shortcode, function ( $atts, $content = '' ) use( $tag ) {
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
			} );
		}
	}
}
