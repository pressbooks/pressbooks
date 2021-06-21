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
	 * Temporary directory used to build Common Cartridge, no trailing slash!
	 *
	 * @var string
	 */
	protected $tmpDir;

	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults

		if ( ! defined( 'PB_KINDLEGEN_COMMAND' ) ) {
			define( 'PB_KINDLEGEN_COMMAND', '/opt/kindlegen/kindlegen' );
		}

		$this->tmpDir = $this->createTmpDir();

	}

	/**
	 * Delete temporary directory when done.
	 */
	function __destruct() {
		$this->deleteTmpDir();
	}

	/**
	 * @return string
	 */
	public function getTmpDir() {
		return $this->tmpDir;
	}


	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {
		if ( empty( $this->tmpDir ) || ! is_dir( $this->tmpDir ) ) {
			$this->logError( '$this->tmpDir must be set before calling convert().' );
			return false;
		}

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

		// Move epub to tmp folder, convert and move to export folder
		$tmp_input_path = $this->tmpDir . DIRECTORY_SEPARATOR . escapeshellcmd( basename( $input_path) );

		copy( escapeshellcmd( $input_path ), $tmp_input_path );

		$command = PB_KINDLEGEN_COMMAND . ' ' . escapeshellcmd( $tmp_input_path ) . ' -locale en -o ' . escapeshellcmd( basename( $this->outputPath ) ) . ' 2>&1';

		$output = [];
		$return_var = 0;
		exec( $command, $output, $return_var );
		copy( $this->tmpDir . DIRECTORY_SEPARATOR . basename( $this->outputPath ), dirname( $this->outputPath ) . DIRECTORY_SEPARATOR . basename( $this->outputPath ) );

		// Check build results

		$last_line = array_filter( $output );
		$last_line = strtolower( end( $last_line ) );
		if ( false !== strpos( $last_line, 'mobi file built successfully' ) ) {

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

	/**
	 * Delete temporary directory
	 */
	protected function deleteTmpDir() {

		// Cleanup temporary directory, if any
		if ( ! empty( $this->tmpDir ) ) {
			\Pressbooks\Utility\rmrdir( $this->tmpDir );
		}
	}

}
