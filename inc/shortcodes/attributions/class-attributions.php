<?php
/**
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Shortcodes\Attributions;

use Pressbooks\Licensing;
use Pressbooks\Media;

class Attributions {

	/**
	 * @var Attributions
	 */
	static $instance = NULL;

	/**
	 * @var array
	 */
	static $book_media = [];

	/**
	 * Function to init our class, set filters & hooks, set a singleton instance
	 *
	 * @return Attributions
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
			self::setBookMedia();
		}

		return self::$instance;
	}

	/**
	 * @param Attributions $obj
	 */
	static public function hooks( Attributions $obj ) {

		add_shortcode( 'media_attributions', [ $obj, 'shortcodeHandler' ] );

		// prevent further processing of formatted strings
		add_filter(
			'no_texturize_shortcodes',
			function ( $excluded_shortcodes ) {
				$excluded_shortcodes[] = 'media_attributions';

				return $excluded_shortcodes;
			}
		);

		// add img tag when searching for media
		add_filter( 'media_embedded_in_content_allowed_types', function ( $allowed_media_types ) {
			if ( ! in_array( 'img', $allowed_media_types ) ) {
				array_push( $allowed_media_types, 'img' );
			}

			return $allowed_media_types;
		} );

		// don't show unless user options
		$options = get_option( 'pressbooks_theme_options_global' );

		if ( 1 === $options['attachment_attributions'] ) {
			add_filter( 'the_content', [ $obj, 'getAttributions' ], 12 );
		}

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
		$all_attributions = [];
		$media_in_page    = get_media_embedded_in_content( $content );

		// these are not the droids you're looking for
		if ( empty( $media_in_page ) ) {
			return $content;
		}

		// get all book attachments
		if ( self::$book_media ) {
			$media_ids = Media\extract_id_from_media( $media_in_page );

			// intersect media_ids found in page with found in book
			$unique_ids = Media\intersect_media_ids( $media_ids, self::$book_media );
		} else {
			return $content;
		}

		// get attribution meta for each attachment
		if ( $unique_ids ) {
			foreach ( $unique_ids as $id ) {
				$all_attributions[ $id ]['title']     = get_post_meta( $id, 'pb_media_attribution_title', true );
				$all_attributions[ $id ]['author']    = get_post_meta( $id, 'pb_media_attribution_author', true );
				$all_attributions[ $id ]['title_url'] = get_post_meta( $id, 'pb_media_attribution_title_url', true );
				$all_attributions[ $id ]['license']   = get_post_meta( $id, 'pb_media_attribution_license', true );
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
		$licensing          = new Licensing();
		$supported          = $licensing->getSupportedTypes();

		if ( $attributions ) {
			// generate appropriate markup for each field
			foreach ( $attributions as $attribution ) {

				// unset empty arrays
				$attribution = array_filter( $attribution, 'strlen' );

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
							( isset( $attribution['license'] ) ) ? $licensing->getUrlForLicense( $attribution['license'] ) : '#',
							( isset ( $attribution['license'] ) ) ? $supported[ $attribution['license'] ]['desc'] : '' )
					);
				}
			}
			if ( ! empty( $media_attributions ) ) {
				$html = sprintf( '<div class="media-atttributions"><h3>Media Attributions</h3><ul>%s</ul></div>', $media_attributions );
			}
		}

		return $html;
	}

	/**
	 * @return void $book_media
	 */
	private static function setBookMedia() {
		$book_media = [];
		$args       = [
			'post_type'      => 'attachment',
			'posts_per_page' => - 1,
			'post_status'    => 'inherit',
		];

		$attached_media = get_posts( $args );

		foreach ( $attached_media as $media ) {
			$book_media[ $media->ID ] = $media->guid;
		}

		self::$book_media = $book_media;
	}

}
