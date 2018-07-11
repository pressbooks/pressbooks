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
				$attributions = get_post_meta( $attachment->ID, 'pb_attachment_attributions', TRUE );
				$title        = isset( $attributions['pb_attribution_title'] ) ? $attributions['pb_attribution_title'] : '';
				$author       = isset( $attributions['pb_attribution_author'] ) ? $attributions['pb_attribution_author'] : '';
				$url          = isset( $attributions['pb_attribution_title_url'] ) ? $attributions['pb_attribution_title_url'] : '';
				$license_meta = isset( $attributions['pb_attribution_license'] ) ? $attributions['pb_attribution_license'] : '';

				$media_attributions .= '<li>' . $title;
				$media_attributions .= ( $url ) ? ' by ' . '<a rel="dc:creator" href="' . $url . '" property="cc:attributionName">' . $author . '</a>' : ' by ' . $author;
				$media_attributions .= ' CC ' . '<a rel="license" href="' . ( new Licensing() )->getUrlForLicense( $license_meta ) . '">' . $license_meta . '</a>';
				$media_attributions .= '</li>';
			}
			$media_attributions .= '</ul>';
			$media_attributions .= '</div>';
		}

		return $content . $media_attributions;
	}
}