<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks;


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
	public $supported = array(
		'web' => 'Web',
		'epub' => 'Ebook',
		'prince' => 'PDF',
	);


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
	 * @see \PressBooks\Activation::wpmuActivate
	 * @see \PressBooks\Metadata::upgradeCustomCss
	 *
	 * @param string $slug post_name
	 *
	 * @return \WP_Post|bool
	 */
	function getPost( $slug ) {

		// Supported post names (ie. slugs)
		$supported = array_keys( $this->supported );
		if ( ! in_array( $slug, $supported ) ) {
			return false;
		}

		$args = array(
			'name' => $slug,
			'post_type' => 'custom-css',
			'posts_per_page' => 1,
			'post_status' => 'publish',
			'orderby' => 'modified',
			'no_found_rows' => true,
			'cache_results' => true,
		);

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

		$path = \PressBooks\Utility\get_media_prefix() . 'custom-css/';
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

		return ( 'pressbooks-custom-css' == get_stylesheet() );
	}


	/**
	 * Is the romanize parts option true?
	 *
	 * @return bool
	 */
	static function isRomanized() {

		$options = get_option( 'pressbooks_theme_options_pdf' );
		
		return ( @$options['pdf_romanize_parts'] );
	}


	// ----------------------------------------------------------------------------------------------------------------
	// Catch form submissions
	// ----------------------------------------------------------------------------------------------------------------


	/**
	 * Save custom CSS to database (and filesystem)
	 *
	 * @see pressbooks/admin/templates/custom-css.php
	 */
	static function formSubmit() {

		if ( false == static::isFormSubmission() || false == current_user_can( 'edit_theme_options' ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		// Process form
		if ( 'yes' == @$_GET['customcss'] && isset( $_POST['my_custom_css'] ) && check_admin_referer( 'pb-custom-css' ) ) {

			$slug = isset( $_POST['slug'] ) ? $_POST['slug'] : 'web';
			$redirect_url = get_bloginfo( 'url' ) . '/wp-admin/themes.php?page=pb_custom_css&slug=' . $slug;

			if ( @$_POST['post_id_integrity'] != md5( NONCE_KEY . @$_POST['post_id'] ) ) {
				// A hacker trying to overwrite posts?.
				error_log( '\PressBooks\CustomCss::formSubmit error: unexpected value for post_id_integrity' );
				\PressBooks\Redirect\location( $redirect_url . '&customcss_error=true' );
			}

			// Write to database
			$my_post = array(
				'ID' => absint( $_POST['post_id'] ),
				'post_content' => static::cleanupCss( $_POST['my_custom_css'] ),
			);
			$response = wp_update_post( $my_post, true );

			if ( is_wp_error( $response ) ) {
				// Something went wrong?
				error_log( '\PressBooks\CustomCss::formSubmit error, wp_update_post(): ' . $response->get_error_message() );
				\PressBooks\Redirect\location( $redirect_url . '&customcss_error=true' );
			}

			// Write to file
			$my_post['post_content'] = stripslashes( $my_post['post_content'] ); // We purposely send \\A0 to WordPress, but we want to send \A0 to the file system
			$filename = static::getCustomCssFolder() . sanitize_file_name( $slug . '.css' );
			file_put_contents( $filename, $my_post['post_content'] );

			// Update "version"
			update_option( 'pressbooks_last_custom_css', time() );

			// Ok!
			\PressBooks\Redirect\location( $redirect_url );
		}

	}


	/**
	 * Check if a user submitted something to themes.php?page=pb_custom_css
	 *
	 * @return bool
	 */
	static function isFormSubmission() {

		if ( 'pb_custom_css' != @$_REQUEST['page'] ) {
			return false;
		}

		if ( ! empty( $_POST ) ) {
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

		if ( $css != $prev )
			$warnings[] = 'preg_replace() double escaped unicode escape sequences';

		$css = str_replace( '<=', '&lt;=', $css ); // Some people put weird stuff in their CSS, KSES tends to be greedy
		$css = wp_kses_split( $prev = $css, array(), array() );
		$css = str_replace( '&gt;', '>', $css ); // kses replaces lone '>' with &gt;
		$css = strip_tags( $css );

		if ( $css != $prev )
			$warnings[] = 'kses() and strip_tags() do not match';

		// TODO: Something with $warnings[]

		return $css;
	}


}
