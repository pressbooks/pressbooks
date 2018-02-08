<?php

namespace Pressbooks\Interactive;

/**
 * This class wedges itself in between Pressbooks and the H5P WordPress Plugin
 *
 * @see https://github.com/h5p/h5p-wordpress-plugin
 *
 * Notes:
 *
 * The H5P plugin should only be activated on books where it will be used (to
 * avoid adding 13 extra tables to every book on a network). Related issues:
 *
 *  + https://github.com/h5p/h5p-wordpress-plugin/issues/41
 *  + https://github.com/h5p/h5p-wordpress-plugin/issues/64
 *
 * Pressbooks can not support H5P cloning until there is significant progress
 * on this issue:
 *
 *  + https://github.com/h5p/h5p-wordpress-plugin/issues/63
 */
class H5P {

	/**
	 * @var \Jenssegers\Blade\Blade
	 */
	protected $blade;

	/**
	 * @param \Jenssegers\Blade\Blade $blade
	 */
	public function __construct( $blade ) {
		$this->blade = $blade;
	}

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
	 *
	 * @return string
	 */
	public function shortcodeHandler( $atts ) {

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]
		global $wpdb;

		$h5p_title = get_the_title( $id );
		$h5p_url = get_permalink( $id );

		if ( isset( $atts['slug'] ) ) {
			$suppress = $wpdb->suppress_errors();
			$row = $wpdb->get_row(
				$wpdb->prepare( "SELECT id FROM {$wpdb->prefix}h5p_contents WHERE slug=%s", $atts['slug'] ),
				ARRAY_A
			);
			if ( isset( $row['id'] ) ) {
				$atts['id'] = $row['id'];
			}
			$wpdb->suppress_errors( $suppress );
		}

		$h5p_id = isset( $atts['id'] ) ? intval( $atts['id'] ) : 0;

		// H5P Content
		if ( $h5p_id ) {
			try {
				if ( class_exists( '\H5P_Plugin' ) ) {
					$content = \H5P_Plugin::get_instance()->get_content( $h5p_id );
					if ( is_array( $content ) && ! empty( $content['title'] ) ) {
						$h5p_title = $content['title'];
					}
				}
			} catch ( \Exception $e ) {
				// Do nothing
			}
		}

		// HTML
		$html = $this->blade->render(
			'interactive.shared', [
				'title' => $h5p_title,
				'url' => $h5p_url,
			]
		);

		return $html;
	}

}
