<?php
/**
 * @author   Pressbooks <code@pressbooks.com>
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Shortcodes\Generics;

class Generics {

	/**
	 * @var Generics - Static property to hold our singleton instance
	 */
	static $instance = null;

	/**
	 * @array Protected array of generic shortcodes as a key => value pair,
	 * where the key is the shortcode and the value is either a string (the tag)
	 * or an array of two strings (tag and class, respectively).
	 */
	protected $blockShortcodes = [
		'heading' => 'h1',
		'subheading' => 'h2',
	];

	/**
	 * @array Protected array of generic shortcodes as a key => value pair,
	 * where the key is the shortcode and the value is either a string (the tag)
	 * or an array of two strings (tag and class, respectively).
	 */
	protected $multilineBlockShortcodes = [
		'blockquote' => 'blockquote',
		'textbox' => [ 'div', 'textbox' ],
	];

	/**
	 * @array Protected array of generic shortcodes as a key => value pair,
	 * where the key is the shortcode and the value is either a string (the tag)
	 * or an array of two strings (tag and class, respectively).
	 */
	protected $inlineShortcodes = [
		'bold' => 'strong',
		'code' => 'code',
		'em' => 'em',
		'italics' => 'em',
		'strong' => 'strong',
	];

	/**
	 * Adds shortcodes based on $self->generics.
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	public static function hooks( Generics $obj ) {
		foreach ( $obj->blockShortcodes as $shortcode => $tag ) {
			add_shortcode( $shortcode, [ $obj, 'blockShortcodeHandler' ] );
		}
		foreach ( $obj->multilineBlockShortcodes as $shortcode => $tag ) {
			add_shortcode( $shortcode, [ $obj, 'multilineBlockShortcodeHandler' ] );
		}
		foreach ( $obj->inlineShortcodes as $shortcode => $tag ) {
			add_shortcode( $shortcode, [ $obj, 'inlineShortcodeHandler' ] );
		}
	}

	public function __construct() {
	}

	public function blockShortcodeHandler( $atts, $shortcode, $content = '' ) {
		$tag = $this->blockShortcodes[ $shortcode ];

		if ( ! $content ) {
			return '';
		}
		$class = '';
		if ( is_array( $tag ) || ( is_array( $atts ) && array_key_exists( 'class', $atts ) ) ) {
			$classnames = [];
			if ( is_array( $tag ) ) {
				$classnames[] = $tag[1];
				$tag = $tag[0];
			}
			if ( is_array( $atts ) && array_key_exists( 'class', $atts ) ) {
				$classnames[] = $atts['class'];
			}
			$class = ' class="' . implode( ' ', $classnames ) . '"';
		}
		$content = trim( $content );
		return '<' . $tag . $class . '>' . do_shortcode( $content ) . '</' . $tag . '>';
	}

	public function multilineBlockShortcodeHandler( $atts, $shortcode, $content = '' ) {
		$tag = $this->multilineBlockShortcodes[ $shortcode ];

		if ( ! $content ) {
			return '';
		}
		$class = '';
		if ( is_array( $tag ) || ( is_array( $atts ) && array_key_exists( 'class', $atts ) ) ) {
			$classnames = [];
			if ( is_array( $tag ) ) {
				$classnames[] = $tag[1];
				$tag = $tag[0];
			}
			if ( is_array( $atts ) && array_key_exists( 'class', $atts ) ) {
				$classnames[] = $atts['class'];
			}
			$class = ' class="' . implode( ' ', $classnames ) . '"';
		}
		$content = wpautop( trim( $content ) );
		return '<' . $tag . $class . '>' . do_shortcode( $content ) . '</' . $tag . '>';
	}

	public function inlineShortcodeHandler( $atts, $shortcode, $content = '' ) {
		$tag = $this->inlineShortcodes[ $shortcode ];

		if ( ! $content ) {
			return '';
		}
		$class = '';
		if ( is_array( $tag ) || ( is_array( $atts ) && array_key_exists( 'class', $atts ) ) ) {
			$classnames = [];
			if ( is_array( $atts ) && array_key_exists( 'class', $atts ) ) {
				$classnames[] = $atts['class'];
			}
			$class = ' class="' . implode( ' ', $classnames ) . '"';
		}
		return '<' . $tag . $class . '>' . do_shortcode( $content ) . '</' . $tag . '>';
	}
}
