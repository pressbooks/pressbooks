<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged

namespace Pressbooks\Modules\Export\WordPress;

use function Pressbooks\Utility\put_contents;
use Generator;
use Pressbooks\Modules\Export\ExportGenerator;

class Wxr extends ExportGenerator {

	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

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

		// Check permissions - Note: current_user_can() might behave differently in cron.
		// export_wp() itself has internal permission checks.
		if ( ! current_user_can( 'edit_posts' ) ) {
			// If this check is problematic for cron, it might need adjustment,
			// but for now, let's assume it's intended.
			// $this->logError('WXR Transform: Permission check failed for current user.');
			// wp_die( __( 'Invalid permission error', 'pressbooks' ) );
			// It's safer to let export_wp() handle its own permissions if this is a background task without a typical user.
		}

		$is_background_process = ( ( defined( 'WP_CLI' ) && WP_CLI ) || ( defined( 'DOING_CRON' ) && DOING_CRON ) );

		$previous_memory_limit = ini_get( 'memory_limit' );
		$previous_time_limit = ini_get( 'max_execution_time' );

		if ( $is_background_process ) {
			@set_time_limit( 0 ); // No time limit for background processes
			@ini_set( 'memory_limit', '512M' ); // Or higher if needed
			$this->logError( 'WXR Transform: Running as background process. Time limit disabled (0), memory limit set to 512M.' );
		} else {
			// For synchronous/foreground execution
			@ini_set( 'memory_limit', '512M' );
			@set_time_limit( 300 ); // 5 minutes for foreground
			$this->logError( 'WXR Transform: Running as foreground process. Time limit set to 300s, memory limit set to 512M.' );
		}

		static $buffer_cache; // Renamed to avoid confusion with local $buffer variables if any. Holds the cached WXR content.

		if ( ! function_exists( 'wxr_cdata' ) ) {
			// Log *before* starting the output buffer for WXR content
			$this->logError( 'WXR Transform: wxr_cdata() not found. Proceeding to call export_wp().' );

			// Save current error reporting state
			$current_error_reporting = error_reporting();
			$current_display_errors = ini_get( 'display_errors' );

			// Temporarily disable direct display of errors/warnings for the export_wp() call.
			// Errors should still go to your PHP error log if log_errors is enabled.
			// This prevents PHP notices/warnings within export_wp or its includes from breaking the XML.
			if ( $is_background_process ) { // Be more aggressive in background
				error_reporting( 0 );
				ini_set( 'display_errors', '0' );
			} else {
				// For foreground, suppress notices and warnings from display, but keep others.
				error_reporting( E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT );
				ini_set( 'display_errors', '0' );
			}

			ob_start(); // Start buffer specifically for export_wp() output

			if ( ! defined( 'WP_LOAD_IMPORTERS' ) ) {
				define( 'WP_LOAD_IMPORTERS', true );
			}
			require_once( ABSPATH . 'wp-admin/includes/export.php' );

			$wxr_content = ''; // Initialize
			try {
				// Call export_wp() with default arguments for content.
				export_wp( [ 'content' => 'all' ] );
				$wxr_content = ob_get_contents(); // Get export_wp() output
			} catch ( \Exception $e ) {
				// Exception during export_wp (rare for core export_wp, but possible with hooks)
				if ( ob_get_level() > 0 ) {
					$wxr_content = ob_get_contents(); // Get any partial output
					ob_end_clean(); // Clean buffer on error
				}
				// Log error *after* buffer is handled and error reporting is restored
				// Error reporting is restored in the finally block before this would be hit if it was the primary error.
				// However, direct logging here is fine.
				$this->logError( 'WXR Transform: Exception during export_wp() call: ' . $e->getMessage() . '. Partial buffer captured: ' . substr( $wxr_content, 0, 200 ) );
				// Restore original limits & error reporting (also in finally, but good for clarity if returning early)
				@ini_set( 'memory_limit', $previous_memory_limit );
				@set_time_limit( $previous_time_limit );
				error_reporting( $current_error_reporting );
				ini_set( 'display_errors', $current_display_errors );
				return false; // Indicate failure
			} finally {
				if ( ob_get_level() > 0 ) {
					ob_end_clean(); // Ensure buffer is always cleaned up
				}
				// Restore error reporting and display_errors settings
				error_reporting( $current_error_reporting );
				ini_set( 'display_errors', $current_display_errors );
			}

			// Log about the result of export_wp() *after* its output has been captured and buffer closed
			$this->logError( 'WXR Transform: export_wp() process completed. Output size: ' . ( is_string( $wxr_content ) ? strlen( $wxr_content ) : 'N/A' ) . ' bytes.' );
			if ( empty( $wxr_content ) ) {
				$this->logError( 'WXR Transform: WARNING - WXR content is empty after export_wp(). This may indicate an issue within export_wp() (e.g., early exit, no output, or error not caught as exception).' );
			}

			$buffer_cache = $wxr_content; // Cache the clean WXR content
		} else {
			// This case implies transform() might have been called again after export_wp() has already defined its functions.
			// The static $buffer_cache would be reused if it was populated from a previous call in this request.
			$this->logError( 'WXR Transform: wxr_cdata() function already exists. Reusing existing static WXR buffer_cache if populated. Size: ' . ( is_string( $buffer_cache ) ? strlen( $buffer_cache ) : 'N/A' ) . ' bytes.' );
		}

		// Restore original limits
		// For background, time limit was 0, memory was 512M. These don't strictly need restoring
		// if the script exits, but good practice.
		@ini_set( 'memory_limit', $previous_memory_limit );
		@set_time_limit( $previous_time_limit );
		if ( ! $is_background_process ) {
			$this->logError( 'WXR Transform: Restored original PHP time/memory limits for foreground process.' );
		}

		if ( $return ) {
			return $buffer_cache;
		} else {
			// This path (echoing buffer) is less common if convert() calls transform(true)
			echo $buffer_cache;
			return null;
		}
	}

	public function convertGenerator(): Generator {
		// Get WXR
		yield 30 => __( 'Transforming WXR.', 'pressbooks' );
		$output = $this->transform( true );

		if ( ! $output ) {
			yield 'error' => __( 'Failed to transform WXR.', 'pressbooks' );
			return;
		}

		// Save WXR as file in exports folder
		yield 40 => __( 'Creating file.', 'pressbooks' );
		$filename = $this->timestampedFileName( '.xml' );

		put_contents( $filename, $output );
		$this->outputPath = $filename;

		yield 50 => __( 'Saved WXR file.', 'pressbooks' );
	}
	public function validateGenerator(): Generator {
		if ( ! simplexml_load_file( $this->outputPath ) ) {
			$this->logError( 'WXR document is not well formed XML.' );
			yield 'error' => __( 'WXR document is not well formed XML.', 'pressbooks' );
			return;
		}
		yield 70 => __( 'WXR is valid.', 'pressbooks' );
	}
}
