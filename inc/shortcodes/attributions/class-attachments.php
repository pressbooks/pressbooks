<?php
/**
 * @author   Brad Payne, Alex Paredes
 * @license  GPLv3 (or any later version)
 */

namespace Pressbooks\Shortcodes\Attributions;

use Pressbooks\Licensing;
use Pressbooks\Media;

class Attachments {

	const SHORTCODE = 'media_attributions';

	/**
	 * @var Attachments
	 */
	static $instance = null;

	/**
	 * Function to init our class, set filters & hooks, set a singleton instance
	 *
	 * @since 5.5.0
	 *
	 * @return Attachments
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}

		return self::$instance;
	}

	/**
	 * Hooks our bits into the machine
	 *
	 * @since 5.5.0
	 *
	 * @param Attachments $obj
	 */
	static public function hooks( Attachments $obj ) {

		add_shortcode( self::SHORTCODE, [ $obj, 'shortcodeHandler' ] );

		// prevent further processing of formatted strings
		add_filter(
			'no_texturize_shortcodes',
			function ( $excluded_shortcodes ) {
				$excluded_shortcodes[] = Attachments::SHORTCODE;

				return $excluded_shortcodes;
			}
		);

		// add img tag when searching for media
		add_filter(
			'media_embedded_in_content_allowed_types', function ( $allowed_media_types ) {
				if ( ! in_array( 'img', $allowed_media_types, true ) ) {
					array_push( $allowed_media_types, 'img' );
				}

				return $allowed_media_types;
			}
		);

		// don't show unless user options
		$options = get_option( 'pressbooks_theme_options_global' );
		// check it's set
		if ( isset( $options['attachment_attributions'] ) ) {
			// check it's turned on
			if ( 1 === $options['attachment_attributions'] ) {
				add_filter( 'the_content', [ $obj, 'getAttributions' ], 12 );
			}
		}

	}

	/**
	 * Returns the array of attachments set in the instance variable.
	 *
	 * @param bool $reset (optional, default is false)
	 *
	 * @since 5.5.0
	 *
	 * @return array|null
	 */
	function getBookMedia( $reset = false ) {
		// Cheap cache
		static $book_media = null;
		if ( $reset || $book_media === null ) {
			$book_media = [];
			$args = [
				'post_type' => 'attachment',
				'posts_per_page' => -1,  // @codingStandardsIgnoreLine
				'post_status' => 'inherit',
				'no_found_rows' => true,
			];

			$attached_media = get_posts( $args );

			foreach ( $attached_media as $media ) {
				$book_media[ $media->ID ] = $media->guid;
			}
		}
		return $book_media;
	}

	/**
	 * Produces a list of media attributions if they are
	 * found in the current page and part of the media library
	 * appends said list to the end of the content
	 *
	 * @since 5.5.0
	 *
	 * @param $content
	 *
	 * @return string
	 */
	function getAttributions( $content ) {
		$media_in_page = get_media_embedded_in_content( $content );

		// these are not the droids you're looking for
		if ( empty( $media_in_page ) ) {
			return $content;
		}

		// get all book attachments
		$book_media = $this->getBookMedia();
		if ( ! empty( $book_media ) ) {
			$media_ids = Media\extract_id_from_media( $media_in_page );
			// intersect media_ids found in page with found in book
			$unique_ids = Media\intersect_media_ids( $media_ids, $book_media );
		} else {
			return $content;
		}

		// get attribution meta for each attachment
		$all_attributions = $this->getAttributionsMeta( $unique_ids );

		// get the content of the attributions
		$media_attributions = $this->attributionsContent( $all_attributions );

		return $content . $media_attributions;
	}

	/**
	 * Returns the gamut of attribution metadata that can be associated with an
	 * attachment
	 *
	 * @param array $ids of attachments
	 *
	 * @return array all attribution metadata
	 */
	public function getAttributionsMeta( $ids ) {
		$all_attributions = [];

		if ( $ids ) {
			foreach ( $ids as $id ) {
				$all_attributions[ $id ]['title'] = get_the_title( $id );
				$all_attributions[ $id ]['title_url'] = get_post_meta( $id, 'pb_media_attribution_title_url', true );
				$all_attributions[ $id ]['author'] = get_post_meta( $id, 'pb_media_attribution_author', true );
				$all_attributions[ $id ]['author_url'] = get_post_meta( $id, 'pb_media_attribution_author_url', true );
				$all_attributions[ $id ]['adapted'] = get_post_meta( $id, 'pb_media_attribution_adapted', true );
				$all_attributions[ $id ]['adapted_url'] = get_post_meta( $id, 'pb_media_attribution_adapted_url', true );
				$all_attributions[ $id ]['license'] = get_post_meta( $id, 'pb_media_attribution_license', true );
			}
		}

		return $all_attributions;
	}

	/**
	 * Produces an html blob of attribution statements for the array
	 * of attachment ids it's given.
	 *
	 * @since 5.5.0
	 *
	 * @param array $attributions
	 *
	 * @return string
	 */
	function attributionsContent( $attributions ) {
		$media_attributions = '';
		$html = '';
		$licensing = new Licensing();
		$supported = $licensing->getSupportedTypes();

		if ( $attributions ) {
			// generate appropriate markup for each field
			foreach ( $attributions as $attribution ) {

				// unset empty arrays
				$attribution = array_filter( $attribution, 'strlen' );

				// only process if non-empty
				if ( count( $attribution ) > 0 ) {
					$author_byline = isset( $attribution['author'] ) ? __( ' by ', 'pressbooks' ) : '';
					$adapted_byline = isset( $attribution['adapted'] ) ? __( ' adapted by ', 'pressbooks' ) : '';
					$license_prefix = isset( $attribution['license'] ) ? ' &copy; ' : '';
					$author = isset( $attribution['author'] ) ? $attribution['author'] : '';
					$title = isset( $attribution['title'] ) ? $attribution['title'] : '';
					$adapted_author = isset( $attribution['adapted'] ) ? $attribution['adapted'] : '';

					$media_attributions .= sprintf(
						'<li %1$s>%2$s %3$s %4$s %5$s</li>',
						// about attribute
						( isset( $attribution['title_url'] ) ) ?
							sprintf(
								'about="%s"',
								$attribution['title_url']
							) : '',
						// title attribution
						( isset( $attribution['title_url'] ) ) ?
							sprintf(
								'<a rel="cc:attributionURL" href="%1$s" property="dc:title">%2$s</a>',
								$attribution['title_url'],
								$title
							) : $title,
						// author attribution
						sprintf(
							'%1$s %2$s',
							$author_byline,
							( isset( $attribution['author_url'] ) ) ?
								sprintf(
									'<a rel="dc:creator" href="%1$s" property="cc:attributionName">%2$s</a>',
									$attribution['author_url'],
									$author
								) : $author
						),
						// adapted attribution
						sprintf(
							'%1$s %2$s',
							$adapted_byline,
							( isset( $attribution['adapted_url'] ) ) ?
								sprintf(
									'<a rel="dc:source" href="%1$s">%2$s</a>',
									$attribution['adapted_url'],
									$adapted_author
								) : $adapted_author
						),
						// license attribution
						sprintf(
							'%1$s %2$s',
							$license_prefix,
							( isset( $attribution['license'] ) ) ?
								sprintf(
									'<a rel="license" href="%1$s">%2$s</a>',
									$licensing->getUrlForLicense( $attribution['license'] ),
									$supported[ $attribution['license'] ]['desc']
								) : ''
						)
					);
				}
			}
			if ( ! empty( $media_attributions ) ) {
				$html = sprintf(
					'<div class="media-atttributions" prefix:cc="http://creativecommons.org/ns#" prefix:dc="http://purl.org/dc/terms/"><h3>' . __( 'Media Attributions', 'pressbooks' ) . '</h3><ul>%s</ul></div>',
					$media_attributions
				);
			}
		}

		return $html;
	}

	/**
	 * If shortcode is present [media_attributions id='33']
	 * returns the one attribution statement for that attachment
	 *
	 * @since 5.5.0
	 *
	 * @param array $atts
	 * @param string $content
	 *
	 * @return string
	 */
	function shortcodeHandler( $atts, $content = '' ) {
		$retval = '';

		$a = shortcode_atts(
			[
				'id' => '',
			], $atts
		);

		if ( ! empty( $a ) ) {
			$meta = $this->getAttributionsMeta( $a );
			$retval = $this->attributionsContent( $meta );
		}

		return $retval;
	}

}
