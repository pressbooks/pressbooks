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
			add_filter( 'the_content', [ $obj, 'getAttributions' ], 11 );
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
		global $id;
		$all_attributions = [];
		$media_in_page    = get_media_embedded_in_content( $content );

		// these are not the droids you're looking for
		if ( empty( $media_in_page ) ) {
			return $content;
		}

		// get all book attachments
		if ( self::$book_media ) {
			$media_ids = $this->extractIdFromMedia( $media_in_page );

			// intersect media_ids found in page with found in book
			$unique_ids = $this->intersectMediaIds( $media_ids, self::$book_media );
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

	/**
	 * @return void $book_media
	 */
	private static function setBookMedia() {
		$book_media = [];
		$args = [
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

	/**
	 * @param $media
	 *
	 * @return array
	 */
	private function extractIdFromMedia( $media ) {
		$result = [];
		if ( empty( $media ) ) {
			return $result;
		}

		// only look for images, for now
		foreach ( $media as $img ) {
			if ( ! preg_match_all( '/<img [^>]+>/', $img, $matches ) ) {
				continue;
			}
			preg_match( '/wp-image-([0-9]+)/i', $matches[0][0], $class_id );
			$attachment_id = ( isset( $class_id[1] ) ) ? absint( $class_id[1] ) : '';

			preg_match( '/src=[\'"](.*?)[\'"]/i', $matches[0][0], $source );
			$attachment_url = $source[1];

			$result[ $attachment_id ] = $attachment_url;
		}

		return $result;
	}

	/**
	 *
	 * @param $media_ids_in_page
	 * @param $media_ids_found_in_book
	 *
	 * @return array
	 */
	private function intersectMediaIds( $media_ids_in_page, $media_ids_found_in_book ) {
		$ids   = [];
		$found = array_intersect_key( $media_ids_in_page, $media_ids_found_in_book );

		foreach ( $found as $k => $v ) {
			$src       = wp_parse_url( $v );
			$guid      = wp_parse_url( $media_ids_found_in_book[ $k ] );
			$src_info  = pathinfo( $src['path'] );
			$guid_info = pathinfo( $guid['path'] );

			// must be from the same host
			if ( 0 !== strcmp( $src['host'], $guid['host'] ) ) {
				continue;
			}
			// must be same file extension
			if ( 0 !== strcmp( $src_info['extension'], $guid_info['extension'] ) ) {
				continue;
			}
			// must have same directory
			if ( 0 !== strcmp( $src_info['dirname'], $guid_info['dirname'] ) ) {
				continue;
			}

			$ids[] = $k;

		}

		return $ids;
	}

}
