<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\WordPress;

use Pressbooks\Modules\Export\Export;

class Wxr extends Export {

	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

	}

	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {

		// Get WXR

		$output = $this->transform( true );

		if ( ! $output ) {
			return false;
		}

		// Save WXR as file in exports folder

		$filename = $this->timestampedFileName( '.xml' );
		\Pressbooks\Utility\put_contents( $filename, $output );
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
	 *
	 * @see \Pressbooks\Redirect\do_format
	 *
	 * @param bool $return (optional)
	 * If you would like to capture the output of transform,
	 * use the return parameter. If this parameter is set
	 * to true, transform will return its output, instead of
	 * printing it.
	 *
	 * @return mixed
	 */
	function transform( $return = false ) {

		// Check permissions
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( __( 'Invalid permission error', 'pressbooks' ) );
		}

		// Ahoy! Gross code ahead.
		// Cannot redeclare a function inside of a function, execute export_wp() only once
		static $buffer;
		if ( ! function_exists( 'wxr_cdata' ) ) {
			ob_start();
			require_once( ABSPATH . 'wp-admin/includes/export.php' );
			@export_wp(); // @codingStandardsIgnoreLine
			$buffer = ob_get_clean();
		}

		if ( $return ) {
			return $buffer;
		} else {
			echo $buffer;
			return null;
		}
	}

}
