<?php

namespace Pressbooks\Interactive;

use function Pressbooks\Utility\length_to_inches;
use function \Pressbooks\Utility\debug_error_log;
use Pressbooks\Container;

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
	const H5P_PLUGIN_PATH = 'h5p/h5p.php';

	/**
	 * @var Blade
	 */
	protected $blade;

	/**
	 * @var H5PPluginInterface
	 */
	protected H5PPluginInterface $h5p_plugin;

	/**
	 * @var H5PExtractorInterface
	 */
	protected H5PExtractorInterface $h5p_extractor;

	/**
	 * @var WordPressHelperInterface
	 */
	protected WordPressHelperInterface $wp_helper;

	/**
	 * @var H5PCoreInterface
	 */
	protected H5PCoreInterface $h5p_core;

	/**
	 * @var float DPI for rendering H5P content
	 */
	protected $dpi = 96;

	/**
	 * @var bool
	 */
	protected bool $enableStaticRepresentation = false;

	static ?H5P $instance = null;

	/**
	 * @param Blade $blade
	 * @param H5PPluginInterface $h5p_plugin
	 * @param H5PExtractorInterface $h5p_extractor
	 * @param WordPressHelperInterface $wp_helper
	 * @param H5PCoreInterface $h5p_core
	 */
	public function __construct( $blade, H5PPluginInterface $h5p_plugin, H5PExtractorInterface $h5p_extractor, WordPressHelperInterface $wp_helper, H5PCoreInterface $h5p_core ) {
		$this->blade = $blade;
		$this->h5p_plugin = $h5p_plugin;
		$this->h5p_extractor = $h5p_extractor;
		$this->wp_helper = $wp_helper;
		$this->h5p_core = $h5p_core;
		add_action( 'pb_pre_export', [ $this, 'shouldEnablePrint' ] );
		add_filter( 'print_h5p_content', [ $this, 'generateCustomH5pWrapper' ], 10, 2 );
		add_filter( 'sanitize_file_name', [ $this, 'renameFont' ] );
		self::$instance = $this;
	}

	public static function getInstance(): H5P {
		if ( null === self::$instance ) {
			self::$instance = new static(
				Container::get( 'Blade' ),
				Container::get( 'H5PPlugin' ),
				Container::get( 'H5PExtractor' ),
				Container::get( 'WordPressHelper' ),
				Container::get( 'H5PCore' )
			);
		}
		return self::$instance;
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
	public function activate(): bool {
		if ( $this->wp_helper->isFile( WP_PLUGIN_DIR . '/' . self::H5P_PLUGIN_PATH ) ) {
			$result = $this->wp_helper->activatePlugin( self::H5P_PLUGIN_PATH );
			if ( is_wp_error( $result ) === false && $this->h5p_plugin->canFetchH5P() ) {
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
			if ( ! $this->wp_helper->isPluginActive( $plugin ) || ! $this->wp_helper->isPluginActiveForNetwork( $plugin ) ) {
				$this->h5p_plugin->restApiInit();
			}
			if (
				(
					$this->wp_helper->hasFilter( 'pb_set_api_items_permission' ) &&
					$this->wp_helper->applyFilters( 'pb_set_api_items_permission', 'h5p' )
				) ||
				$this->wp_helper->getOption( 'blog_public' )
			) {
				$this->wp_helper->addFilter( 'h5p_rest_api_all_permission', '__return_true' );
			}
		} catch ( \Throwable $e ) {
			return false;
		}
		return true;
	}

	/**
	 * Enable H5P content on export
	 *
	 */
	public function shouldEnablePrint(): void {
		$export_options = get_option( 'pressbooks_export_options' );
		$is_previewing = isset( $_GET['debug'] ) ? sanitize_text_field( wp_unslash( $_GET['debug'] ) ) : false;
		if ( isset( $export_options['h5p_print_on_exports'] ) && $export_options['h5p_print_on_exports'] && ! $is_previewing ) {
			$this->enableStaticRepresentation = $export_options['h5p_print_on_exports'];
		}
	}

	/**
	 * Download and add H5P content from given url.
	 *
	 * @param string $url
	 *
	 * @return int
	 */
	public function fetch( $url ) {
		return $this->h5p_plugin->fetchH5P( $url );
	}

	/**
	 * Determine if params contain any match.
	 * Function taken from H5P core, required to create H5P export.
	 *
	 * @param mixed $params The parameters to search.
	 * @param string $pattern The pattern to match.
	 * @param bool $found (optional) Whether a match has been found.
	 * @return bool Whether a match has been found.
	 */
	public function textAddonMatches( mixed $params, string $pattern, bool $found = false ): bool {
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
	private function generateContentSlug( array $content ): string {
		$slug = $this->h5p_core->slugify( $content['title'] );
		$core = $this->h5p_plugin->getH5PInstance( 'core' );

		$available = null;
		while ( ! $available ) {
			if ( false === $available ) {
				// If not available, add number suffix.
				$matches = [];
				if ( preg_match( '/(.+-)([0-9]+)$/', $slug, $matches ) ) {
					$slug = $matches[1] . ( intval( $matches[2] ) + 1 );
				} else {
					$slug .= '-2';
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
	private function createH5PExport( array $content ): bool {
		if ( ! ( isset( $content['library'] ) && isset( $content['params'] ) ) ) {
			return false;
		}

		$params = (object) [
			'library' => $this->h5p_core->libraryToString( $content['library'] ),
			'params' => json_decode( $content['params'] ),
		];

		if ( ! $params->params ) {
			return false;
		}

		$core = $this->h5p_plugin->getH5PInstance( 'core' );

		// Validate and filter against main library semantics.
		$validator = new \H5PContentValidator( $core->h5pF, $core );
		$validator->validateLibrary(
			$params, (object) [ 'options' => [ $params->library ] ]
		);

		// Handle addons
		$addons = $core->h5pF->loadAddons();
		foreach ( $addons as $addon ) {
			$add_to = json_decode( $addon['addTo'] );

			if ( isset( $add_to->content->types ) ) {
				foreach ( $add_to->content->types as $type ) {

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
			[
				'filtered' => $params,
				'slug' => $content['slug'],
			]
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
	private function ensureH5Export( int $h5p_id ): callable {
		$core = $this->h5p_plugin->getH5PInstance( 'core' );
		$content = $core->loadContent( $h5p_id );

		$export_filename = $content['slug'] . '-' . $content['id'] . '.h5p';

		if ( $core->fs->hasExport( $export_filename ) ) {
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
		return function ( int $h5p_id ): void {
			$core = $this->h5p_plugin->getH5PInstance( 'core' );
			$content = $core->loadContent( $h5p_id );
			$export_filename = $content['slug'] . '-' . $content['id'] . '.h5p';
			$core->fs->deleteExport( $export_filename );
		};
	}

	/**
	 * Get H5P representation
	 *
	 * @param int $h5p_id ID of H5P content to get representation of.
	 *
	 * @return string|null HTML representation of H5P content or null.
	 */
	protected function getH5PRepresentation( int $h5p_id ): mixed {

		if ( ! $this->enableStaticRepresentation ) {
			return null; // Static representation is disabled
		}

		try {
			$export_cleanup_callback = $this->ensureH5Export( $h5p_id );
			$content = $this->h5p_plugin->getContent( $h5p_id );

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
				'H5P Extractor error: ' . __( 'Could not find H5P export file' )
			);
			return null;
		}

		// Calculate render width in pixels
		$pdf_options = get_option( 'pressbooks_theme_options_pdf' );
		$page_width = length_to_inches( $pdf_options['pdf_page_width'], $this->dpi ) -
			length_to_inches( $pdf_options['pdf_page_margin_inside'], $this->dpi ) -
			length_to_inches( $pdf_options['pdf_page_margin_outside'], $this->dpi );
		$render_width = $page_width * $this->dpi;

		// Guards against CSS spill-over from Pressbooks. !important is necessary here, unfortunately.
		$custom_css_pre  = '.h5p-extractor .h5p-iframe .h5p-content p { text-indent: 0; }';
		$custom_css_pre .= '.h5p-extractor .h5p-iframe .h5p-content div + div { text-indent: 0; }';
		// Guard for Question Set
		$custom_css_pre .= '.h5p-extractor .h5p-iframe .h5p-content .h5p-question-container + .qs-footer ' .
			'.progress-dot:not(.current):not(.unanswered) { background: #cecece; }';
		// Guard for Old Accordion version used in Pressbooks
		$custom_css_pre .= '.h5p-extractor .h5p-accordion .h5p-panel-title:before {content: "";}';
		$custom_css_pre .= '.h5p-extractor .h5p-accordion .h5p-panel-title { padding-left: 0; font-size: unset;}';

		// Workaround for Advanced Text, the > selector in the original CSS will be replaced by &gt; for XML
		$custom_css_pre .= '.h5p-extractor .h5p-advanced-text ul { padding-left: 1.5em; }';
		$custom_css_pre .= '.h5p-extractor .h5p-advanced-text ol { padding-left: 1.5em; }';
		$custom_css_pre .= '.h5p-extractor .h5p-advanced-text ul li { list-style-type: disc; margin: 0 0 1em 1.5em; padding: 0;}';
		$custom_css_pre .= '.h5p-extractor .h5p-advanced-text ol li { list-style-type: decimal; margin: 0 0 1em 1.5em; padding: 0;}';
		$custom_css_pre .= '.h5p-extractor .h5p-advanced-text ul li:last-child { margin-bottom: 0; }';
		$custom_css_pre .= '.h5p-extractor .h5p-advanced-text ol li:last-child { margin-bottom: 0; }';

		// Workaround for Image, the > selector in the original CSS will be replaced by &gt; for XML
		$custom_css_pre .= '.h5p-extractor .h5p-image img { display: block; width: 100%; height: 100%; }';

		// Catch extractor errors and return null if extraction fails
		// Update the extractor configuration with current context
		$extractor_config = [
			'uploadsPath' => wp_upload_dir()['basedir'],
			'h5pContentUrl' => wp_upload_dir()['baseurl'] . '/h5p/content/' . $h5p_id . '/',
			'h5pCoreUrl' => plugins_url() . '/h5p/h5p-php-library/',
			'h5pLibrariesUrl' => wp_upload_dir()['baseurl'] . '/h5p/libraries/',
			'customCssPre' => $custom_css_pre,
			'baseFontSize' => 10,
			'renderWidth' => $render_width,
		];

		// Create a new extractor instance with updated config

		try {
			$h5p_extractor = new H5PExtractorAdapter( $extractor_config );
			$extract = $h5p_extractor->extract([
				'file' => $path,
				'format' => 'html',
			]);
			if ( isset( $extract['error'] ) ) {
				debug_error_log( 'H5P Extractor error: ' . $extract['error'] );
			}
			if ( str_contains( $extract['result'], 'No HTML renderer for' ) ) {
				return null;
			}
		} catch ( \Exception | \Error $e ) {
			return null; // Any Extractor Exception or Error will return null and will fallback to the default H5P shortcode
		}

		// Ensure to delete export file if it had not existed before
		$export_cleanup_callback( $h5p_id );

		return $extract['result'] ?? null;
	}

	/**
	 * Override H5P shortcode
	 */
	public function override(): void {
		remove_shortcode( self::SHORTCODE );
		add_shortcode( self::SHORTCODE, [ $this, 'replaceShortcode' ] );
		add_filter( 'h5p_embed_access', '__return_false' );
	}

	/**
	 * Replace [h5p] shortcode with static HTML representation of H5P content.
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

		$activities_included_in_exported_chapters = apply_filters( 'h5p_activities_to_export', '' );

		if ( ! empty( $activities_included_in_exported_chapters ) ) {
			if ( ! in_array( (int) $atts['id'], $activities_included_in_exported_chapters, false ) ) { // @codingStandardsIgnoreLine
				// If the H5P ID is not in the list of activities inside of the exported chapters, halt processing
				return '';
			}
		}

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
			$content = $this->h5p_plugin->getContent( $h5p_id );
			if ( is_array( $content ) && ! empty( $content['title'] ) ) {
				$h5p_title = $content['title'];
			}
		}

		$representation = $this->getH5PRepresentation( $h5p_id );

		$blade_render_params = [
			'id' => $h5p_id ? '#' . self::SHORTCODE . '-' . $h5p_id : '',
			'title' => $h5p_title,
			'url' => $h5p_url,
		];

		if ( isset( $representation ) ) {
			$blade_template = 'interactive.h5pextractor';
			$blade_render_params['representation'] = $representation;
		} else {
			$blade_template = 'interactive.h5p';
		}

		// HTML
		return $this->blade->render(
			$blade_template,
			$blade_render_params
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
	public function replaceUncloneable( $content, $ids = [] ): string {
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
	public function findAllShortcodeIds( $content ): array {
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
	public function generateCustomH5pWrapper( $html, array $content ): string {
		return '<div id="' . self::SHORTCODE . '-' . $content['id'] . '">' . $html . '</div>';
	}

	public function renameFont( $filename ): string {
		if ( $filename === 'H5P.ttf' ) {
			return 'h5p.ttf';
		}
		return $filename;
	}

}
