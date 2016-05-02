<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
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

		if ( ! defined( 'PB_KINDLEGEN_COMMAND' ) )
			define( 'PB_KINDLEGEN_COMMAND', '/opt/kindlegen/kindlegen' );

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

		$output = array();
		$return_var = 0;
		exec( $command, $output, $return_var );


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


}
