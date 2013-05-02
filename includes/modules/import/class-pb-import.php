<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks\Import;


use PressBooks\Import\Epub\Epub201;

require_once( PB_PLUGIN_DIR . 'symbionts/htmLawed/htmLawed.php' );

abstract class Import {

	/**
	 * Email addresses to send log errors.
	 *
	 * @var array
	 */
	public $errors_email = array(
		'bpayne@bccampus.ca',
		'michael@4horsemen.de',
		'errors@pressbooks.com'
	);


	/**
	 * Mandatory setCurrentImportOption() method, creates WP option 'pressbooks_current_import'
	 *
	 * $upload should look something like:
	 *     Array (
	 *       [file] => /home/dac514/public_html/bdolor/wp-content/uploads/sites/2/2013/04/Hello-World-13662149822.epub
	 *       [url] => http://localhost/~dac514/bdolor/helloworld/wp-content/uploads/sites/2/2013/04/Hello-World-13662149822.epub
	 *       [type] => application/epub+zip
	 *     )
	 *
	 * 'pressbooks_current_import' should look something like:
	 *     Array (
	 *       [file] => '/home/dac514/public_html/bdolor/wp-content/uploads/sites/2/imports/Hello-World-1366214982.epub'
	 *       [file_type] => 'application/epub+zip'
	 *       [type_of] => 'epub'
	 *       [chapters] => Array (
	 *         [some-id] => 'Some title'
	 *         [front-cover] => 'Front Cover'
	 *         [chapter-001] => 'Some other title'
	 *       )
	 *     )
	 *
	 * @see wp_handle_upload
	 *
	 * @param array $upload An associative array of file attributes
	 *
	 * @return bool
	 */
	abstract function setCurrentImportOption( array $upload );


	/**
	 * @param array $current_import
	 *
	 * @return mixed
	 */
	abstract function import( array $current_import );


	/**
	 * Delete 'pressbooks_current_import' option, delete the file too.
	 *
	 * @return bool
	 */
	function revokeCurrentImport() {

		$current_import = get_option( 'pressbooks_current_import' );

		if ( is_array( $current_import ) && isset( $current_import['file'] ) && is_file( $current_import['file'] ) ) {
			unlink( $current_import['file'] );
		}

		return delete_option( 'pressbooks_current_import' );
	}


	/**
	 * @return int
	 */
	protected function getChapterParent() {

		$q = new \WP_Query();

		$args = array(
			'post_type' => 'part',
			'posts_per_page' => 1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'no_found_rows' => true,
		);

		$results = $q->query( $args );

		return absint( $results[0]->ID );
	}


	/**
	 * @param $id
	 *
	 * @return bool
	 */
	protected function flaggedForImport( $id ) {

		if ( ! @is_array( $_POST['chapters'] ) )
			return false;

		if ( ! @isset( $_POST['chapters'][$id]['import'] ) )
			return false;

		return ( 1 == $_POST['chapters'][$id]['import'] ? true : false );
	}


	/**
	 * @param string $id
	 *
	 * @return string
	 */
	protected function determinePostType( $id ) {

		$supported_types = array( 'front-matter', 'chapter', 'back-matter' );
		$default = 'chapter';

		if ( ! @is_array( $_POST['chapters'] ) )
			return $default;

		if ( ! @isset( $_POST['chapters'][$id]['type'] ) )
			return $default;

		if ( ! in_array( $_POST['chapters'][$id]['type'], $supported_types ) )
			return $default;

		return $_POST['chapters'][$id]['type'];
	}


	/**
	 * Tidy HTML
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	protected function tidy( $html ) {

		// Reduce the vulnerability for scripting attacks

		$config = array(
			'safe' => 1,
		);

		return htmLawed( $html, $config );
	}


	/**
	 *
	 */
	static public function formSubmit() {

		// --------------------------------------------------------------------------------------------------------
		// Sanity check

		if ( false == static::isFormSubmission() || false == current_user_can( 'edit_posts' ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		if ( 'yes' != @$_GET['import'] || false == check_admin_referer( 'pb-import' ) ) {
			// Not a valid submission, bail.
			return;
		}

		// --------------------------------------------------------------------------------------------------------
		// Determine at what stage of the import we are and do something about it

		$redirect_url = get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=pb_import';
		$current_import = get_option( 'pressbooks_current_import' );

		if ( is_array( @$_POST['chapters'] ) && is_array( $current_import ) && isset( $current_import['file'] ) ) {

			// --------------------------------------------------------------------------------------------------------
			// Do Import

			@set_time_limit( 300 );

			$ok = false;
			switch ( $current_import['type_of'] ) {

				case 'epub':
					$importer = new Epub\Epub201();
					$importer->import( $current_import );
					break;

				case 'wxr':
					$importer = new Wordpress\Wxr();
					$importer->import( $current_import );
					break;
			}
			if ( ! $ok ) {
				// TODO: Deal with error
			}

		} elseif ( ! @empty( $_FILES['import_file']['name'] ) && @$_POST['type_of'] ) {

			// --------------------------------------------------------------------------------------------------------
			// Set the 'pressbooks_current_import' option

			$allowed_file_types = array( 'epub' => 'application/epub+zip', 'xml' => 'application/xml' );
			$overrides = array( 'test_form' => false, 'mimes' => $allowed_file_types  );

			if ( ! function_exists( 'wp_handle_upload' ) )
				require_once( ABSPATH . 'wp-admin/includes/file.php' );

			$upload = wp_handle_upload( $_FILES['import_file'], $overrides );

			if ( ! empty( $upload['error'] ) ) {
				$_SESSION['pb_notices'][] = $upload['error'];
				\PressBooks\Redirect\location( $redirect_url );
			}

			$ok = false;
			switch ( $_POST['type_of'] ) {

				case 'wxr':
					$importer = new Wordpress\Wxr();
					$ok = $importer->setCurrentImportOption( $upload );
					break;

				case 'epub':
					$importer = new Epub\Epub201();
					$ok = $importer->setCurrentImportOption( $upload );
					break;
			}
			if ( ! $ok ) {
				// TODO: Deal with error
			}
		}

		\PressBooks\Redirect\location( $redirect_url );
	}


	/**
	 * Check if a user submitted something to admin.php?page=pb_import
	 *
	 * @return bool
	 */
	static function isFormSubmission() {

		if ( 'pb_import' != @$_REQUEST['page'] ) {
			return false;
		}

		if ( ! empty ( $_POST ) ) {
			return true;
		}

		if ( count( $_GET ) > 1 ) {
			return true;
		}

		return false;
	}


	/**
	 * Log errors using wp_mail() and error_log(), include useful WordPress info.
	 *
	 * @param string $message
	 * @param array  $more_info
	 */
	function logError( $message, array $more_info = array() ) {

		/** $var \WP_User $current_user */
		global $current_user;

		$subject = get_class( $this );

		$info = array(
			'time' => strftime( '%c' ),
			'user' => ( isset ( $current_user ) ? $current_user->user_login : '__UNKNOWN__' ),
			'site_url' => site_url(),
			'blog_id' => get_current_blog_id(),
			'theme' => '' . wp_get_theme(), // Stringify by appending to empty string
		);

		$message = print_r( array_merge( $info, $more_info ), true ) . $message;

		// ------------------------------------------------------------------------------------------------------------
		// Write to error log

		error_log( $subject . "\n" . $message );

		// ------------------------------------------------------------------------------------------------------------
		// Email logs

		if ( @$current_user->user_email && get_option( 'pressbooks_email_validation_logs' ) ) {
			$this->errors_email[] = $current_user->user_email;
		}

		add_filter( 'wp_mail_from', function ( $from_email ) {
			return str_replace( 'wordpress@', 'pressbooks@', $from_email );
		} );
		add_filter( 'wp_mail_from_name', function ( $from_name ) {
			return 'PressBooks';
		} );

		foreach ( $this->errors_email as $email ) {
			wp_mail( $email, $subject, $message );
		}
	}


}
