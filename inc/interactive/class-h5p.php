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
 */
class H5P {

	const SHORTCODE = 'h5p';

	/**
	 * @var \Jenssegers\Blade\Blade
	 */
	protected $blade;

	/**
	 * @param \Jenssegers\Blade\Blade $blade
	 */
	public function __construct( $blade ) {
		$this->blade = $blade;
		if ( is_file( WP_PLUGIN_DIR . '/h5p/autoloader.php' ) ) {
			require_once( WP_PLUGIN_DIR . '/h5p/autoloader.php' );
		}
		add_filter( 'print_h5p_content', [ $this, 'generateCustomH5pWrapper' ], 10, 2 );
	}

	/**
	 * Is this the HP5 plugin we're looking for?
	 *
	 * @return bool
	 */
	public function isActive() {
		if ( shortcode_exists( self::SHORTCODE ) ) {
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function activate() {
		$h5p_plugin = 'h5p/h5p.php';
		if ( is_file( WP_PLUGIN_DIR . "/{$h5p_plugin}" ) ) {
			$result = activate_plugin( $h5p_plugin );
			if ( is_wp_error( $result ) === false && method_exists( '\H5P_Plugin', 'fetch_h5p' ) === true ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Defines REST API callbacks
	 *
	 * @return bool
	 */
	public function apiInit() {
		try {
			if ( ! is_plugin_active( 'h5p/h5p.php' ) ) {
				\H5P_Plugin::get_instance()->rest_api_init();
			}
			if (
				(
					has_filter( 'pb_set_api_items_permission' ) &&
					apply_filters( 'pb_set_api_items_permission', 'h5p' )
				) ||
				get_option( 'blog_public' )
			) {
				add_filter( 'h5p_rest_api_all_permission', '__return_true' );
			}
		} catch ( \Throwable $e ) {
			return false;
		}
		return true;
	}

	/**
	 * Download and add H5P content from given url.
	 *
	 * @param string $url
	 *
	 * @return int
	 */
	public function fetch( $url ) {
		try {
			$new_h5p_id = \H5P_Plugin::get_instance()->fetch_h5p( $url );
		} catch ( \Throwable $e ) {
			$new_h5p_id = 0;
		}
		return $new_h5p_id;
	}

	/**
	 * Override H5P shortcode
	 */
	public function override() {
		remove_shortcode( self::SHORTCODE );
		add_shortcode( self::SHORTCODE, [ $this, 'replaceShortcode' ] );
		add_filter( 'h5p_embed_access', '__return_false' );
	}

	/**
	 * Replace [h5p] shortcode with standard text (used in exports)
	 *
	 * @see \H5P_Plugin::shortcode
	 *
	 * @param array $atts
	 *
	 * @return string
	 */
	public function replaceShortcode( $atts ) {

		global $id; // This is the Post ID, [@see WP_Query::setup_postdata, ...]
		global $wpdb;

		$h5p_url = wp_get_shortlink( $id );
		$h5p_title = get_the_title( $id );
		if ( empty( $h5p_title ) ) {
			$h5p_title = get_bloginfo( 'name' );
		}

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

		$h5p_id = isset( $atts['id'] ) ? (int) $atts['id'] : 0;

		// H5P Content
		if ( $h5p_id ) {
			try {
				$content = \H5P_Plugin::get_instance()->get_content( $h5p_id );
				if ( is_array( $content ) && ! empty( $content['title'] ) ) {
					$h5p_title = $content['title'];
				}
			} catch ( \Throwable $e ) {
				// Do nothing
			}
		}
		// HTML
		return $this->blade->render(
			'interactive.h5p', [
				'title' => $h5p_title,
				'url' => $h5p_url,
				'id' => $h5p_id ? '#' . self::SHORTCODE . '-' . $h5p_id : '',
			]
		);
	}

	/**
	 * Replace imported/cloned [h5p] shortcodes with warning
	 *
	 * @param string $content
	 * @param int[]|int $ids (optional)
	 *
	 * @return string
	 */
	public function replaceUncloneable( $content, $ids = [] ) {
		$pattern = get_shortcode_regex( [ self::SHORTCODE ] );
		$callback = function ( $shortcode ) use ( $ids ) {
			$warning = __( 'The original version of this chapter contained H5P content. You may want to remove or replace this element.', 'pressbooks' );
			if ( empty( $ids ) ) {
				return $warning;
			} else {
				$shortcode_attrs = shortcode_parse_atts( $shortcode[3] );
				if ( is_array( $shortcode_attrs ) && isset( $shortcode_attrs['id'] ) ) {
					// Remove quotes, return just the integer
					$my_id = $shortcode_attrs['id'];
					$my_id = trim( $my_id, "'" );
					$my_id = trim( $my_id, '"' );
					$my_id = str_replace( '&quot;', '', $my_id );
					if ( in_array( $my_id, (array) $ids, false ) ) { // @codingStandardsIgnoreLine
						return $warning;
					}
				}
			}
			return $shortcode[0];
		};
		$content = preg_replace_callback(
			"/$pattern/",
			$callback,
			$content
		);
		return $content;
	}

	/**
	 * @param string $content
	 *
	 * @return int[]
	 */
	public function findAllShortcodeIds( $content ) {
		$ids = [];
		$matches = [];
		$regex = get_shortcode_regex( [ self::SHORTCODE ] );
		if ( preg_match_all( '/' . $regex . '/s', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $shortcode ) {
				$shortcode_attrs = shortcode_parse_atts( $shortcode[3] );
				if ( is_array( $shortcode_attrs ) && isset( $shortcode_attrs['id'] ) ) {
					// Remove quotes, return just the integer
					$my_id = $shortcode_attrs['id'];
					$my_id = trim( $my_id, "'" );
					$my_id = trim( $my_id, '"' );
					$my_id = str_replace( '&quot;', '', $my_id );
					$ids[] = (int) $my_id;
				}
			}
		}
		return $ids;
	}

	/**
	 * This hook adds a HTML wrapper to identify each hp5 activity
	 *
	 * @param $html
	 * @param $content array this array holds the custom post type information (h5p)
	 * @return string
	 */
	public function generateCustomH5pWrapper( $html, array $content ) {
		return '<div id="' . self::SHORTCODE . '-' . $content['id'] . '">' . $html . '</div>';
	}

}
