<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\Mobi;

use Pressbooks\Modules\Export\Export;

class Kindlegen extends Export {

	/**
	 * @var bool
	 */
	public $hasWarnings = false;


	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults

		if ( ! defined( 'PB_KINDLEGEN_COMMAND' ) ) {
			define( 'PB_KINDLEGEN_COMMAND', '/opt/kindlegen/kindlegen' );
		}

		if ( ! defined( 'PB_KINDLEGEN_SUPPRESS_MOBI8' ) ) {
			define( 'PB_KINDLEGEN_SUPRRESS_MOBI8', false );
		}

	}


	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {

		// Get most recent Epub file

		$input_folder = static::getExportFolder();
		$input_path = false;
		$files = \Pressbooks\Utility\scandir_by_date( $input_folder );
		foreach ( $files as $file ) {
			if ( preg_match( '/\.epub/i', $file ) ) {
				$input_path = $input_folder . $file;
				break;
			}
		}

		if ( ! $input_path ) {
			$this->logError( "Could not convert to MOBI because no EPUB file was found in $input_folder" );

			return false;
		}

		// Convert

		$filename = $this->timestampedFileName( '.mobi' );
		$this->outputPath = $filename;

		$command = PB_KINDLEGEN_COMMAND . ' ' . escapeshellcmd( $input_path ) . ' -locale en -o ' . escapeshellcmd( basename( $this->outputPath ) ) . ' 2>&1';

		$output = [];
		$return_var = 0;
		exec( $command, $output, $return_var );

		// Check build results

		$last_line = array_filter( $output );
		$last_line = strtolower( end( $last_line ) );
		if ( false !== strpos( $last_line, 'mobi file built successfully' ) ) {
			if ( PB_KINDLEGEN_SUPPRESS_MOBI8 ) {
				$mobi8_filename = $input_folder . sanitize_file_name( basename( $this->outputPath . '8' ) );
				if ( file_exists( $mobi8_filename ) ) {
					unlink( $mobi8_filename );
				}
			}

			// Ok!
			return true;

		} elseif ( false !== strpos( $last_line, 'mobi file built with warnings' ) ) {

			// Built, but has warnings
			$this->hasWarnings = true;
			$this->logError( implode( "\n", $output ) );

			return true;

		} else {

			// Error
			$this->logError( implode( "\n", $output ) );

			return false;
		}

	}


	/**
	 * Check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	function validate() {

		// Validation errors were set in $this->convert() method
		return ( ! $this->hasWarnings );
	}

	/**
	 * Dependency check.
	 *
	 * @return bool
	 */
	static function hasDependencies() {
		if ( false !== \Pressbooks\Utility\check_epubcheck_install() && false !== \Pressbooks\Utility\check_kindlegen_install() ) {
			return true;
		}

		return false;
	}

}
