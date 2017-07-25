<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

class CustomCss {

	/**
	 * Supported formats
	 * Array key is slug, array val is text (passed to _e() where necessary.)
	 * 'web' is considered the default key because web isn't going anywhere.
	 * All keys must match an *existing* WP post where post_name = __key__ and post_type = 'custom-css'
	 * If the key is not 'web' then it must map to: themes-book/__SOME_THEME__/export/__key__/style.css
	 *
	 * @var array
	 */
	public $supported = [
		'web' => 'Web',
		'epub' => 'Ebook',
		'prince' => 'PDF',
	];

	function __construct() {
	}

	/**
	 * Get CSS from database
	 *
	 * @param $slug
	 *
	 * @return string
	 */
	function getCss( $slug ) {

		$result = $this->getPost( $slug );

		if ( ! $result ) {
			return '';
		}

		return $result->post_content;
	}

	/**
	 * Returns the latest "custom-css" post
	 *
	 * @see \Pressbooks\Activation::wpmuActivate
	 * @see \Pressbooks\Metadata::upgradeCustomCss
	 *
	 * @param string $slug post_name
	 *
	 * @return \WP_Post|bool
	 */
	function getPost( $slug ) {

		// Supported post names (ie. slugs)
		$supported = array_keys( $this->supported );
		if ( ! in_array( $slug, $supported, true ) ) {
			return false;
		}

		$args = [
			'name' => $slug,
			'post_type' => 'custom-css',
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
	 * Get the fullpath to the Custom CSS folder
	 * Create if not there.
	 *
	 * @return string fullpath
	 */
	static function getCustomCssFolder() {

		$path = \Pressbooks\Utility\get_media_prefix() . 'custom-css/';
		if ( ! file_exists( $path ) ) {
			mkdir( $path, 0775, true );
		}

		return $path;
	}


	/**
	 * Is the current theme the custom css theme?
	 *
	 * @return bool
	 */
	static function isCustomCss() {
		return ( 'pressbooks-custom-css' === get_stylesheet() );
	}


	/**
	 * Is the romanize parts option true?
	 *
	 * @return bool
	 */
	static function isRomanized() {

		$options = get_option( 'pressbooks_theme_options_pdf' );
		if ( isset( $options['pdf_romanize_parts'] ) ) {
			return (bool) ( $options['pdf_romanize_parts'] );
		}
		return false;
	}


	// ----------------------------------------------------------------------------------------------------------------
	// Catch form submissions
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * Save custom CSS to database (and filesystem)
	 *
	 * @see pressbooks/templates/admin/custom-css.php
	 */
	static function formSubmit() {

		if ( empty( static::isFormSubmission() ) || empty( current_user_can( 'edit_others_posts' ) ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		// Process form
		if ( isset( $_GET['customcss'] ) && $_GET['customcss'] === 'yes' && isset( $_POST['my_custom_css'] ) && check_admin_referer( 'pb-custom-css' ) ) {
			$slug = isset( $_POST['slug'] ) ? $_POST['slug'] : 'web';
			$redirect_url = get_admin_url( get_current_blog_id(), '/themes.php?page=pb_custom_css&slug=' . $slug );

			if ( isset( $_POST['post_id'] ) && isset( $_POST['post_id_integrity'] ) && md5( NONCE_KEY . $_POST['post_id'] ) !== $_POST['post_id_integrity'] ) {
				// A hacker trying to overwrite posts?.
				error_log( '\Pressbooks\CustomCss::formSubmit error: unexpected value for post_id_integrity' );
				\Pressbooks\Redirect\location( $redirect_url . '&customcss_error=true' );
			}

			// Write to database
			$my_post = [
				'ID' => absint( $_POST['post_id'] ),
				'post_content' => static::cleanupCss( $_POST['my_custom_css'] ),
			];
			$response = wp_update_post( $my_post, true );

			if ( is_wp_error( $response ) ) {
				// Something went wrong?
				error_log( '\Pressbooks\CustomCss::formSubmit error, wp_update_post(): ' . $response->get_error_message() );
				\Pressbooks\Redirect\location( $redirect_url . '&customcss_error=true' );
			}

			// Write to file
			$my_post['post_content'] = stripslashes( $my_post['post_content'] ); // We purposely send \\A0 to WordPress, but we want to send \A0 to the file system
			$filename = static::getCustomCssFolder() . sanitize_file_name( $slug . '.css' );
			file_put_contents( $filename, $my_post['post_content'] );

			// Update "version"
			update_option( 'pressbooks_last_custom_css', time() );

			// Ok!
			\Pressbooks\Redirect\location( $redirect_url );
		}

	}


	/**
	 * Check if a user submitted something to themes.php?page=pb_custom_css
	 *
	 * @return bool
	 */
	static function isFormSubmission() {

		if ( empty( $_REQUEST['page'] ) ) {
			return false;
		}

		if ( 'pb_custom_css' !== $_REQUEST['page'] ) {
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


	/**
	 * Clean up CSS.
	 * Minimal intervention, but prevent users from injecting garbage.
	 *
	 * @param $css
	 *
	 * @return string
	 */
	protected static function cleanupCss( $css ) {

		$css = stripslashes( $css );

		$css = preg_replace( '/\\\\([0-9a-fA-F]{2,4})/', '\\\\\\\\$1', $prev = $css );

		if ( $css !== $prev ) {
			$warnings[] = 'preg_replace() double escaped unicode escape sequences';
		}

		$css = str_replace( '<=', '&lt;=', $css ); // Some people put weird stuff in their CSS, KSES tends to be greedy
		$css = wp_kses_split( $prev = $css, [], [] );
		$css = str_replace( '&gt;', '>', $css ); // kses replaces lone '>' with &gt;
		$css = strip_tags( $css );

		if ( $css !== $prev ) {
			$warnings[] = 'kses() and strip_tags() do not match';
		}

		// TODO: Something with $warnings[]

		return $css;
	}

	/**
	 * Determine base theme that was used for the selected Custom CSS.
	 *
	 * @param $slug string
	 *
	 * @return string
	 */
	static function getBaseTheme( $slug ) {
		$filename = static::getCustomCssFolder() . sanitize_file_name( $slug . '.css' );
		if ( ! file_exists( $filename ) ) {
			return false;
		}
		$theme = get_file_data( $filename, [ 'ThemeURI' => 'Theme URI' ] );
		$theme_slug = str_replace( [ 'http://pressbooks.com/themes/', 'https://pressbooks.com/themes/' ], [ '', '' ], $theme['ThemeURI'] );

		return untrailingslashit( $theme_slug );
	}

}
