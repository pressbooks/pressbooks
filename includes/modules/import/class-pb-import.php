<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Import;


require_once( ABSPATH . 'wp-admin/includes/image.php' );
require_once( ABSPATH . 'wp-admin/includes/file.php' );
require_once( ABSPATH . 'wp-admin/includes/media.php' );

abstract class Import {

	/**
	 * Email addresses to send logs.
	 *
	 * @deprecated
	 * @var array
	 */
	static $logsEmail = array(
		'errors@pressbooks.com',
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
	 * @return bool
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

		\Pressbooks\Book::deleteBookObjectCache();
		delete_transient( 'dirsize_cache' ); /** @see get_dirsize() */

		return delete_option( 'pressbooks_current_import' );
	}


	/**
	 * Create a temporary file that automatically gets deleted on __sleep()
	 *
	 * @return string fullpath
	 */
	function createTmpFile() {

		return array_search( 'uri', @array_flip( stream_get_meta_data( $GLOBALS[mt_rand()] = tmpfile() ) ) );
	}


	/**
	 * Get a valid Part id to act as post_parent to a Chapter
	 *
	 * @return int
	 */
	protected function getChapterParent() {

		$q = new \WP_Query();

		$args = array(
			'post_type' => 'part',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'no_found_rows' => true,
		);

		$results = $q->query( $args );

		return absint( $results[0]->ID );
	}


	/**
	 * Check against what the user selected for import in our form
	 *
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
	 * Check against what the user selected for post_type in our form
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function determinePostType( $id ) {

		$supported_types = apply_filters( 'pb_import_custom_post_types', array( 'front-matter', 'chapter', 'part', 'back-matter', 'metadata' ) );

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
	 * Checks if the file extension matches its mimetype, returns a modified
	 * filename if they don't match.
	 *
	 * @param string $path_to_file
	 * @param string $filename
	 *
	 * @return string - modified filename if the extension did not match the mimetype,
	 * otherwise returns the filename that was passed to it
	 */
	protected function properImageExtension( $path_to_file, $filename ) {
		$mimes = array(
			'jpg|jpeg|jpe' => 'image/jpeg',
			'gif' => 'image/gif',
			'png' => 'image/png',
		);

		// Attempt to determine the real file type of a file.
		$validate = wp_check_filetype_and_ext( $path_to_file, $filename, $mimes );

		// change filename to the extension that matches its mimetype
		if ( $validate['proper_filename'] !== false ) {
			return $validate['proper_filename'];
		} else {
			return $filename;
		}
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

		return \Htmlawed::filter( $html, $config );
	}


	/**
	 * Catch form submissions
	 *
	 * @see pressbooks/templates/admin/import.php
	 */
	static public function formSubmit() {

		// --------------------------------------------------------------------------------------------------------
		// Sanity check

		if ( false == static::isFormSubmission() || false == current_user_can( 'edit_posts' ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		// --------------------------------------------------------------------------------------------------------
		// Determine at what stage of the import we are and do something about it

		$redirect_url = get_admin_url( get_current_blog_id(), '/tools.php?page=pb_import' );
		$current_import = get_option( 'pressbooks_current_import' );

		// Revoke
		if ( @$_GET['revoke'] && check_admin_referer( 'pb-revoke-import' ) ) {
			self::revokeCurrentImport();
			\Pressbooks\Redirect\location( $redirect_url );
		}

		// only html import uses a url, not a file path
		if ( 0 !== strcmp( $current_import['type_of'], 'html' ) ) {
			// Appends 'last part' of the path to the dynamic first part of the path ($upload_dir)
			$upload_dir             = wp_upload_dir();
			$current_import['file'] = trailingslashit( $upload_dir['path'] ) . basename( $current_import['file'] );
		}
		
		if ( @$_GET['import'] && is_array( @$_POST['chapters'] ) && is_array( $current_import ) && isset( $current_import['file'] ) && check_admin_referer( 'pb-import' ) ) {

			// --------------------------------------------------------------------------------------------------------
			// Do Import

			@set_time_limit( 300 );

			$ok = false;
			switch ( $current_import['type_of'] ) {

				case 'epub':
					$importer = new Epub\Epub201();
					$ok = $importer->import( $current_import );
					break;

				case 'wxr':
					$importer = new Wordpress\Wxr();
					$ok = $importer->import( $current_import );
					break;

				case 'odt':
					$importer = new Odf\Odt();
					$ok = $importer->import( $current_import );
					break;

				case 'docx':
					$importer = new Ooxml\Docx();
					$ok = $importer->import( $current_import );
					break;

				case 'html':
					$importer = new Html\Xhtml();
					$ok = $importer->import( $current_import );
			}

			$msg = "Tried to import a file of type {$current_import['type_of']} and ";
			$msg .= ( $ok ) ? 'succeeded :)' : 'failed :(';
			self::log( $msg, $current_import );

			if ( $ok ) {
				// Success! Redirect to organize page
				$success_url = get_admin_url( get_current_blog_id(), '/admin.php?page=pressbooks' );
				\Pressbooks\Redirect\location( $success_url );
			}

		} elseif ( @$_GET['import'] && ! @empty( $_FILES['import_file']['name'] ) && @$_POST['type_of'] && check_admin_referer( 'pb-import' ) ) {

			// --------------------------------------------------------------------------------------------------------
			// Set the 'pressbooks_current_import' option

			$allowed_file_types = array(
				'epub' => 'application/epub+zip',
				'xml' => 'application/xml',
				'odt' => 'application/vnd.oasis.opendocument.text',
				'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			);
			$overrides = array( 'test_form' => false, 'mimes' => $allowed_file_types );

			if ( ! function_exists( 'wp_handle_upload' ) )
				require_once( ABSPATH . 'wp-admin/includes/file.php' );

			$upload = wp_handle_upload( $_FILES['import_file'], $overrides );

			if ( ! empty( $upload['error'] ) ) {
				// Error, redirect back to form
				$_SESSION['pb_notices'][] = $upload['error'];
				\Pressbooks\Redirect\location( $redirect_url );
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

				case 'odt':
					$importer = new Odf\Odt();
					$ok = $importer->setCurrentImportOption( $upload );
					break;

				case 'docx':
					$importer = new Ooxml\Docx();
					$ok = $importer->setCurrentImportOption( $upload );
					break;
			}

			$msg = "Tried to upload a file of type {$_POST['type_of']} and ";
			$msg .= ( $ok ) ? 'succeeded :)' : 'failed :(';
			self::log( $msg, $upload );

			if ( ! $ok ) {
				// Not ok?
				$_SESSION['pb_errors'][] = sprintf( __( 'Your file does not appear to be a valid %s.', 'pressbooks' ), strtoupper( $_POST['type_of'] ) );
				unlink ( $upload['file'] );
			}

		} elseif ( @$_GET['import'] && @$_POST['type_of'] === 'html' && check_admin_referer( 'pb-import' ) ) {

			// check if it's a valid url
			if ( false == filter_var( $_POST['import_html'], FILTER_VALIDATE_URL ) ) {
				$_SESSION['pb_errors'][] = __( 'Your URL does not appear to be valid', 'pressbooks' );
				\Pressbooks\Redirect\location( $redirect_url );
			}

			// HEAD request, check for a valid response from server
			$remote_head = wp_remote_head( $_POST['import_html'] );

			// Something failed
			if ( is_wp_error( $remote_head ) ) {
				error_log( '\Pressbooks\Modules\Import::formSubmit html import error, wp_remote_head()' . $remote_head->get_error_message() );
				$_SESSION['pb_errors'][] = $remote_head->get_error_message();
				\Pressbooks\Redirect\location( $redirect_url );
			}

			// weebly.com (and likely some others) prevent HEAD requests, but allow GET requests
			if ( 200 !== $remote_head['response']['code'] && 405 !== $remote_head['response']['code'] ) {
				$_SESSION['pb_errors'][] = __( 'The website you are attempting to reach is not returning a successful response header on a HEAD request: ' . $remote_head['response']['code'] , 'pressbooks' );
				\Pressbooks\Redirect\location( $redirect_url );
			}

			// ensure the media type is HTML (not JSON, or something we can't deal with)
			if ( false === strpos( $remote_head['headers']['content-type'], 'text/html' ) && false === strpos( $remote_head['headers']['content-type'], 'application/xhtml+xml')) {
				$_SESSION['pb_errors'][] = __( 'The website you are attempting to reach is not returning HTML content', 'pressbooks' );
				\Pressbooks\Redirect\location( $redirect_url );
			}

			// GET http request
			$body = wp_remote_get( $_POST['import_html'] );

			// check for wp error
			if ( is_wp_error( $body ) ) {
				$error_message = $body->get_error_message();
				error_log( '\Pressbooks\Modules\Import::formSubmit error, import_html' . $error_message );
				$_SESSION['pb_errors'][] = $error_message;
				\Pressbooks\Redirect\location( $redirect_url );
			}

			// check for a successful response code on GET request
			if ( 200 !== $body['response']['code'] ){
				$_SESSION['pb_errors'][] = __( 'The website you are attempting to reach is not returning a successful response on a GET request: ' . $body['response']['code'] , 'pressbooks' );
				\Pressbooks\Redirect\location( $redirect_url );
			}

			// add our url
			$body['url'] = $_POST['import_html'];

			$importer = new Html\Xhtml();
			$ok = $importer->setCurrentImportOption( $body );

			$msg = "Tried to upload a file of type {$_POST['type_of']} and ";
			$msg .= ( $ok ) ? 'succeeded :)' : 'failed :(';
			self::log( $msg, $body['headers'] );

			if ( ! $ok ) {
				// Not ok?
				$_SESSION['pb_errors'][] = sprintf( __( 'Your file does not appear to be a valid %s.', 'pressbooks' ), strtoupper( $_POST['type_of'] ) );
			}
		}
		// Default, back to form
		\Pressbooks\Redirect\location( $redirect_url );
	}


	/**
	 * Check if a user submitted something to options-general.php?page=pb_import
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
	 * Log something using wp_mail() and error_log(), include useful WordPress info.
	 *
	 * Note: This method is here temporarily. We are using it to find & fix bugs for the first iterations of import.
	 * Do not count on this method being here in the future.
	 *
	 * @deprecated
	 *
	 * @param string $message
	 * @param array $more_info
	 */
	static function log( $message, array $more_info = array() ) {

		/** $var \WP_User $current_user */
		global $current_user;

		$subject = '[ Import Log ]';

		$info = array(
			'time' => strftime( '%c' ),
			'user' => ( isset( $current_user ) ? $current_user->user_login : '__UNKNOWN__' ),
			'site_url' => site_url(),
			'blog_id' => get_current_blog_id(),
		);

		$message = print_r( array_merge( $info, $more_info ), true ) . $message;

		\Pressbooks\Utility\email_error_log( self::$logsEmail, $subject, $message );
	}


}
