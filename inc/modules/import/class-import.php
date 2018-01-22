<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Import;

use function \Pressbooks\Utility\getset;
use function \Pressbooks\Utility\debug_error_log;

abstract class Import {

	/**
	 * Email addresses to send logs.
	 *
	 * @deprecated
	 * @var array
	 */
	static $logsEmail = [];


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
		return self::_revokeCurrentImport();
	}

	/**
	 * @return bool
	 */
	protected static function _revokeCurrentImport() {

		$current_import = get_option( 'pressbooks_current_import' );

		if ( is_array( $current_import ) && isset( $current_import['file'] ) && is_file( $current_import['file'] ) ) {
			unlink( $current_import['file'] );
		}

		\Pressbooks\Book::deleteBookObjectCache();
		delete_transient( 'dirsize_cache' );
		/** @see get_dirsize() */

		return delete_option( 'pressbooks_current_import' );
	}

	/**
	 * Create a temporary file that automatically gets deleted on __sleep()
	 *
	 * @return string fullpath
	 */
	function createTmpFile() {

		return \Pressbooks\Utility\create_tmp_file();
	}


	/**
	 * Get a valid Part id to act as post_parent to a Chapter
	 *
	 * @return int
	 */
	protected function getChapterParent() {

		$q = new \WP_Query();

		$args = [
			'post_type' => 'part',
			'post_status' => 'publish',
			'posts_per_page' => 1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'no_found_rows' => true,
		];

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

		$chapters = getset( '_POST', 'chapters' );

		if ( ! is_array( $chapters ) ) {
			return false;
		}

		if ( ! isset( $chapters[ $id ] ) ) {
			return false;
		}

		if ( ! isset( $chapters[ $id ]['import'] ) ) {
			return false;
		}

		return ( 1 === (int) $chapters[ $id ]['import'] ? true : false );
	}


	/**
	 * Check against what the user selected for post_type in our form
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function determinePostType( $id ) {

		$chapters = getset( '_POST', 'chapters' );
		$supported_types = apply_filters( 'pb_import_custom_post_types', [ 'front-matter', 'chapter', 'part', 'back-matter', 'metadata' ] );
		$default = 'chapter';

		if ( ! is_array( $chapters ) ) {
			return $default;
		}

		if ( ! isset( $chapters[ $id ] ) && ! isset( $chapters[ $id ]['type'] ) ) {
			return $default;
		}

		if ( ! in_array( $chapters[ $id ]['type'], $supported_types, true ) ) {
			return $default;
		}

		return $chapters[ $id ]['type'];
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
		return \Pressbooks\Image\proper_image_extension( $path_to_file, $filename );
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

		$config = [
			'safe' => 1,
		];

		return \Pressbooks\HtmLawed::filter( $html, $config );
	}


	/**
	 * Catch form submissions
	 *
	 * @see pressbooks/templates/admin/import.php
	 */
	static public function formSubmit() {

		// --------------------------------------------------------------------------------------------------------
		// Sanity check

		if ( false === static::isFormSubmission() || false === current_user_can( 'edit_posts' ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		// --------------------------------------------------------------------------------------------------------
		// Determine at what stage of the import we are and do something about it

		$redirect_url = get_admin_url( get_current_blog_id(), '/tools.php?page=pb_import' );
		$current_import = get_option( 'pressbooks_current_import' );

		// Revoke
		if ( ! empty( $_GET['revoke'] ) && check_admin_referer( 'pb-revoke-import' ) ) {
			self::_revokeCurrentImport();
			\Pressbooks\Redirect\location( $redirect_url );
		}

		if ( ! empty( $_GET['import'] ) && isset( $_POST['chapters'] ) && is_array( $_POST['chapters'] ) && is_array( $current_import ) && isset( $current_import['file'] ) && check_admin_referer( 'pb-import' ) ) {
			self::doImport( $current_import );
		} elseif ( isset( $_GET['import'] ) && ! empty( $_POST['import_type'] ) && check_admin_referer( 'pb-import' ) ) {
			self::setImportOptions();
		}

		// Default, back to form
		\Pressbooks\Redirect\location( $redirect_url );
	}

	/**
	 * @param array $current_import
	 */
	static protected function doImport( array $current_import ) {

		// Set post status
		$current_import['default_post_status'] = ( isset( $_POST['import_as_drafts'] ) ) ? 'export-only' : 'publish';

		// --------------------------------------------------------------------------------------------------------
		// Do Import

		@set_time_limit( 300 ); // @codingStandardsIgnoreLine

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
				break;

			default:
				/**
				 * Allows users to add a custom import routine for custom import type.
				 *
				 * @since 3.9.6
				 *
				 * @param \Pressbooks\Modules\Import\Import $value
				 */
				$importer = apply_filters( 'pb_initialize_import', null );
				if ( is_object( $importer ) ) {
					$ok = $importer->import( $current_import );
				}
				break;
		}

		$msg = "Tried to import a file of type {$current_import['type_of']} and ";
		$msg .= ( $ok ) ? 'succeeded :)' : 'failed :(';
		self::log( $msg, $current_import );

		if ( $ok ) {
			// Success! Redirect to organize page
			$success_url = get_admin_url( get_current_blog_id(), '/admin.php?page=pb_organize' );
			\Pressbooks\Redirect\location( $success_url );
		}
	}

	/**
	 * @return bool
	 */
	static protected function setImportOptions() {

		if ( ! check_admin_referer( 'pb-import' ) ) {
			return false;
		}

		// --------------------------------------------------------------------------------------------------------
		// Set the 'pressbooks_current_import' option

		/**
		 * Allows users to append import options to the list of allowed file types.
		 *
		 * @since 3.9.6
		 *
		 * @param array $value The list of currently allowed file types.
		 */
		$allowed_file_types = apply_filters(
			'pb_import_file_types', [
				'epub' => 'application/epub+zip',
				'xml' => 'application/xml',
				'odt' => 'application/vnd.oasis.opendocument.text',
				'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
			]
		);

		$overrides = [
			'test_form' => false,
			'test_type' => false,
			'mimes' => $allowed_file_types,
		];

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		if ( getset( '_POST', 'import_type' ) === 'url' ) {
			self::createFileFromUrl();
		}

		if ( empty( $_FILES['import_file']['name'] ) ) {
			return false;
		}

		$upload = wp_handle_upload( $_FILES['import_file'], $overrides );

		if ( ! empty( $upload['error'] ) ) {
			// Error, redirect back to form
			$_SESSION['pb_notices'][] = $upload['error'];
			return false;
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

			case 'html':
				$importer = new Html\Xhtml();
				$ok = $importer->setCurrentImportOption( $upload );
				break;

			default:
				/**
				 * Allows users to add custom import routine for custom import type
				 * via HTTP GET requests
				 *
				 * @since 4.0.0
				 *
				 * @param \Pressbooks\Modules\Import\Import $value
				 */
				$importer = apply_filters( 'pb_initialize_import', null );
				if ( is_object( $importer ) ) {
					/** @var \Pressbooks\Modules\Import\Import $importer */
					$ok = $importer->setCurrentImportOption( $upload );
				}
		}

		$msg = "Tried to upload a file of type {$_POST['type_of']} and ";
		$msg .= ( $ok ) ? 'succeeded :)' : 'failed :(';
		self::log( $msg, $upload );

		if ( ! $ok ) {
			// Not ok?
			$_SESSION['pb_errors'][] = sprintf( __( 'Your file does not appear to be a valid %s.', 'pressbooks' ), strtoupper( $_POST['type_of'] ) );
			unlink( $upload['file'] );
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	static protected function createFileFromUrl() {

		if ( ! check_admin_referer( 'pb-import' ) ) {
			return false;
		}

		// check if it's a valid url
		$url = getset( '_POST', 'import_http' );
		if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$_SESSION['pb_errors'][] = __( 'Your URL does not appear to be valid', 'pressbooks' );
			return false;
		}

		// HEAD request, check for a valid response from server
		$remote_head = wp_remote_head( $url );

		// Something failed
		if ( is_wp_error( $remote_head ) ) {
			debug_error_log( '\Pressbooks\Modules\Import::formSubmit html import error, wp_remote_head()' . $remote_head->get_error_message() );
			$_SESSION['pb_errors'][] = $remote_head->get_error_message();
			return false;
		}

		// weebly.com (and likely some others) prevent HEAD requests, but allow GET requests
		if ( 200 !== $remote_head['response']['code'] && 405 !== $remote_head['response']['code'] ) {
			$_SESSION['pb_errors'][] = __( 'The website you are attempting to reach is not returning a successful response header on a HEAD request: ', 'pressbooks' ) . $remote_head['response']['code'];
			return false;
		}

		if ( getset( '_POST', 'type_of' ) === 'html' && false === strpos( $remote_head['headers']['content-type'], 'text/html' ) && false === strpos( $remote_head['headers']['content-type'], 'application/xhtml+xml' ) ) {
			$_SESSION['pb_errors'][] = __( 'The website you are attempting to reach is not returning HTML content', 'pressbooks' );
			return false;
		}

		$response = \Pressbooks\Utility\remote_get_retry( $url, [ 'timeout' => 90 ] );
		$tmp_file = \Pressbooks\Utility\create_tmp_file();
		\Pressbooks\Utility\put_contents( $tmp_file, wp_remote_retrieve_body( $response ) );

		// Basename
		$basename = explode( '?', basename( $url ) );
		$basename = array_shift( $basename );
		$basename = explode( '#', $basename )[0]; // Remove trailing anchors
		$basename = sanitize_file_name( urldecode( $basename ) );

		$_FILES['import_file'] = [
			'name' => $basename,
			'type' => \Pressbooks\Media\mime_type( $tmp_file ),
			'tmp_name' => $tmp_file,
			'error' => 0,
			'size' => filesize( $tmp_file ),
		];

		return true;
	}


	/**
	 * Check if a user submitted something to options-general.php?page=pb_import
	 *
	 * @return bool
	 */
	static function isFormSubmission() {

		if ( empty( $_REQUEST['page'] ) ) {
			return false;
		}

		if ( 'pb_import' !== $_REQUEST['page'] ) {
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
	static function log( $message, array $more_info = [] ) {

		/** $var \WP_User $current_user */
		global $current_user;

		$subject = '[ Import Log ]';

		$info = [
			'time' => strftime( '%c' ),
			'user' => ( isset( $current_user ) ? $current_user->user_login : '__UNKNOWN__' ),
			'site_url' => site_url(),
			'blog_id' => get_current_blog_id(),
		];

		$message = print_r( array_merge( $info, $more_info ), true ) . $message; // @codingStandardsIgnoreLine

		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			\Pressbooks\Utility\email_error_log( self::$logsEmail, $subject, $message );
		}
	}


}
