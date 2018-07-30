<?php
/**
 * @author   Pressbooks <code@pressbooks.com>
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Shortcodes\Complex;

class Complex {

	/**
	 * @var Complex - Static property to hold our singleton instance.
	 */
	static $instance = null;

	/**
	 * Adds shortcodes based on $self->complex.
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * Shortcode registration hooks.
	 *
	 * @param Complex $obj
	 */
	static public function hooks( Complex $obj ) {
		add_shortcode( 'anchor', [ $obj, 'anchorShortCodeHandler' ] );
		add_shortcode( 'columns', [ $obj, 'columnsShortCodeHandler' ] );
		add_shortcode( 'email', [ $obj, 'emailShortCodeHandler' ] );
		add_shortcode( 'equation', [ $obj, 'equationShortCodeHandler' ] );
		add_shortcode( 'media', [ $obj, 'mediaShortCodeHandler' ] );
	}

	/**
	 * Constructor; doesn't do much.
	 */
	public function __construct() {
	}

	/**
	 * Shortcode handler for [anchor].
	 */
	public function anchorShortCodeHandler( $atts, $content = '', $shortcode ) {
		return $content; // TODO: Build the shortcode.
	}

	/**
	 * Shortcode handler for [columns].
	 */
	public function columnsShortCodeHandler( $atts, $content = '', $shortcode ) {
		return $content; // TODO: Build the shortcode.
	}

	/**
	 * Shortcode handler for [email].
	 */
	public function emailShortCodeHandler( $atts, $content = '', $shortcode ) {
		return $content; // TODO: Build the shortcode.
	}

	/**
	 * Shortcode handler for [equation].
	 */
	public function equationShortCodeHandler( $atts, $content = '', $shortcode ) {
		return $content; // TODO: Build the shortcode.
	}

	/**
	 * Shortcode handler for [media].
	 */
	public function mediaShortCodeHandler( $atts, $content = '', $shortcode ) {
		return $content; // TODO: Build the shortcode.
	}
}
