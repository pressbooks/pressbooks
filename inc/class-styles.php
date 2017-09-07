<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

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
		// TODO: Use built-in WP when released, see: https://core.trac.wordpress.org/ticket/12423
		if ( isset( $_REQUEST['page'] ) && $_REQUEST['page'] === $this::PAGE ) {
			$codemirror_version = '5.29.0';
			$assets = new \PressbooksMix\Assets( 'pressbooks', 'plugin' );
			add_action(
				'wp_default_scripts', function ( \WP_Scripts $scripts ) use ( $codemirror_version, $assets ) {
					$scripts->add( 'codemirror',  $assets->getPath( 'scripts/codemirror.js' ), [], $codemirror_version );
					$scripts->add( 'codemirror-mode-css', $assets->getPath( 'scripts/codemirror-mode-css.js' ), [ 'codemirror' ], $codemirror_version );
				}
			);
			add_action(
				'wp_default_styles', function ( \WP_Styles $styles ) use ( $codemirror_version, $assets ) {
					$codemirror_version = '5.29.0';
					$styles->add( 'codemirror',  $assets->getPath( 'styles/codemirror.css' ), [], $codemirror_version );
				}
			);
			add_action(
				'admin_enqueue_scripts', function () {
					wp_enqueue_script( 'codemirror-mode-css' );
					wp_enqueue_style( 'codemirror' );
				}
			);
		}

		add_action( 'init', function () {
			// Admin Menu
			add_action( 'admin_menu', function () {
				add_theme_page( __( 'Custom Styles', 'pressbooks' ), __( 'Custom Styles', 'pressbooks' ), 'edit_others_posts', $this::PAGE, [ $this, 'editor' ] );
			}, 11 );
			// Register Post Types
			$this->registerPosts();
		} );

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

		$post = [ 'post_status' => 'publish', 'post_author' => wp_get_current_user()->ID ];

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
	function registerPosts() {
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
	public  function getPost( $slug ) {

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
	public  function isCurrentThemeCompatible( $version = 1, $theme = null ) {

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
	 * @param array|string $overrides (optional)
	 *
	 * @return string
	 */
	public function customizeWeb( $overrides = [] ) {
		$path = $this->getPathToWebScss();
		if ( $path ) {
			return $this->customize( 'web', file_get_contents( $path ), $overrides );
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
			return $this->customize( 'prince', file_get_contents( $path ), $overrides );
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
			return $this->customize( 'epub', file_get_contents( $path ), $overrides );
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

		if ( $this->isCurrentThemeCompatible( 1 ) ) {
			$css = $this->sass->compile(
				$scss,
				[
					$this->sass->pathToUserGeneratedSass(),
					$this->sass->pathToPartials(),
					$this->sass->pathToFonts(),
					get_stylesheet_directory(),
				]
			);
		} elseif ( $this->isCurrentThemeCompatible( 2 ) ) {
			$css = $this->sass->compile(
				$scss,
				$this->sass->defaultIncludePaths( $type )
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
				$css = str_replace( $token, file_get_contents( $replace_with ), $css );
			}
		}

		return $css;
	}

	/**
	 * Update and save the supplementary webBook stylesheet which incorporates user options, etc.
	 *
	 * @return void
	 */
	function updateWebBookStyleSheet() {

		$overrides = apply_filters( 'pb_web_css_override', '' ) . "\n";

		// Populate $url-base variable so that links to images and other assets remain intact
		$scss = '$url-base: \'' . get_stylesheet_directory_uri() . "/';\n";

		if ( $this->isCurrentThemeCompatible( 1 ) ) {
			$scss .= file_get_contents( realpath( get_stylesheet_directory() . '/style.scss' ) );
		} elseif ( $this->isCurrentThemeCompatible( 2 ) ) {
			$scss .= file_get_contents( realpath( get_stylesheet_directory() . '/assets/styles/web/style.scss' ) );
		} else {
			return;
		}

		$custom_styles = $this->getWebPost();
		if ( $custom_styles && ! empty( $custom_styles->post_content ) ) {
			// append the user's custom styles to the theme stylesheet prior to compilation
			$scss .= "\n" . $custom_styles->post_content;
		}

		$css = $this->customize( 'web', $scss, $overrides );
		$css = \Pressbooks\Sanitize\normalize_css_urls( $css );

		$css_file = $this->sass->pathToUserGeneratedCss() . '/style.css';
		file_put_contents( $css_file, $css );
	}

	/**
	 * If the current theme's version has increased, call updateWebBookStyleSheet().
	 *
	 * @return bool
	 */
	public function maybeUpdateWebBookStylesheet() {
		$theme = wp_get_theme();
		$current_version = $theme->get( 'Version' );
		$last_version = get_option( 'pressbooks_theme_version', $current_version );

		if ( version_compare( $current_version, $last_version ) > 0 ) {
			$this->updateWebBookStyleSheet();
			update_option( 'pressbooks_theme_version', $current_version );
			return true;
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
	function renderDropdownForSlugs( $slug ) {

		$select_id = $select_name = 'slug';
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
	function renderRevisionsTable( $slug, $post_id ) {

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
	function formSubmit() {

		if ( empty( $this->isFormSubmission() ) || empty( current_user_can( 'edit_others_posts' ) ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		// Process form
		if ( isset( $_GET['custom_styles'] ) && $_GET['custom_styles'] === 'yes' && isset( $_POST['your_styles'] ) && check_admin_referer( 'pb-custom-styles' ) ) {
			$slug = isset( $_POST['slug'] ) ? $_POST['slug'] : 'web';
			$redirect_url = get_admin_url( get_current_blog_id(), '/themes.php?page=' . $this::PAGE . '&slug=' . $slug );

			if ( ! isset( $_POST['post_id'], $_POST['post_id_integrity'] ) ) {
				error_log( __METHOD__ . ' error: Missing post ID' );
				\Pressbooks\Redirect\location( $redirect_url . '&custom_styles_error=true' );
			}
			if ( md5( NONCE_KEY . $_POST['post_id'] ) !== $_POST['post_id_integrity'] ) {
				// A hacker trying to overwrite posts?.
				error_log( __METHOD__ . ' error: unexpected value for post_id_integrity' );
				\Pressbooks\Redirect\location( $redirect_url . '&custom_styles_error=true' );
			}

			// Write to database
			$my_post = [
				'ID' => absint( $_POST['post_id'] ),
				'post_content' => \Pressbooks\Sanitize\cleanup_css( $_POST['your_styles'] ),
			];
			$response = wp_update_post( $my_post, true );

			if ( is_wp_error( $response ) ) {
				// Something went wrong?
				error_log( __METHOD__ . ' error, wp_update_post(): ' . $response->get_error_message() );
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
