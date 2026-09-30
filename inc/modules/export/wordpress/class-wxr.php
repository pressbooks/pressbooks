<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged

namespace Pressbooks\Modules\Export\WordPress;

use Generator;
use Pressbooks\Modules\Export\Export;
use function Pressbooks\Utility\put_contents;

class Wxr extends Export {

	/**
	 * @param array $args
	 */
	public function __construct( array $args ) {
	}

	/**
	 * Procedure for "format/wxr" rewrite rule.
	 * Optimized for background processing.
	 *
	 * @param bool $return If true, returns output instead of echoing it.
	 * @return mixed
	 */
	public function transform( $return = false ) {
		// Set unlimited execution time and increase memory for background processing
		@set_time_limit( 0 );
		@ini_set( 'memory_limit', '512M' );

		static $buffer_cache;

		if ( ! function_exists( 'wxr_cdata' ) ) {

			$current_error_reporting = error_reporting( 0 );
			$current_display_errors = ini_set( 'display_errors', '0' );

			ob_start();

			if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
				define( 'WP_LOAD_IMPORTERS', true );
			}
			require_once ABSPATH . 'wp-admin/includes/export.php';

			try {
				export_wp( [ 'content' => 'all' ] );
				$wxr_content = ob_get_contents();
			} catch ( \Exception $e ) {
				$wxr_content = ob_get_contents();
				ob_end_clean();

				// Restore error reporting
				error_reporting( $current_error_reporting );
				ini_set( 'display_errors', $current_display_errors );

				return false;
			} finally {
				if ( ob_get_level() > 0 ) {
					ob_end_clean();
				}
				// Restore error reporting
				error_reporting( $current_error_reporting );
				ini_set( 'display_errors', $current_display_errors );
			}

			$buffer_cache = $wxr_content;
		}

		if ( $return ) {
			return $buffer_cache;
		} else {
			echo $buffer_cache;
			return null;
		}
	}

	public function convert(): Generator {
		// Get WXR
		yield 30 => __( 'Transforming WXR.', 'pressbooks' );
		$output = $this->transform( true );

		if ( ! $output ) {
			yield 'error' => __( 'Failed to transform WXR.', 'pressbooks' );
			return;
		}

		// Save WXR as file in exports folder
		yield 70 => __( 'Creating file.', 'pressbooks' );
		$filename = $this->timestampedFileName( '.xml' );

		put_contents( $filename, $output );
		$this->outputPath = $filename;

		yield 80 => __( 'Saved WXR file.', 'pressbooks' );

		return $this->outputPath;
	}
	public function validate(): Generator {
		yield 90 => __( 'Validating WXR.', 'pressbooks' );
		if ( ! simplexml_load_file( $this->outputPath ) ) {
			$this->logError( 'WXR document is not well formed XML.' );
			yield 'error' => __( 'WXR document is not well formed XML.', 'pressbooks' );
			return false;
		}
		yield 100 => __( 'WXR is valid.', 'pressbooks' );
		return true;
	}
}
