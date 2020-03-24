<?php
/**
 * @author   Pressbooks <code@pressbooks.com>
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Shortcodes\Complex;

use function \Pressbooks\Utility\do_shortcode_by_tags;
use function \Pressbooks\Utility\str_starts_with;
use PressbooksMix\Assets;

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
		add_shortcode( 'reveal-answer', [ $obj, 'revealAnswerShortCodeHandler' ] );
		add_shortcode( 'hidden-answer', [ $obj, 'hiddenAnswerShortCodeHandler' ] );
		add_action( 'init', [ $obj, 'hiddenAnswerScripts' ] );
	}

	/**
	 * Constructor; doesn't do much.
	 */
	public function __construct() {
	}

	/**
	 * Shortcode handler for [anchor].
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $shortcode Shortcode name.
	 *
	 * @return string
	 */
	public function anchorShortCodeHandler( $atts, $content, $shortcode ) {
		if ( ! isset( $atts['id'] ) ) {
			return '';
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
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $shortcode Shortcode name.
	 *
	 * @return string
	 */
	public function columnsShortCodeHandler( $atts, $content, $shortcode ) {
		if ( ! $content ) {
			return '';
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
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $shortcode Shortcode name.
	 *
	 * @return string
	 */
	public function emailShortCodeHandler( $atts, $content, $shortcode ) {
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
			return '';
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
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $shortcode Shortcode name.
	 *
	 * @return string
	 */
	public function equationShortCodeHandler( $atts, $content, $shortcode ) {
		if ( ! $content ) {
			return '';
		}
		$atts = shortcode_atts(
			[
				'size' => 0,
				'color' => false,
				'background' => false,
			], $atts
		);

		return do_shortcode_by_tags(
			sprintf(
				'<p>[latex%1$s]%2$s[/latex]' . "</p>\n",
				( $atts['size'] !== 0 )
				? sprintf(
					' size="%1$s" color="%2$s" background="%3$s"',
					$atts['size'],
					$atts['color'],
					$atts['background']
				)
				: sprintf(
					' color="%1$s" background="%2$s"',
					$atts['color'],
					$atts['background']
				),
				$content
			),
			[ 'latex' ]
		);
	}

	/**
	 * Shortcode handler for [media].
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 * @param string $shortcode Shortcode name.
	 *
	 * @return string
	 */
	public function mediaShortCodeHandler( $atts, $content, $shortcode ) {
		global $wp_embed;
		$atts = shortcode_atts(
			[
				'caption' => null,
				'src' => null,
			], $atts
		);
		$src = $atts['src'] ?? $content;
		if ( str_starts_with( $src, '“' ) || str_starts_with( $src, '”' ) || str_starts_with( $src, '‘' ) || str_starts_with( $src, '’' ) ) {
			$src = trim( $src, '“”‘’' ); // Trim fancy quotes, for MS-Word compatibility
		}
		$src = esc_url_raw( $src );
		if ( ! filter_var( $src, FILTER_VALIDATE_URL ) ) {
			return '';
		}
		// Can't use `do_shortcode_by_tags` because the default callback for 'embed' is `__return_false` - @see \WP_Embed::__construct
		$e = $wp_embed->run_shortcode( sprintf( '[embed src="%s"]', $src ) );
		if ( $atts['caption'] ) {
			return sprintf(
				'<figure class="embed">%1$s<figcaption>%2$s</figcaption></figure>',
				str_replace( [ '<p>', '</p>' ], '', $e ),
				$atts['caption']
			);
		} else {
			return $e;
		}
	}

	/**
	 * Shortcode handler for [reveal-answer].
	 *
	 * @param array $atts Shortcode attributes.
	 * @param string $content Shortcode content.
	 *
	 * @return string
	 */
	public function revealAnswerShortCodeHandler( $atts, $content = null) {
		$wrapper_style = 'display: block';
		$show_answer_style = 'cursor: pointer';

		$atts = shortcode_atts(
			array(
				'q' => 'default 1',
			), $atts
		);

		return '<div class="qa-wrapper" style="' . $wrapper_style . '"><span class="show-answer collapsed" style="' . $show_answer_style . '" data-target="q' . $atts['q'] . '">' . do_shortcode( $content ) . '</span>';
		}

	/**
	 * Shortcode that wraps around text that hides the answer.
	 * Ex: [hidden-answer a="1"]Show Answer[/hidden-answer].
	 */
	public function hiddenAnswerShortCodeHandler( $atts, $content = null ) {

		$hidden_answer_style = 'display: none';

		$atts = shortcode_atts(
			array(
				'a' => 'default 1',
			), $atts
		);

		return '<div id="q' . $atts['a'] . '" class="hidden-answer" style="' . $hidden_answer_style . '">' . do_shortcode( $content ) . '</div></div>';
	}

	/**
	 * Enqueues show/hide answer JavaScript
	 */
	public function hiddenAnswerScripts() {
		  $assets = new Assets( 'pressbooks', 'plugin' );
			wp_enqueue_script( 'hide-answers', $assets->getPath( 'scripts/hide-answer.js' ), array( 'jquery' ), '', true );
	}
}
