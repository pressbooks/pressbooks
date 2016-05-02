<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Modules\Export\WordPress;


use Pressbooks\Modules\Export\Export;

class Wxr extends Export {


	/**
	 * Timeout in seconds.
	 * Used with wp_remote_get()
	 *
	 * @var int
	 */
	public $timeout = 90;


	/**
	 * Service URL
	 *
	 * @var string
	 */
	public $url;


	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults


		// Set the access protected "format/wxr" URL with a valid timestamp and NONCE
		$timestamp = time();
		$md5 = $this->nonce( $timestamp );
		$this->url = home_url() . "/format/wxr?timestamp={$timestamp}&hashkey={$md5}";

	}


	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {

		// Get WXR

		$output = $this->queryWxr();

		if ( ! $output ) {
			return false;
		}

		// Save WXR as file in exports folder

		$filename = $this->timestampedFileName( '.xml' );
		file_put_contents( $filename, $output );
		$this->outputPath = $filename;

		return true;
	}


	/**
	 * Check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	function validate() {

		if ( ! simplexml_load_file( $this->outputPath ) ) {

			$this->logError( 'WXR document is not well formed XML.' );

			return false;
		}

		return true;
	}


	/**
	 * Procedure for "format/wxr" rewrite rule.
	 */
	function transform() {

		// Check permissions

		if ( ! current_user_can( 'manage_options' ) ) {
			$timestamp = absint( @$_REQUEST['timestamp'] );
			$hashkey = @$_REQUEST['hashkey'];
			if ( ! $this->verifyNonce( $timestamp, $hashkey ) ) {
				wp_die( __( 'Invalid permission error', 'pressbooks' ) );
			}
		}

		// ------------------------------------------------------------------------------------------------------------
		// WXR, Start!

		require_once( ABSPATH . 'wp-admin/includes/export.php' );
		export_wp();
	}


	/**
	 * Add $this->url as additional log info, fallback to parent.
	 *
	 * @param $message
	 * @param array $more_info (unused, overridden)
	 */
	function logError( $message, array $more_info = array() ) {

		$more_info = array(
			'url' => $this->url,
		);

		parent::logError( $message, $more_info );
	}


	/**
	 * Query the access protected "format/wxr" URL, return the results.
	 *
	 * @return bool|string
	 */
	protected function queryWxr() {

		$args = array( 'timeout' => $this->timeout );
		$response = wp_remote_get( $this->url, $args );

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			$this->logError( $response->get_error_message() );

			return false;
		}

		// Server error?
		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
			$this->logError( wp_remote_retrieve_response_message( $response ) );

			return false;
		}

		return wp_remote_retrieve_body( $response );
	}


}
