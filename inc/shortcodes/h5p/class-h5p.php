<?php

namespace Pressbooks\Shortcodes\H5P;

class H5P {

	/**
	 * Is this the HP5 plugin we're looking for?
	 *
	 * @return bool
	 */
	public function isActive() {
		if ( shortcode_exists( 'h5p' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Override H5P shortcode
	 */
	public function override() {
		remove_shortcode( 'h5p' );
		add_shortcode( 'h5p', [ $this, 'shortcodeHandler' ] );
		add_filter( 'h5p_embed_access', '__return_false' );
	}

	/**
	 * @see \H5P_Plugin::shortcode
	 *
	 * @param array $atts
	 * @return string
	 */
	public function shortcodeHandler( $atts ) {

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]
		global $wpdb;

		if ( isset( $atts['slug'] ) ) {
			$row = $wpdb->get_row(
				$wpdb->prepare( "SELECT id FROM {$wpdb->prefix}h5p_contents WHERE slug=%s", $atts['slug'] ),
				ARRAY_A
			);
			if ( $wpdb->last_error ) {
				return '';
			}
			if ( ! isset( $row['id'] ) ) {
				return '';
			}
			$atts['id'] = $row['id'];
		}

		$h5p_id = isset( $atts['id'] ) ? intval( $atts['id'] ) : 0;
		if ( ! $h5p_id ) {
			return '';
		}

		if ( ! class_exists( '\H5P_Plugin' ) ) {
			return '';
		}

		$content = \H5P_Plugin::get_instance()->get_content( $h5p_id );
		$url = get_permalink( $id );

		$html = "<p class='h5p'>{$content['title']}<br/>{$url}</a></p>";

		return $html;
	}

}
