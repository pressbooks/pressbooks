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
	static $instance = null;

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
				$all_attributions[ $attachment->ID ]['title']     = get_post_meta( $attachment->ID, 'pb_attribution_title', true );
				$all_attributions[ $attachment->ID ]['author']    = get_post_meta( $attachment->ID, 'pb_attribution_author', true );
				$all_attributions[ $attachment->ID ]['title_url'] = get_post_meta( $attachment->ID, 'pb_attribution_title_url', true );
				$all_attributions[ $attachment->ID ]['license']   = get_post_meta( $attachment->ID, 'pb_attribution_license', true );
			}
		}

		// get the content of the attributions
		$media_attributions = $this->attributionsContent( $all_attributions );

		return $content . $media_attributions;
	}

	/**
	 * Logic and markup for the attribution fields
	 *
	 * @param array $attributions
	 *
	 * @return string
	 */
	function attributionsContent( $attributions ) {
		$media_attributions = '';
		$html               = '';

		if ( $attributions ) {
			// generate appropriate markup for each field
			foreach ( $attributions as $attribution ) {

				// unset empty arrays
				$attribution = array_filter( $attribution, 'strlen' );

				// if we have enough arguments, use built in PB function
//				if ( count( $attribution ) === 4 ) {
//					$media_attributions .= sprintf( '<li>%s</li>',
//						( new Licensing() )->getLicense( $attribution['license'], $attribution['author'], $attribution['title_url'], $attribution['title'], '', false )
//					);
//					continue;
//				}

				// only process if non-empty
				if ( count( $attribution ) > 0 ) {
					$media_attributions .= sprintf( '<li>%1$s %2$s %3$s</li>',
						sprintf( '<a rel="cc:attributionURL" href="%1$s" property="dc:title">%2$s</a>',
							( isset( $attribution['title_url'] ) ) ? $attribution['title_url'] : '#',
							( isset( $attribution['title'] ) ) ? $attribution['title'] : '' ),
						sprintf( '%1$s<span property="cc:attributionName">%2$s</span>',
							( isset( $attribution['author'] ) ) ? ' by ' : '',
							( isset( $attribution['author'] ) ) ? $attribution['author'] . '.' : '' ),
						sprintf( '<a rel="license" href="%1$s">%2$s</a>',
							( isset( $attribution['license'] ) ) ? ( new Licensing() )->getUrlForLicense( $attribution['license'] ) : '#',
							( isset ( $attribution['license'] ) ) ? ( new Licensing() )->getNameForLicense( $attribution['license'] ) : '' )
					);
				}
			}
			if ( ! empty( $media_attributions ) ) {
				$html = sprintf( '<div class="media-atttributions"><h3>Media Attributions</h3><ul>%s</ul></div>', $media_attributions );
			}
		}

		return $html;
	}
}
