<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks;

use function \Pressbooks\Editor\update_editor_style;
use function \Pressbooks\Sanitize\normalize_css_urls;
use function \Pressbooks\Utility\debug_error_log;
use Pressbooks\Modules\ThemeOptions\ThemeOptions;

/**
 * Custom Styles Feature(s)
 */
class Styles {

	const PAGE = 'pb_custom_styles';

	/**
	 * Supported formats
	 *
	 * Array key is slug, array val is text (passed to _e() where necessary.)
	 * All keys must match an *existing* WP post where post_name = __key__ and post_type = 'custom-style'
	 *
	 * @return array
	 */
	protected $supported = [
		'web' => 'Web',
		'epub' => 'Ebook',
		'prince' => 'PDF',
	];

	/**
	 * @var Sass
	 */
	protected $sass;

	/**
	 * @param Sass $sass
	 */
	public function __construct( $sass ) {
		$this->sass = $sass;
	}

	/**
	 * @return array
	 */
	public function getSupported() {
		return $this->supported;
	}

	/**
	 * @return Sass
	 */
	public function getSass() {
		return $this->sass;
	}

	/**
	 * Set filters & hooks
	 */
	public function init() {

		if ( ! Book::isBook() ) {
			// Not a book, disable
			return;
		}
		if ( class_exists( '\Pressbooks\CustomCss' ) && CustomCss::isCustomCss() ) {
			// Uses old Custom CSS Editor, disable
			return;
		}

		// Code Mirror
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === $this::PAGE ) {
			add_action(
				'admin_enqueue_scripts', function () {
					wp_enqueue_script( 'wp-codemirror' );
					wp_enqueue_script( 'csslint' );
					wp_enqueue_style( 'wp-codemirror' );
				}
			);
		}

		add_action(
			'init', function () {
				// Admin Menu
				add_action(
					'admin_menu', function () {
						add_theme_page( __( 'Custom Styles', 'pressbooks' ), __( 'Custom Styles', 'pressbooks' ), 'edit_others_posts', $this::PAGE, [ $this, 'editor' ] );
					}, 11
				);
				// Register Post Types
				$this->registerPosts();
			}
		);

		// Catch form submission
		add_action( 'init', [ $this, 'formSubmit' ], 50 );
	}

	/**
	 * Custom style rules will be saved in a custom post type: custom-style
	 */
	public function initPosts() {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$posts = [
			[
				'post_title' => __( 'Custom Style for Ebook', 'pressbooks' ),
				'post_name' => 'epub',
				'post_type' => 'custom-style',
			],
			[
				'post_title' => __( 'Custom Style for PDF', 'pressbooks' ),
				'post_name' => 'prince',
				'post_type' => 'custom-style',
			],
			[
				'post_title' => __( 'Custom Style for Web', 'pressbooks' ),
				'post_name' => 'web',
				'post_type' => 'custom-style',
			],
		];

		$post = [
			'post_status' => 'publish',
			'post_author' => wp_get_current_user()->ID,
		];

		foreach ( $posts as $item ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_name = %s AND post_status = 'publish' ",
					[ $item['post_title'], $item['post_type'], $item['post_name'] ]
				)
			);
			if ( empty( $exists ) ) {
				$data = array_merge( $item, $post );
				wp_insert_post( $data );
			}
		}
	}

	/**
	 * Custom style rules will be saved in a custom post type: custom-style
	 */
	public function registerPosts() {
		$args = [
			'exclude_from_search' => true,
			'public' => false,
			'publicly_queryable' => false,
			'show_ui' => false,
			'supports' => [ 'revisions' ],
			'label' => 'Custom Style',
			'can_export' => false,
			'rewrite' => false,
			'capabilities' => [
				'edit_post' => 'edit_others_posts',
				'read_post' => 'read',
				'delete_post' => 'edit_others_posts',
				'edit_posts' => 'edit_others_posts',
				'edit_others_posts' => 'edit_others_posts',
				'publish_posts' => 'edit_others_posts',
				'read_private_posts' => 'read',
			],
		];
		register_post_type( 'custom-style', $args );
	}

	/**
	 * @return \WP_Post|false
	 */
	public function getWebPost() {
		return $this->getPost( 'web' );
	}

	/**
	 * @return \WP_Post|false
	 */
	public function getEpubPost() {
		return $this->getPost( 'epub' );
	}

	/**
	 * @return \WP_Post|false
	 */
	public function getPrincePost() {
		return $this->getPost( 'prince' );
	}

	/**
	 * @param string $slug post_name
	 *
	 * @return \WP_Post|false
	 */
	public function getPost( $slug ) {

		// Supported post names (ie. slugs)
		$supported = array_keys( $this->supported );
		if ( ! in_array( $slug, $supported, true ) ) {
			return false;
		}

		$args = [
			'name' => $slug,
			'post_type' => 'custom-style',
			'posts_per_page' => 1,
			'post_status' => 'publish',
			'orderby' => 'modified',
			'no_found_rows' => true,
			'cache_results' => true,
		];

		$q = new \WP_Query();
		$results = $q->query( $args );

		if ( empty( $results ) ) {
			return false;
		}

		return $results[0];
	}

	/**
	 *
	 * Get stylesheet directory, applies filter: pb_stylesheet_directory
	 *
	 * @param \WP_Theme $theme (optional)
	 * @param bool $realpath (optional)
	 *
	 * @return string|false
	 */
	public function getDir( $theme = null, $realpath = false ) {

		if ( $theme ) {
			$dir = $theme->get_stylesheet_directory();
		} else {
			$dir = get_stylesheet_directory();
		}

		$basepath = apply_filters( 'pb_stylesheet_directory', $dir );

		if ( $realpath ) {
			$basepath = realpath( $basepath );
		}

		return $basepath;
	}

	/**
	 * Fullpath to SCSS file for Web
	 *
	 * @param \WP_Theme $theme (optional)
	 *
	 * @return false|string
	 */
	public function getPathToWebScss( $theme = null ) {
		return $this->getPathToScss( 'web', $theme );
	}

	/**
	 * Fullpath to SCSS file for Prince XML
	 *
	 * @param \WP_Theme $theme (optional)
	 *
	 * @return false|string
	 */
	public function getPathToPrinceScss( $theme = null ) {
		return $this->getPathToScss( 'prince', $theme );
	}

	/**
	 * Fullpath to SCSS file for Epub
	 *
	 * @param \WP_Theme $theme (optional)
	 *
	 * @return false|string
	 */
	public function getPathToEpubScss( $theme = null ) {
		return $this->getPathToScss( 'epub', $theme );
	}

	/**
	 * Fullpath to SCSS file
	 *
	 * @param string $type
	 * @param \WP_Theme $theme (optional)
	 *
	 * @return string|false
	 */
	public function getPathToScss( $type, $theme = null ) {

		if ( null === $theme ) {
			$theme = wp_get_theme();
		}

		if ( $this->isCurrentThemeCompatible( 1, $theme ) ) {
			if ( 'web' === $type ) {
				$path_to_style = realpath( $this->getDir( $theme ) . '/style.scss' );
			} else {
				$path_to_style = realpath( $this->getDir( $theme ) . "/export/$type/style.scss" );
			}
		} elseif ( $this->isCurrentThemeCompatible( 2, $theme ) ) {
			$path_to_style = realpath( $this->getDir( $theme ) . "/assets/styles/$type/style.scss" );
		} else {
			$path_to_style = false;
		}

		return $path_to_style;
	}

	/**
	 * Are the current theme's stylesheets SCSS compatible?
	 *
	 * @param int $version
	 * @param \WP_Theme $theme (optional)
	 *
	 * @return bool
	 */
	public function isCurrentThemeCompatible( $version = 1, $theme = null ) {

		if ( null === $theme ) {
			$theme = wp_get_theme();
		}

		$basepath = $this->getDir( $theme );

		$types = [
			'prince',
			'epub',
			'web',
		];

		foreach ( $types as $type ) {
			$path = '';
			if ( 1 === $version && 'web' !== $type ) {
				$path = $basepath . "/export/$type/style.scss";
			} elseif ( 1 === $version && 'web' === $type ) {
				$path = $basepath . '/style.scss';
			}

			if ( 2 === $version ) {
				$path = $basepath . "/assets/styles/$type/style.scss";
			}

			$fullpath = realpath( $path );
			if ( ! is_file( $fullpath ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the version of Buckram for the current install or locked theme.
	 *
	 * @since 5.0.0
	 *
	 * @see https://github.com/pressbooks/buckram/blob/master/styles/buckram.scss
	 *
	 * @return string|bool
	 */
	public function getBuckramVersion() {
		$fullpath = realpath( $this->sass->pathToGlobals() . 'buckram.scss' );
		if ( is_file( $fullpath ) ) {
			return get_file_data(
				$fullpath,
				[
					'version' => 'Version',
				]
			)['version'];
		}
		return false; // No version available.
	}

	/**
	 * Check that the currently active theme uses Buckram (optionally a minimum version of Buckram).
	 *
	 * @since 5.3.0
	 *
	 * @param int|string $version
	 *
	 * @return bool
	 */
	public function hasBuckram( $version = 0 ) {
		if ( $this->isCurrentThemeCompatible( 2 ) && version_compare( $this->getBuckramVersion(), $version ) >= 0 ) {
			return true;
		}
		return false;
	}

	/**
	 * @param array|string $overrides (optional)
	 *
	 * @return string
	 */
	public function customizeWeb( $overrides = [] ) {
		$path = $this->getPathToWebScss();
		if ( $path ) {
			return $this->customize( 'web', \Pressbooks\Utility\get_contents( $path ), $overrides );
		}
		return '';
	}

	/**
	 * @param array|string $overrides (optional)
	 *
	 * @return string
	 */
	public function customizePrince( $overrides = [] ) {
		$path = $this->getPathToPrinceScss();
		if ( $path ) {
			return $this->customize( 'prince', \Pressbooks\Utility\get_contents( $path ), $overrides );
		}
		return '';
	}

	/**
	 * @param array|string $overrides (optional)
	 *
	 * @return string
	 */
	public function customizeEpub( $overrides = [] ) {
		$path = $this->getPathToEpubScss();
		if ( $path ) {
			return $this->customize( 'epub', \Pressbooks\Utility\get_contents( $path ), $overrides );
		}
		return '';
	}

	/**
	 * Transpile SCSS based on theme compatibility
	 *
	 * @param string $type
	 * @param string $scss
	 * @param array|string $overrides
	 *
	 * @return string
	 */
	public function customize( $type, $scss, $overrides = [] ) {
		$scss = $this->applyOverrides( $scss, $overrides );

		// Apply Theme Options
		if ( $type === 'prince' ) {
			$scss = apply_filters( 'pb_pdf_css_override', $scss );
		} else {
			$scss = apply_filters( "pb_{$type}_css_override", $scss );
		}

		if ( $this->isCurrentThemeCompatible( 1 ) ) {
			$css = $this->sass->compile(
				$scss,
				[
					$this->sass->pathToUserGeneratedSass(),
					$this->sass->pathToPartials(),
					$this->sass->pathToFonts(),
					$this->getDir(),
				]
			);
		} elseif ( $this->isCurrentThemeCompatible( 2 ) ) {
			$css = $this->sass->compile(
				$scss,
				$this->sass->defaultIncludePaths( $type )
			);
		} elseif ( CustomCss::isCustomCss() ) {
			// Compile pressbooks-book web stylesheet when using the *DEPRECATED* Custom CSS theme
			$css = $this->sass->compile(
				$scss,
				$this->sass->defaultIncludePaths( $type, wp_get_theme( 'pressbooks-book' ) )
			);
		} else {
			$css = $this->injectHouseStyles( $scss );
		}

		return $css;
	}

	/**
	 * Prepend or append SCSS overrides depending on which version of the theme architecture is in use.
	 *
	 * @param string $scss
	 * @param array|string $overrides
	 *
	 * @return string
	 */
	public function applyOverrides( $scss, $overrides = [] ) {

		if ( ! is_array( $overrides ) ) {
			$overrides = (array) $overrides;
		}
		$overrides = implode( "\n", $overrides );

		if ( $this->isCurrentThemeCompatible( 2 ) ) {
			// Prepend override variables (see: http://sass-lang.com/documentation/file.SASS_REFERENCE.html#variable_defaults_).
			$scss = $overrides . "\n" . $scss;
		} else {
			// Append overrides.
			$scss .= "\n" . $overrides;
		}

		return $scss;
	}

	/**
	 * Inject house styles into CSS
	 *
	 * @param string $css
	 *
	 * @return string
	 */
	public function injectHouseStyles( $css ) {

		$scan = [
			'/*__INSERT_PDF_HOUSE_STYLE__*/' => get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/legacy/styles/_pdf-house-style.scss',
			'/*__INSERT_EPUB_HOUSE_STYLE__*/' => get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/legacy/styles/_epub-house-style.scss',
			'/*__INSERT_MOBI_HOUSE_STYLE__*/' => get_theme_root( 'pressbooks-book' ) . '/pressbooks-book/assets/legacy/styles/_mobi-house-style.scss',
		];

		foreach ( $scan as $token => $replace_with ) {
			if ( is_file( $replace_with ) ) {
				$css = str_replace( $token, \Pressbooks\Utility\get_contents( $replace_with ), $css );
			}
		}

		return $css;
	}

	/**
	 * Update and save the supplementary webBook stylesheet which incorporates user options, etc.
	 *
	 * @param string $stylesheet Directory name for the theme. Defaults to current theme.
	 * @return void
	 */
	public function updateWebBookStyleSheet( $stylesheet = null ) {

		if ( CustomCss::isCustomCss() ) {
			// Compile pressbooks-book web stylesheet when using the *DEPRECATED* Custom CSS theme
			$theme = wp_get_theme( 'pressbooks-book' );
		} else {
			$theme = wp_get_theme( $stylesheet );
		}

		// Populate $url-base variable so that links to images and other assets remain intact
		$overrides = [ '$url-base: "' . $theme->get_stylesheet_directory_uri() . '";' ];
		if ( $this->isCurrentThemeCompatible( 1 ) ) {
			$scss = \Pressbooks\Utility\get_contents( realpath( $theme->get_stylesheet_directory() . '/style.scss' ) );
		} elseif ( $this->isCurrentThemeCompatible( 2 ) || CustomCss::isCustomCss() ) {
			$scss = \Pressbooks\Utility\get_contents( realpath( $theme->get_stylesheet_directory() . '/assets/styles/web/style.scss' ) );
		} else {
			return;
		}

		$custom_styles = $this->getWebPost();
		if ( $custom_styles && ! empty( $custom_styles->post_content ) ) {
			// append the user's custom styles to the theme stylesheet prior to compilation
			$scss .= "\n" . $custom_styles->post_content;
		}

		$css = $this->customize( 'web', $scss, $overrides );

		$css = normalize_css_urls( $css );

		$css_file = $this->sass->pathToUserGeneratedCss() . '/style.css';
		\Pressbooks\Utility\put_contents( $css_file, $css );
	}

	/**
	 * If the current theme's version or Buckram's version has increased, do SCSS stuff
	 *
	 * @return bool
	 */
	public function maybeUpdateStylesheets() {
		// If this is ajax/cron, don't update right now
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return false;
		}

		// Compare current and previous Buckram versions
		$current_buckram_version = $this->getBuckramVersion();
		$last_buckram_version = get_option( 'pressbooks_buckram_version' );
		$buckram_updated = version_compare( $current_buckram_version, $last_buckram_version ) > 0;

		// Compare current and previous theme versions
		$theme = wp_get_theme();
		$current_theme_version = $theme->get( 'Version' );
		$last_theme_version = get_option( 'pressbooks_theme_version' );
		$theme_updated = version_compare( $current_theme_version, $last_theme_version ) > 0;

		// If either Buckram or the theme were updated, rebuild the web and editor stylesheets.
		if ( $buckram_updated || $theme_updated ) {
			if ( ! get_transient( 'pressbooks_updating_stylesheet' ) ) {
				set_transient( 'pressbooks_updating_stylesheet', 1, 5 * MINUTE_IN_SECONDS );
				( new ThemeOptions() )->clearCache();
				$this->updateWebBookStyleSheet();
				update_editor_style();
				if ( $buckram_updated ) {
					update_option( 'pressbooks_buckram_version', $current_buckram_version );
				}
				if ( $theme_updated ) {
					update_option( 'pressbooks_theme_version', $current_theme_version );
				}
				delete_transient( 'pressbooks_updating_stylesheet' );
				return true;
			}
		}

		return false;
	}

	/**
	 *
	 */
	public function editor() {

		$slug = isset( $_GET['slug'] ) ? $_GET['slug'] : get_transient( 'pb-last-custom-style-slug' );
		if ( ! $slug ) {
			$slug = 'web';
		}

		$supported = array_keys( $this->supported );
		if ( ! in_array( $slug, $supported, true ) ) {
			wp_die( "Unknown slug: $slug" );
		}

		$style_post = $this->getPost( $slug );
		if ( false === $style_post ) {
			wp_die( sprintf( __( 'Unexpected Error: There was a problem trying to query slug: %s - Please contact technical support.', 'pressbooks' ), $slug ) );
		}

		set_transient( 'pb-last-custom-style-slug', $slug );

		require( PB_PLUGIN_DIR . 'templates/admin/custom-styles.php' );
	}

	/**
	 * Render dropdown and JavaScript for slugs.
	 *
	 * @param string $slug
	 *
	 * @return string
	 */
	public function renderDropdownForSlugs( $slug ) {
		$select_name = 'slug';
		$select_id = $select_name;
		$redirect_url = get_admin_url( get_current_blog_id(), '/themes.php?page=' . $this::PAGE . '&slug=' );
		$html = '';

		$html .= "
	<script type='text/javascript'>
    // <![CDATA[
	jQuery.noConflict();
	jQuery(function ($) {
		$('#" . $select_id . "').change(function() {
		  window.location = '" . $redirect_url . "' + $(this).val();
		});
	});
	// ]]>
    </script>";

		$html .= '<select id="' . $select_id . '" name="' . $select_name . '">';
		foreach ( $this->supported as $key => $val ) {
			$html .= '<option value="' . $key . '"';
			if ( $key === $slug ) {
				$html .= ' selected="selected"';
			}
			if ( 'Web' === $val ) {
				$val = __( 'Web', 'pressbooks' );
			}
			$html .= '>' . $val . '</option>';
		}
		$html .= '</select>';

		return $html;
	}

	/**
	 * Render table for revisions.
	 *
	 * @param string $slug
	 * @param int $post_id
	 *
	 * @return string
	 */
	public function renderRevisionsTable( $slug, $post_id ) {

		$args = [
			'posts_per_page' => 10,
			'post_type' => 'revision',
			'post_status' => 'inherit',
			'post_parent' => $post_id,
			'orderby' => 'date',
			'order' => 'DESC',
		];

		$q = new \WP_Query();
		$results = $q->query( $args );

		$html = '<table class="widefat fixed" cellspacing="0">';
		$html .= '<thead><th>' . __( 'Last 10 Revisions', 'pressbooks' ) . ' <em>(' . $this->supported[ $slug ] . ')</em> </th></thead><tbody>';
		foreach ( $results as $post ) {
			$html .= '<tr><td>' . wp_post_revision_title( $post ) . ' ';
			$html .= __( 'by', 'pressbooks' ) . ' ' . get_userdata( $post->post_author )->user_login . '</td></tr>';
		}
		$html .= '</tbody></table>';

		return $html;
	}

	/**
	 * Save custom styles to database
	 */
	public function formSubmit() {

		if ( empty( $this->isFormSubmission() ) || empty( current_user_can( 'edit_others_posts' ) ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		// Process form
		if ( isset( $_GET['custom_styles'] ) && $_GET['custom_styles'] === 'yes' && isset( $_POST['your_styles'] ) && check_admin_referer( 'pb-custom-styles' ) ) {
			$slug = isset( $_POST['slug'] ) ? $_POST['slug'] : 'web';
			$redirect_url = get_admin_url( get_current_blog_id(), '/themes.php?page=' . $this::PAGE . '&slug=' . $slug );

			if ( ! isset( $_POST['post_id'], $_POST['post_id_integrity'] ) ) {
				debug_error_log( __METHOD__ . ' error: Missing post ID' );
				\Pressbooks\Redirect\location( $redirect_url . '&custom_styles_error=true' );
			}
			if ( md5( NONCE_KEY . $_POST['post_id'] ) !== $_POST['post_id_integrity'] ) {
				// A hacker trying to overwrite posts?.
				debug_error_log( __METHOD__ . ' error: unexpected value for post_id_integrity' );
				\Pressbooks\Redirect\location( $redirect_url . '&custom_styles_error=true' );
			}

			// Remove wp_filter_post_kses, this causes CSS escaping issues
			remove_filter( 'content_save_pre', 'wp_filter_post_kses' );
			remove_filter( 'content_filtered_save_pre', 'wp_filter_post_kses' );
			remove_all_filters( 'content_save_pre' );

			// Write to database
			$my_post = [
				'ID' => absint( $_POST['post_id'] ),
				'post_content' => \Pressbooks\Sanitize\cleanup_css( $_POST['your_styles'] ),
			];
			$response = wp_update_post( $my_post, true );

			if ( is_wp_error( $response ) ) {
				// Something went wrong?
				debug_error_log( __METHOD__ . ' error, wp_update_post(): ' . $response->get_error_message() );
				\Pressbooks\Redirect\location( $redirect_url . '&custom_styles_error=true' );
			}

			if ( $slug === 'web' ) {
				// a recompile will be triggered whenever the user saves custom styles targeting web
				$this->updateWebBookStyleSheet();
			}

			// Ok!
			\Pressbooks\Redirect\location( $redirect_url );
		}

	}


	/**
	 * Check if a user submitted something to themes.php?page=pb_custom_styles
	 *
	 * @return bool
	 */
	public function isFormSubmission() {

		if ( empty( $_REQUEST['page'] ) ) {
			return false;
		}

		if ( $this::PAGE !== $_REQUEST['page'] ) {
			return false;
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			return true;
		}

		if ( count( $_GET ) > 1 ) {
			return true;
		}

		return false;
	}

}
