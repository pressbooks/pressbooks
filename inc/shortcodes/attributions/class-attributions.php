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
		add_filter( 'the_content', [ $obj, 'getAttributions' ], 11 );
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
		// don't show unless user options
		$options = get_option( 'pressbooks_theme_options_global' );
		if ( 1 !== $options['attachment_attributions'] ) {
			return $content;
		}

		global $id;
		$all_attributions = [];

		$attachments = get_attached_media( '', $id );

		// get attribution meta for each attachment
		if ( $attachments ) {
			foreach ( $attachments as $attachment ) {
				$all_attributions[ $attachment->ID ]['title']      = get_post_meta( $attachment->ID, 'pb_attribution_title', true );
				$all_attributions[ $attachment->ID ]['author']     = get_post_meta( $attachment->ID, 'pb_attribution_author', true );
				$all_attributions[ $attachment->ID ]['title_url'] = get_post_meta( $attachment->ID, 'pb_attribution_title_url', true );
				$all_attributions[ $attachment->ID ]['license']    = get_post_meta( $attachment->ID, 'pb_attribution_license', true );
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
		$html               = '';
		// proceed if there's attributions
		if ( $attributions ) {
			// loop through each attribution, generate appropriate markup for each field
			foreach ( $attributions as $attribution ) {
				// only process non-empty values
				$attribution = array_filter( $attribution, 'strlen' );
				if ( count( $attribution ) > 0 ) {
					$media_attributions .= '<li>';
					// attribution title
					$media_attributions .= ( ! empty( $attribution['title'] ) ? $attribution['title'] : '' );
					// attribution author without url
					$media_attributions .= ( ! empty( $attribution['author'] ) && empty( $attribution['title_url'] ) ) ? ' by ' . $attribution['author'] : '';
					// attribution author with url
					$media_attributions .= ( ! empty( $attribution['author'] ) && ! empty( $attribution['title_url'] ) ) ? ' by ' . '<a rel="dc:creator" href="' . $attribution['title_url'] . '" property="cc:attributionName">' . $attribution['author'] . '</a>' : '';
					// attribution license
					$media_attributions .= ( ! empty( $attribution['license'] ) ) ? ' ' . '<a rel="license" href="' . ( new Licensing() )->getUrlForLicense( $attribution['license'] ) . '">' . ( new Licensing() )->getNameForLicense( $attribution['license'] ). '</a>' : '';
					$media_attributions .= '</li>';
				}
			}

			if ( ! empty( $media_attributions ) ) {
				$html .= '<div class="media-atttributions">';
				$html .= '<h3>Media Attributions</h3>';
				$html .= '<ul>';
				$html .= $media_attributions;
				$html .= '</ul></div>';
			}

		}

		return $html;
	}
}
