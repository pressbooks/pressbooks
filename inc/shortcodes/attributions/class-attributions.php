<?php
/**
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Shortcodes\Attributions;

use Pressbooks\Licensing;

class Attributions {

	/**
	 * @var Attributions
	 */
	static $instance = NULL;

	/**
	 * Function to init our class, set filters & hooks, set a singleton instance
	 *
	 * @return Attributions
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}

		return self::$instance;
	}

	/**
	 * @param Attributions $obj
	 */
	static public function hooks( Attributions $obj ) {
		add_shortcode( 'attributions', [ $obj, 'shortcodeHandler' ] );
		add_filter(
			'no_texturize_shortcodes',
			function ( $excluded_shortcodes ) {
				$excluded_shortcodes[] = 'attributions';

				return $excluded_shortcodes;
			}
		);
		// do_shortcode() is registered as a default filter on 'the_content' with a priority of 11.
		// We need to run $this->attributionsContent() after this, set to 12
		add_filter( 'the_content', [ $obj, 'attributionsContent' ], 12 );
	}

	/**
	 * Pre-process attributions shortcode
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	function shortcodeHandler( $atts, $content = '' ) {
		//todo: Make the shortcode do something cool with attributes
		return;
	}

	/**
	 * Post-process attributions shortcode
	 *
	 * @param $content
	 *
	 * @return string
	 */
	function attributionsContent( $content ) {
		global $post;
		$media_attributions = '';

		// get all post attachments
		$args        = [
			'post_type'      => 'attachment',
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'post_parent'    => $post->ID
		];
		$attachments = get_posts( $args );

		// get attachment attributions
		if ( $attachments ) {
			$media_attributions = '<div class="media-atttributions">';
			$media_attributions .= '<h3>Attributions</h3>';
			$media_attributions .= '<ul>';
			foreach ( $attachments as $attachment ) {
				$title   = get_post_meta( $attachment->ID, 'pb_attribution_title', TRUE );
				$author  = get_post_meta( $attachment->ID, 'pb_attribution_author', TRUE );
				$source  = get_post_meta( $attachment->ID, 'pb_attribution_title_url', TRUE );
				$license = get_post_meta( $attachment->ID, 'pb_attribution_license', TRUE );

				$media_attributions .= '<li>' . $title;
				$media_attributions .= ( $source ) ? ' by ' . '<a rel="dc:creator" href="' . $source . '" property="cc:attributionName">' . $author . '</a>' : ' by ' . $author;
				$media_attributions .= ' CC ' . '<a rel="license" href="' . ( new Licensing() )->getUrlForLicense( $license ) . '">' . $license . '</a>';

				$media_attributions .= '</li>';
			}
			$media_attributions .= '</ul>';
			$media_attributions .= '</div>';
		}

		return $content . $media_attributions;
	}
}