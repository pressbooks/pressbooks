<?php

namespace Pressbooks\Interactive;

use function \Pressbooks\Utility\debug_error_log;

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
			$plugin = 'h5p/h5p.php';
			// Initialize H5P REST API only if the plugin is not already initialized or is network disabled
			if ( ! is_plugin_active( $plugin ) || ! is_plugin_active_for_network( $plugin ) ) {
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
	 * Get composer vendor path.
	 *
	 * @param string $startDir The directory to start the search from.
	 *
	 * @return string|null The vendor path or null if not found.
	 */
	private static function getVendorPath( $startDir ) {
		while ( ! file_exists( $startDir . DIRECTORY_SEPARATOR . 'vendor' ) ) {
			$startDir = dirname( $startDir );
			if ( DIRECTORY_SEPARATOR === $startDir ) {
				return null;
			}
		}

		return $startDir . DIRECTORY_SEPARATOR . 'vendor';
	}

  /**
   * Determine if params contain any match.
	 * Private function taken from H5P core, required to create H5P export.
   *
   * @param mixed $params The parameters to search.
	 * @param string $pattern The pattern to match.
	 * @param bool $found (optional) Whether a match has been found.
	 * @return bool Whether a match has been found.
   */
  private function textAddonMatches( $params, $pattern, $found = false ) {
    $type = gettype( $params );
    if ( $type === 'string' ) {
      if ( preg_match( $pattern, $params ) === 1 ) {
        return true;
      }
    } elseif ( $type === 'array' || $type === 'object' ) {
      foreach ( $params as $value ) {
        $found = $this->textAddonMatches( $value, $pattern, $found );
        if ( true === $found ) {
          return true;
        }
      }
    }

    return false;
  }

  /**
   * Generate content slug.
	 * Private function taken from H5P core, required to create H5P export.
   *
   * @param array $content Object with content data.
   * @return string Unique content slug.
   */
  private function generateContentSlug( $content ) {
    $slug = \H5PCore::slugify( $content['title'] );
		$core = \H5P_Plugin::get_instance()->get_h5p_instance( 'core' );

    $available = null;
    while ( !$available ) {
      if ( false === $available ) {
        // If not available, add number suffix.
        $matches = array();
        if ( preg_match( '/(.+-)([0-9]+)$/', $slug, $matches ) ) {
          $slug = $matches[1] . ( intval( $matches[2] ) + 1 );
        } else {
          $slug .=  '-2';
        }
      }
      $available = $core->h5pF->isContentSlugAvailable( $slug );
    }

    return $slug;
  }

	/**
	 * Create H5P export.
	 * Part of filterParameters function taken from H5P core. We cannot use that
	 * function, because the `h5p_export` option could be set to false in order to
	 * prevent downloading the H5P files - we need it temporarily though.
	 *
	 * @param array $content Object with content data.
	 *
	 * @return bool Whether the export was created successfully.
	 */
	private function createH5PExport( $content ) {
		if ( ! ( isset( $content['library'] ) && isset( $content['params'] ) ) ) {
      return false;
    }

		$params = (object) array(
			'library' => \H5PCore::libraryToString( $content['library'] ),
			'params' => json_decode( $content['params'] )
		);

		if (!$params->params) {
			return false;
		}

		$core = \H5P_Plugin::get_instance()->get_h5p_instance( 'core' );

		// Validate and filter against main library semantics.
		$validator = new \H5PContentValidator( $core->h5pF, $core );
		$validator->validateLibrary(
			$params, (object) array( 'options' => array( $params->library ) )
		);

    // Handle addons
    $addons = $core->h5pF->loadAddons();
    foreach ( $addons as $addon ) {
      $add_to = json_decode( $addon['addTo'] );

      if ( isset( $add_to->content->types ) ) {
        foreach( $add_to->content->types as $type ) {

          if ( isset( $type->text->regex ) &&
              $this->textAddonMatches( $params->params, $type->text->regex )
					) {
            $validator->addon( $addon );

            // An addon shall only be added once
            break;
          }
        }
      }
    }

		$params = json_encode( $params->params );

    // Update content dependencies.
    $content['dependencies'] = $validator->getDependencies();

    // Sometimes the parameters are filtered before content has been created
    if ( ! isset( $content['id'] ) ) {
			return false;
    }

		$core->h5pF->deleteLibraryUsage( $content['id'] );
		$core->h5pF->saveLibraryUsage( $content['id'], $content['dependencies'] );

		if ( ! $content['slug'] ) {
			$content['slug'] = $this->generateContentSlug( $content );

			// Remove old export file
			$core->fs->deleteExport( $content['id'] . '.h5p' );
		}

		$exporter = new \H5PExport( $core->h5pF, $core );
		$content['filtered'] = $params;

		$exporter->createExportFile( $content );

		// Cache.
		$core->h5pF->updateContentFields(
			$content['id'],
			array(
				'filtered' => $params,
				'slug' => $content['slug']
			)
		);

		return true;
	}

	/**
	 * Ensure the H5P export file exists.
	 *
	 * @param int $h5p_id ID of H5P content to ensure export for.
	 *
	 * @return callable Cleanup function that needs to be called later to remove
	 *                  the export file if it had not existed before.
	 */
	private function ensureH5Export( $h5p_id ) {
		$core = \H5P_Plugin::get_instance()->get_h5p_instance( 'core' );
		$content = $core->loadContent( $h5p_id );

		$exportFileName = $content['slug'] . '-' . $content['id'] . '.h5p';

		if ( $core->fs->hasExport( $exportFileName ) ) {
			return function ( $h5p_id ) {
				// File exists already, nothing to do
			};
		}

    if ( ! $this->createH5PExport( $content ) ) {
			return function ( $h5p_id ) {
				// Could not create export file, nothing to do.
			};
		}

		/*
		 * Cleanup function that needs to be called later to remove the export file
		 * if it had not existed before - leaving everything as we found it.
		 */
		return function ( $h5p_id ) {
			$core = \H5P_Plugin::get_instance()->get_h5p_instance( 'core' );
			$content = $core->loadContent( $h5p_id );
			$exportFileName = $content['slug'] . '-' . $content['id'] . '.h5p';
			$core->fs->deleteExport( $exportFileName );
		};
	}

	/**
	 * Get H5P representation
	 *
	 * @param int $h5p_id ID of H5P content to get representation of.
	 *
	 * @return string|null HTML representation of H5P content or null.
	 */
	private function getH5PRepresentation( $h5p_id ) {
		/*
		 * Dynamically load H5PExtractor. Could be done via autloader as well, but
		 * why load this unconditionally if if's only needed for printing?
		 */
		$vendorPath = self::getVendorPath( __DIR__ );
		if ( ! isset( $vendorPath )) {
			debug_error_log( 'H5P Extractor error: Could not load H5PExtractor' );
			return null; // Could not load H5PExtractor
		}

		$H5PExtractorPath = $vendorPath .
			DIRECTORY_SEPARATOR . 'snordian' .
			DIRECTORY_SEPARATOR . 'h5p-extractor' .
			DIRECTORY_SEPARATOR . 'app' .
			DIRECTORY_SEPARATOR . 'H5PExtractor.php';

		try {
			require_once $H5PExtractorPath;
		} catch ( \Throwable $e ) {
			debug_error_log( 'H5P Extractor error: ' . $e->getMessage() );
			return null; // Could not load H5PExtractor
		}

		try {
			$exportCleanupCallback = $this->ensureH5Export( $h5p_id );
			$content = \H5P_Plugin::get_instance()->get_content( $h5p_id );

			// Try to get H5P export file for H5P ID
			if ( is_array( $content ) ) {
				$path =
					wp_upload_dir()['basedir'] . DIRECTORY_SEPARATOR .
						'h5p' . DIRECTORY_SEPARATOR .
						'exports' . DIRECTORY_SEPARATOR .
						( $content['slug'] ? $content['slug'] . '-' : '' ) .
						$content['id'] .
						'.h5p';
			}
		} catch ( \Throwable $e ) {
			debug_error_log( 'H5P Extractor error: ' . $e->getMessage() );
			return null;
		}

		if ( ! isset( $path ) || ! file_exists( $path ) ) {
			debug_error_log(
				'H5P Extractor error: ' . _( 'Could not find H5P export file' )
			);
			return null;
		}

		// Guard against CSS spill-over from Pressbooks
		$customCssPre  = '.h5p-iframe { font-family: sans-serif; }';
		$customCssPre .= '.h5p-iframe .h5p-content p { text-indent: 0; }';
		$customCssPre .= '.h5p-iframe .h5p-content div + div { text-indent: 0; }';

		// Custom CSS for font size. Should probably be configurable.
		$customCssPost = '.h5p-iframe .h5p-content { font-size: 10px; }';

  	// Use WordPress upload dir for temporary H5PExtractor files
		$h5pExtractor = new \H5PExtractor\H5PExtractor([
			'uploadsPath' => wp_upload_dir()['basedir'],
			'customCssPre' => $customCssPre,
			'customCssPost' => $customCssPost
		]);

		$extract = $h5pExtractor->extract( ['file' => $path, 'format' => 'html'] );

		if ( isset( $extract['error'] ) ) {
			debug_error_log( 'H5P Extractor error: ' . $extract['error'] );
		}

		// Ensure to delete export file if it had not existed before
		$exportCleanupCallback( $h5p_id );

		return $extract['result'] ?? null;
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

		$representation = $this->getH5PRepresentation( $h5p_id );

		$bladeRenderParams = [
			'id' => $h5p_id,
			'title' => $h5p_title,
			'url' => $h5p_url,
		];

		if ( isset( $representation ) ) {
			$bladeTemplate = 'interactive.h5pextractor';
			$bladeRenderParams['representation'] = $representation;
		} else {
			$bladeTemplate = 'interactive.h5p';
		}

		// HTML
		return $this->blade->render(
			$bladeTemplate,
			$bladeRenderParams
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
