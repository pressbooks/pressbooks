<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import;

use function \Pressbooks\Utility\debug_error_log;
use function \Pressbooks\Utility\getset;
use Pressbooks\Book;
use Pressbooks\Cloner;
use Pressbooks\HtmLawed;

abstract class Import {

	/**
	 * Abstract CONST
	 */
	const TYPE_OF = null;

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
	 *       [file] => /home/user/public_html/wp-content/uploads/sites/2/2013/04/Hello-World-13662149822.epub
	 *       [url] => http://localhost/~user/Hello-World-13662149822.epub (optional, can be null)
	 *       [type] => application/epub+zip
	 *     )
	 *
	 * 'pressbooks_current_import' should look something like:
	 *     Array (
	 *       [file] => '/home/user/public_html/wp-content/uploads/sites/2/imports/Hello-World-1366214982.epub'
	 *       [url] => http://localhost/~user/Hello-World-13662149822.epub (optional, can be null)
	 *       [file_type] => 'application/epub+zip'
	 *       [type_of] => 'epub'
	 *       [default_post_status] => 'draft'
	 *       [chapters] => Array (
	 *         [some-id] => 'Some title'
	 *         [front-cover] => 'Front Cover'
	 *         [chapter-001] => 'Some other title'
	 *       )
	 *       [post_types] => Array (
	 *         [some-id] => 'front-matter'
	 *         [front-cover] => 'chapter'
	 *         [chapter-001] => 'back-matter' (optional)
	 *       )
	 *       [allow_parts] => false,
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
	 * @param array $current_import WP option 'pressbooks_current_import'
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

		Book::deleteBookObjectCache();
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
			'post_status' => [ 'draft', 'web-only', 'private', 'publish' ],
			'posts_per_page' => 1,
			'orderby' => 'menu_order',
			'order' => 'ASC',
			'no_found_rows' => true,
			'cache_results' => false,
			'update_post_meta_cache' => false,
			'update_post_term_cache ' => false,
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
		$supported_types = apply_filters( 'pb_import_custom_post_types', [ 'front-matter', 'chapter', 'part', 'back-matter', 'metadata', 'glossary' ] );
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

		return HtmLawed::filter( $html, $config );
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

		if ( ! empty( $_GET['import'] ) && isset( $_POST['chapters'] ) && is_array( $_POST['chapters'] ) && is_array( $current_import ) && check_admin_referer( 'pb-import' ) ) {
			self::doImport( $current_import );
		} elseif ( isset( $_GET['import'] ) && ! empty( $_POST['import_type'] ) && check_admin_referer( 'pb-import' ) ) {
			self::setImportOptions();
		}

		// Default, back to form
		\Pressbooks\Redirect\location( $redirect_url );
	}

	/**
	 * Do Import
	 *
	 * @param array $current_import WP option 'pressbooks_current_import'
	 */
	static protected function doImport( array $current_import ) {

		// Set post status
		$current_import['default_post_status'] = ( isset( $_POST['show_imports_in_web'] ) ) ? 'publish' : 'private'; // @codingStandardsIgnoreLine

		/**
		 * Maximum execution time, in seconds. If set to zero, no time limit
		 * Overrides PHP's max_execution_time of a Nginx->PHP-FPM->PHP configuration
		 * See also request_terminate_timeout (PHP-FPM) and fastcgi_read_timeout (Nginx)
		 *
		 * @since 5.6.0
		 *
		 * @param int $seconds
		 * @param string $some_action
		 *
		 * @return int
		 */
		@set_time_limit( apply_filters( 'pb_set_time_limit', 600, 'import' ) ); // @codingStandardsIgnoreLine

		$ok = false;
		switch ( $current_import['type_of'] ) {

			case Epub\Epub201::TYPE_OF:
				$importer = new Epub\Epub201();
				$ok = $importer->import( $current_import );
				break;

			case Wordpress\Wxr::TYPE_OF:
				$importer = new Wordpress\Wxr();
				$ok = $importer->import( $current_import );
				break;

			case Odf\Odt::TYPE_OF:
				$importer = new Odf\Odt();
				$ok = $importer->import( $current_import );
				break;

			case Ooxml\Docx::TYPE_OF:
				$importer = new Ooxml\Docx();
				$ok = $importer->import( $current_import );
				break;

			case Api\Api::TYPE_OF:
				$importer = new Api\Api();
				$ok = $importer->import( $current_import );
				break;

			case Html\Xhtml::TYPE_OF:
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
				$importers = apply_filters( 'pb_initialize_import', [] );
				if ( ! is_array( $importers ) ) {
					$importers = [ $importers ];
				}
				foreach ( $importers as $importer ) {
					if ( is_object( $importer ) ) {
						$class = get_class( $importer );
						if (
							count( $importers ) === 1 ||
							defined( "{$class}::TYPE_OF" ) && $class::TYPE_OF === $current_import['type_of']
						) {
							$ok = $importer->import( $current_import );
							break;
						}
					}
				}
		}

		if ( $ok ) {
			// Success! Redirect to organize page
			$success_url = get_admin_url( get_current_blog_id(), '/admin.php?page=pb_organize' );
			\Pressbooks\Redirect\location( $success_url );
		}
	}


	/**
	 *  Look at $_POST and $_FILES, sets 'pressbooks_current_import' option based on submission
	 *
	 * @return bool
	 */
	static protected function setImportOptions() {

		if ( ! check_admin_referer( 'pb-import' ) ) {
			return false;
		}

		$overrides = [
			'test_form' => false,
			'test_type' => false,
		];

		if ( ! function_exists( 'wp_handle_upload' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/file.php' );
		}

		// If Import Type is a URL then download and fake $_FILES on success
		//
		// This is redundant for a webbook URL (REST API) but we do it anyway because:
		//  + The select option UI expects us to fallback to the file when not a webbook in the same Submit
		//  + Sanity check verifies that we can access the website like any other
		//
		if ( getset( '_POST', 'import_type' ) === 'url' ) {
			$overrides['action'] = 'pb_handle_url_upload';
			self::createFileFromUrl();
		}
		if ( empty( $_FILES['import_file']['name'] ) ) {
			return false;
		}
		$bad_extensions = '/\.(php([0-9])?|htaccess|htpasswd|cgi|sh|pl|bat|exe|cmd|dll)$/i';
		if ( preg_match( $bad_extensions, $_FILES['import_file']['name'] ) ) {
			$_SESSION['pb_errors'][] = __( 'Sorry, this file type is not permitted for security reasons.' );
			return false;
		}

		// Handle PHP uploads in WordPress
		$upload = wp_handle_upload( $_FILES['import_file'], $overrides );
		$upload['url'] = getset( '_POST', 'import_http' );
		if ( empty( $upload['type'] ) && ! empty( $_FILES['import_file']['type'] ) ) {
			$upload['type'] = $_FILES['import_file']['type'];
		}

		if ( ! empty( $upload['error'] ) ) {
			// Error, redirect back to form
			$_SESSION['pb_notices'][] = $upload['error'];
			return false;
		}

		$ok = false;
		switch ( $_POST['type_of'] ) {

			case Wordpress\Wxr::TYPE_OF:
				$importer = new Wordpress\Wxr();
				$ok = $importer->setCurrentImportOption( $upload );
				break;

			case Epub\Epub201::TYPE_OF:
				$importer = new Epub\Epub201();
				$ok = $importer->setCurrentImportOption( $upload );
				break;

			case Odf\Odt::TYPE_OF:
				$importer = new Odf\Odt();
				$ok = $importer->setCurrentImportOption( $upload );
				break;

			case Ooxml\Docx::TYPE_OF:
				$importer = new Ooxml\Docx();
				$ok = $importer->setCurrentImportOption( $upload );
				break;

			case Api\Api::TYPE_OF:
			case Html\Xhtml::TYPE_OF:
				if ( ! empty( $upload['url'] ) && self::hasApi( $upload ) ) {
					// API
					$importer = new Api\Api();
					$ok = $importer->setCurrentImportOption( $upload );
				} else {
					// HTML
					unset( $_SESSION['pb_errors'] );
					$importer = new Html\Xhtml();
					$ok = $importer->setCurrentImportOption( $upload );
				}
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
				$importers = apply_filters( 'pb_initialize_import', [] );
				if ( ! is_array( $importers ) ) {
					$importers = [ $importers ];
				}
				foreach ( $importers as $importer ) {
					if ( is_object( $importer ) ) {
						$class = get_class( $importer );
						if (
							count( $importers ) === 1 ||
							defined( "{$class}::TYPE_OF" ) && $class::TYPE_OF === $_POST['type_of']
						) {
							$ok = $importer->setCurrentImportOption( $upload );
							break;
						}
					}
				}
		}

		if ( ! $ok ) {
			// Not ok?
			$_SESSION['pb_errors'][] = sprintf( __( 'Your file does not appear to be a valid %s.', 'pressbooks' ), strtoupper( $_POST['type_of'] ) );
			unlink( $upload['file'] );
			return false;
		}

		return true;
	}

	/**
	 * @param array $upload Passed by reference because we want to change the URL
	 *
	 * @return bool
	 */
	static protected function hasApi( &$upload ) {
		$cloner = new Cloner( $upload['url'] );
		$is_compatible = $cloner->isCompatible( $upload['url'] );
		if ( $is_compatible ) {
			$upload['url'] = $cloner->getSourceBookUrl();
			return true;
		}
		return false;
	}

	/**
	 * Tries to download URL in $_POST['import_http'], impersonates $_FILES on success
	 * Note: Faking the $_FILES array will cause PHP's is_uploaded_file() to fail
	 *
	 * @return bool
	 */
	static protected function createFileFromUrl() {

		if ( ! check_admin_referer( 'pb-import' ) ) {
			return false;
		}

		// check if it's a valid url
		$url = trim( getset( '_POST', 'import_http', '' ) );
		if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$_SESSION['pb_errors'][] = __( 'Your URL does not appear to be valid', 'pressbooks' );
			return false;
		}

		// Check that it's small enough
		$max_file_size = \Pressbooks\Utility\parse_size( \Pressbooks\Utility\file_upload_max_size() );
		if ( ! self::isUrlSmallerThanUploadMaxSize( $url, $max_file_size ) ) {
			$_SESSION['pb_errors'][] = __( 'The URL you are trying to import is bigger than the maximum file size.', 'pressbooks' );
			return false;
		}

		$tmp_file = \Pressbooks\Utility\create_tmp_file();
		$args = [
			'stream'   => true,
			'filename' => $tmp_file,
		];

		$response = wp_remote_get( $url, $args );

		// Something failed
		if ( is_wp_error( $response ) ) {
			debug_error_log( '\Pressbooks\Modules\Import::formSubmit html import error, wp_remote_head()' . $response->get_error_message() );
			$_SESSION['pb_errors'][] = $response->get_error_message();
			unlink( $tmp_file );
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		if ( $code >= 400 ) {
			$_SESSION['pb_errors'][] = __( 'The website you are attempting to reach is not returning a successful response code: ', 'pressbooks' ) . $code;
			unlink( $tmp_file );
			return false;
		}

		// Double check file size
		if ( filesize( $tmp_file ) > $max_file_size ) {
			$_SESSION['pb_errors'][] = __( 'The URL you are trying to import is bigger than the maximum file size.', 'pressbooks' );
			unlink( $tmp_file );
			return false;
		}

		// Basename
		$parsed_url = wp_parse_url( $url );
		if ( isset( $parsed_url['path'] ) ) {
			$basename = basename( $parsed_url['path'] );
		}
		if ( empty( $basename ) ) {
			$basename = uniqid( 'import-' );
		}

		// Mime type
		$mime = \Pressbooks\Media\mime_type( $tmp_file );
		if ( empty( $mime ) ) {
			$mime = wp_remote_retrieve_header( $response, 'content-type' );
		}

		$_FILES['import_file'] = [
			'name' => $basename,
			'type' => $mime,
			'tmp_name' => $tmp_file,
			'error' => 0,
			'size' => filesize( $tmp_file ),
		];

		return true;
	}


	/**
	 * Check that a URL is smaller than MAX UPLOAD without downloading the file
	 *
	 * @param string $url
	 * @param int $max
	 *
	 * @return bool
	 */
	static protected function isUrlSmallerThanUploadMaxSize( $url, $max ) {
		$response = wp_safe_remote_head(
			$url, [
				'redirection' => 2,
			]
		);
		$size = (int) wp_remote_retrieve_header( $response, 'Content-Length' );
		if ( empty( $size ) ) {
			return true; // Unable to verify, return true and hope for the best...
		}
		return ( $max >= $size );
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
