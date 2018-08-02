<?php
/**
 * @author   Pressbooks <code@pressbooks.com>
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Shortcodes\Complex;

use Masterminds\HTML5;

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
		add_shortcode( 'image', [ $obj, 'imageShortCodeHandler' ] );
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
		if ( ! isset( $atts['id'] ) ) {
			return;
		}

		$atts = shortcode_atts(
			[
				'class' => null,
				'id' => null,
			],
			$atts,
			$shortcode
		);

		return sprintf(
			'<a id="%1$s"%2$s%3$s></a>',
			sanitize_title( $atts['id'] ),
			( isset( $atts['class'] ) ) ? sprintf( ' class="%s"', $atts['class'] ) : '',
			( $content ) ? sprintf( ' title="%s"', $content ) : ''
		);
	}

	/**
	 * Shortcode handler for [columns].
	 */
	public function columnsShortCodeHandler( $atts, $content = '', $shortcode ) {
		if ( ! $content ) {
			return;
		}

		$atts = shortcode_atts(
			[
				'class' => null,
				'count' => 2,
			],
			$atts,
			$shortcode
		);

		$classes = $atts['class'] ?? '';

		switch ( $atts['count'] ) {
			case 2:
				$classes .= ' twocolumn';
				break;
			case 3:
				$classes .= ' threecolumn';
				break;
			default:
				$classes .= ' twocolumn';
		}

		return sprintf(
			'<div class="%1$s">%2$s</div>',
			trim( $classes ),
			wpautop( trim( $content ) )
		);
	}

	/**
	 * Shortcode handler for [email].
	 */
	public function emailShortCodeHandler( $atts, $content = '', $shortcode ) {
		$atts = shortcode_atts(
			[
				'class' => null,
				'address' => null,
			],
			$atts,
			$shortcode
		);

		$address = $atts['address'] ?? $content;

		if ( ! is_email( $address ) ) {
			return;
		}

		if ( $address === $content ) {
			return sprintf(
				'<a href="mailto:%1$s"%2$s>%1$s</a>',
				antispambot( $address ),
				( isset( $atts['class'] ) ) ? sprintf( ' class="%s"', $atts['class'] ) : ''
			);
		} else {
			return sprintf(
				'<a href="mailto:%1$s"%2$s>%3$s</a>',
				antispambot( $address ),
				( isset( $atts['class'] ) ) ? sprintf( ' class="%s"', $atts['class'] ) : '',
				( $content ) ? $content : antispambot( $address )
			);
		}
	}

	/**
	 * Shortcode handler for [equation].
	 */
	public function equationShortCodeHandler( $atts, $content = '', $shortcode ) {
		if ( ! $content ) {
			return;
		}
		$atts = shortcode_atts( [
			'size' => 0,
			'color' => false,
			'background' => false,
		], $atts );
		return apply_filters(
			'the_content',
			sprintf(
				'[latex%1$s]%2$s[/latex]',
				sprintf(
					' size="%1$s" color="%2$s" background="%3$s"',
					$atts['size'],
					$atts['color'],
					$atts['background']
				),
				$content
			)
		);
	}

	/**
	 * Shortcode handler for [image].
	 */
	public function imageShortCodeHandler( $atts, $content = '', $shortcode ) {
		if ( ! $content ) {
			return;
		}
		$atts = shortcode_atts( [
			'caption' => null,
			'alt' => null,
		], $atts );

		$html5 = new HTML5(
			[
				'disable_html_ns' => true,
			]
		);
		$dom = $html5->loadHTML( $content );
		$tags = $dom->getElementsByTagName( 'img' );
		if ( empty( $tags ) ) {
			return;
		}
		$img = $tags[0];
		$classes = $img->getAttribute( 'class' );
		$id = preg_match( '/wp-image-([0-9]+)/i', $classes, $class_id );
		$attachment_id = ( isset( $class_id[1] ) ) ? absint( $class_id[1] ) : '';

		if ( isset( $atts['caption'] ) ) {
			$classes .= ' wp-caption';
			$figure = $dom->createElement( 'figure' );
			$figure->setAttribute( 'id', "attachment_$attachment_id" );
			$figure->setAttribute( 'style', sprintf(
				'width: %spx',
				$img->getAttribute( 'width' )
			) );
			$figure->setAttribute( 'class', $classes );
			$figcaption = $dom->createElement( 'figcaption' );
			$figcaption->setAttribute( 'class', 'wp-caption-text' );
			$figcaption->nodeValue = $atts['caption'];
			$figure->appendChild( $img );
			$figure->appendChild( $figcaption );
			$dom->appendChild( $figure );
		}
		if ( isset( $atts['alt'] ) ) {
			$img->setAttribute( 'alt', $atts['alt'] );
		}
		$content = $dom->saveHTML( $figure );
		return $content;
	}

	/**
	 * Shortcode handler for [media].
	 */
	public function mediaShortCodeHandler( $atts, $content = '', $shortcode ) {
		$atts = shortcode_atts( [
			'caption' => null,
			'src' => null,
		], $atts );
		$src = $atts['src'] ?? $content;
		$src = esc_url_raw( $src );
		if ( ! filter_var( $src, FILTER_VALIDATE_URL ) ) {
			return;
		}
		if ( $atts['caption'] ) {
			return sprintf(
				'<figure class="embed">%1$s<figcaption>%2$s</figcaption></figure>',
				str_replace( [ '<p>', '</p>' ], '', apply_filters( 'the_content', sprintf( '[embed src="%s"]', $src ) ) ),
				$atts['caption']
			);
		}
		return apply_filters( 'the_content', sprintf( '[embed src="%s"]', $src ) );
	}
}
