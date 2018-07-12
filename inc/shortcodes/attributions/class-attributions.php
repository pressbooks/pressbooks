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

		// get the content by applying the attributionsContent() function recursively to every member of $all_attributions array
		$media_attributions = array_walk_recursive( $all_attributions, [ $this, 'attributionsContent' ] );

		return $content . $media_attributions;
	}

	/**
	 * @param $value
	 * @param $key
	 */
	function attributionsContent( $value, $key ) {

		if ( $key === 'title' && isset( $value ) ) {
			echo $value;
		}
		if ( $key === 'author' && isset( $value ) ) {
			echo ' by ' . $value;
		}
		if ( $key === 'author_url' && isset( $value ) ) {
			echo '<a rel="dc:creator" href="' . $value . '" property="cc:attributionName">' . '[url]' . '</a>';
		}
		if ( $key === 'license' && isset( $value ) ) {
			echo ' CC ' . '<a rel="license" href="' . ( new Licensing() )->getUrlForLicense( $value ) . '">' . $value . '</a>';
		}
	}
}