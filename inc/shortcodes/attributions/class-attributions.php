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
		add_filter( 'the_content', [ $obj, 'getAttributions' ], 12 );
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
	function getAttributions( $content ) {
		global $post;
		$all_attributions = [];

		// get all post attachments
		$args        = [
			'post_type'      => 'attachment',
			'posts_per_page' => - 1,
			'post_status'    => 'any',
			'post_parent'    => $post->ID
		];
		$attachments = get_posts( $args );

		// get attributions for each attachment
		if ( $attachments ) {
			foreach ( $attachments as $attachment ) {
				$all_attributions[ $attachment->ID ]['title']      = get_post_meta( $attachment->ID, 'pb_attribution_title', TRUE );
				$all_attributions[ $attachment->ID ]['author']     = get_post_meta( $attachment->ID, 'pb_attribution_author', TRUE );
				$all_attributions[ $attachment->ID ]['author_url'] = get_post_meta( $attachment->ID, 'pb_attribution_title_url', TRUE );
				$all_attributions[ $attachment->ID ]['license']    = get_post_meta( $attachment->ID, 'pb_attribution_license', TRUE );
			}
		}

		// get the content of the attributions
		$media_attributions = $this->attributionsContent( $all_attributions );

		return $content . $media_attributions;
	}

	/**
	 * Logic and markup for the attribution fields
	 *
	 * @param $attributions
	 *
	 * @return string
	 */
	function attributionsContent( $attributions ) {
		$media_attributions = '';
		// proceed if there's attributions
		if ( $attributions ) {
			// make sure there's at least one value in any of the attribution fields
			if ( ! empty( array_column( $attributions, 'title' ) ) || ! empty( array_column( $attributions, 'author' ) || ! empty( array_column( $attributions, 'license' ) ) ) ) {
				$media_attributions = '<div class="media-atttributions">';
				$media_attributions .= '<h3>Attributions</h3>';
				$media_attributions .= '<ul>';
				// loop through each attribution, generate appropriate markup for each field, if they aren't empty
				foreach ( $attributions as $attribution ) {
					$media_attributions .= '<li>';
					// attribution title
					$media_attributions .= ( ! empty( $attribution['title'] ) ? $attribution['title'] : '' );
					// attribution author without url
					$media_attributions .= ( ! empty( $attribution['author'] ) && empty( $attribution['author_url'] ) ) ? ' by ' . $attribution['author'] : '';
					// attribution author with url
					$media_attributions .= ( ! empty( $attribution['author'] ) && ! empty( $attribution['author_url'] ) ) ? ' by ' . '<a rel="dc:creator" href="' . $attribution['author_url'] . '" property="cc:attributionName">' . $attribution['author'] . '</a>' : '';
					// attribution license
					$media_attributions .= ( ! empty( $attribution['license'] ) ) ? ' CC ' . '<a rel="license" href="' . ( new Licensing() )->getUrlForLicense( $attribution['license'] ) . '">' . $attribution['license'] . '</a>' : '';
					$media_attributions .= '</li>';
				}
				$media_attributions .= '</ul>';
				$media_attributions .= '</div>';
			}
		}

		return $media_attributions;
	}
}