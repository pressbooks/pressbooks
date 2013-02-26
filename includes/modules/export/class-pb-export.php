<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Export;


// IMPORTANT! if this isn't set correctly before include, with a trailing slash, PclZip will fail.
if ( ! defined( 'PCLZIP_TEMPORARY_DIR' ) ) {
	if ( ! empty( $_ENV['TMP'] ) ) {
		define( 'PCLZIP_TEMPORARY_DIR', trailingslashit( realpath( $_ENV['TMP'] ) ) );
	} else if ( ! empty( $_ENV['TMPDIR'] ) ) {
		define( 'PCLZIP_TEMPORARY_DIR', trailingslashit( realpath( $_ENV['TMPDIR'] ) ) );
	} else if ( ! empty( $_ENV['TEMP'] ) ) {
		define( 'PCLZIP_TEMPORARY_DIR', trailingslashit( realpath( $_ENV['TEMP'] ) ) );
	} else {
		define( 'PCLZIP_TEMPORARY_DIR', '/tmp/' );
	}
}

abstract class Export {

	/**
	 * Email addresses to send log errors.
	 *
	 * @var array
	 */
	public $errorsEmail = array(
		'errors@pressbooks.com',
	);


	/**
	 * Reserved html IDs.
	 *
	 * @var array
	 */
	protected $reservedIds = array(
		'cover-image',
		'title-page',
		'copyright-page',
		'toc',
	);


	/**
	 * Location where data is held until ready to be displayed.
	 *
	 * @var string fullpath
	 */
	protected $outputPath;


	/**
	 * Mandatory convert method, create $this->outputPath
	 *
	 * @return bool
	 */
	abstract function convert();


	/**
	 * Mandatory validate method, check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	abstract function validate();


	/**
	 * Return $this->outputPath
	 *
	 * @return string
	 */
	function getOutputPath() {

		return $this->outputPath;
	}


	/**
	 * Return the fullpath to an export module's style files, no trailing slash!
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	function getExportStylePath( $type ) {

		return realpath( get_stylesheet_directory() . "/export/$type" );
	}


	/**
	 * Log errors using wp_mail() and error_log(), include useful WordPress info.
	 *
	 * @param string $message
	 * @param array  $more_info
	 */
	function logError( $message, array $more_info = array() ) {

		/** $var \WP_User $current_user */
		global $current_user;

		$subject = get_class( $this );

		$info = array(
			'time' => strftime( '%c' ),
			'user' => ( isset( $current_user ) ? $current_user->user_login : '__UNKNOWN__' ),
			'site_url' => site_url(),
			'blog_id' => get_current_blog_id(),
			'theme' => '' . wp_get_theme(), // Stringify by appending to empty string
		);

		$message = print_r( array_merge( $info, $more_info ), true ) . $message;

		// ------------------------------------------------------------------------------------------------------------
		// Write to error log

		error_log( $subject . "\n" . $message );

		// ------------------------------------------------------------------------------------------------------------
		// Email logs

		if ( @$current_user->user_email && get_option( 'pressbooks_email_validation_logs' ) ) {
			$this->errorsEmail[] = $current_user->user_email;
		}

		add_filter( 'wp_mail_from', function ( $from_email ) {
			return str_replace( 'wordpress@', 'pressbooks@', $from_email );
		} );
		add_filter( 'wp_mail_from_name', function ( $from_name ) {
			return 'PressBooks';
		} );

		foreach ( $this->errorsEmail as $email ) {
			wp_mail( $email, $subject, $message );
		}
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
	 * Create a timestamped filename.
	 *
	 * @param      $extension
	 * @param bool $fullpath
	 *
	 * @return string
	 */
	function timestampedFileName( $extension, $fullpath = true ) {

		$book_title_slug = sanitize_file_name( get_bloginfo( 'name' ) );
		$book_title_slug = str_replace( array( '+' ), '', $book_title_slug );

		if ( $fullpath ) {
			$path = static::getExportFolder();
		} else {
			$path = '';
		}

		// IMPORTANT: if you change the dash + time() convention then you need to also change
		// pressbooks/admin/templates/export.php, which uses that convention to split and sort files.
		// Maybe a few other places. :(

		$filename = $path . $book_title_slug . '-' . time() . '.' . ltrim( $extension, '.' );

		return $filename;
	}


	/**
	 * Detect MIME Content-type for a file.
	 *
	 * @param string $file fullpath
	 *
	 * @return string
	 */
	function mimeType( $file ) {

		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME );
			$mime = finfo_file( $finfo, $file );
			finfo_close( $finfo );
		} elseif ( function_exists( 'mime_content_type' ) ) {
			$mime = @mime_content_type( $file ); // Suppress deprecated message
		} else {
			$mime = system( "file -i -b " . escapeshellarg( $file ) );
		}

		return $mime;
	}


	/**
	 * Create a NONCE using WordPress' NONCE_KEY and a Unix timestamp.
	 *
	 * @see verifyNonce
	 *
	 * @param string $timestamp unix timestamp
	 *
	 * @return string
	 */
	function nonce( $timestamp ) {

		return md5( NONCE_KEY . $timestamp );
	}


	/**
	 * Verify that a NONCE was created within a range of 5 minutes and is valid.
	 *
	 * @see nonce
	 *
	 * @param string $timestamp unix timestamp
	 * @param string $md5
	 *
	 * @return bool
	 */
	function verifyNonce( $timestamp, $md5 ) {

		// Within range of 5 minutes?
		$within_range = time() - $timestamp;
		if ( $within_range > ( 60 * 5 ) ) {
			return false;
		}

		// Correct md5?
		if ( md5( NONCE_KEY . $timestamp ) != $md5 ) {
			return false;
		}

		return true;
	}


	/**
	 * Check a post_name against a list of reserved IDs, sanitize for use as an XML ID.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function preProcessPostName( $id ) {

		if ( in_array( $id, $this->reservedIds ) ) {
			$id = uniqid( "$id-" );
		}

		return \PressBooks\Sanitize\sanitize_xml_id( $id );
	}


	/**
	 * Create a temporary directory, no trailing slash!
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function createTmpDir() {

		$temp_file = tempnam( sys_get_temp_dir(), '' );
		if ( file_exists( $temp_file ) ) {
			unlink( $temp_file );
		}
		mkdir( $temp_file );
		if ( ! is_dir( $temp_file ) ) {
			throw new \Exception( 'Could not create temporary directory.' );

		}

		return untrailingslashit( $temp_file );
	}


	/**
	 * Recursively delete all contents of a directory.
	 *
	 * @param string $dirname
	 * @param bool   $only_empty
	 *
	 * @return bool
	 */
	protected function obliterateDir( $dirname, $only_empty = false ) {

		if ( ! is_dir( $dirname ) )
			return false;

		$dscan = array( realpath( $dirname ) );
		$darr = array();
		while ( ! empty( $dscan ) ) {
			$dcur = array_pop( $dscan );
			$darr[] = $dcur;
			if ( $d = opendir( $dcur ) ) {
				while ( $f = readdir( $d ) ) {
					if ( $f == '.' || $f == '..' ) continue;
					$f = $dcur . '/' . $f;
					if ( is_dir( $f ) ) $dscan[] = $f;
					else unlink( $f );
				}
				closedir( $d );
			}
		}
		$i_until = ( $only_empty ) ? 1 : 0;
		for ( $i = count( $darr ) - 1; $i >= $i_until; $i -- ) {
			if ( ! rmdir( $darr[$i] ) ) trigger_error( "Warning: There was a problem deleting a temporary file in $dirname", E_USER_WARNING );
		}

		return ( ( $only_empty ) ? ( count( scandir( $dirname ) ) <= 2 ) : ( ! is_dir( $dirname ) ) );
	}


	/**
	 * Convert an XML string via XSLT file.
	 *
	 * @param string     $content
	 * @param string     $path_to_xsl
	 *
	 * @return string
	 */
	protected function transformXML( $content, $path_to_xsl ) {

		$xsl = new \DOMDocument();
		$xsl->load( $path_to_xsl );

		$proc = new \XSLTProcessor();
		$proc->importStyleSheet( $xsl );

		$xml = new \DOMDocument();
		@$xml->loadXML( $content );
		$content = @$proc->transformToXML( $xml );

		return $content;
	}


	/**
	 * Simple template system.
	 *
	 * @param       $path
	 * @param array $vars (optional)
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function loadTemplate( $path, array $vars = array() ) {

		if ( ! file_exists( $path ) ) {
			throw new \Exception( "File not found: $path" );
		}

		ob_start();
		extract( $vars );
		include( $path );
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}


	/**
	 * Get the fullpath to the Exports folder.
	 * Create if not there. Create .htaccess protection if missing.
	 *
	 * @return string fullpath
	 */
	static function getExportFolder() {

		$path = \PressBooks\Utility\get_media_prefix() . 'exports/';
		if ( ! file_exists( $path ) ) {
			mkdir( $path, 0775, true );
		}

		$path_to_htaccess = $path . '.htaccess';
		if ( ! file_exists( $path_to_htaccess ) ) {
			// Restrict access
			file_put_contents( $path_to_htaccess, "deny from all\n" );
		}

		return $path;
	}


	// ----------------------------------------------------------------------------------------------------------------
	// Catch form submissions
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 * Overrides default WP template loading to do some funky stuff
	 * On export page, chooses correct export module for epub, pdf, indesign, etc/
	 * Handles deleting a saved export file
	 *
	 * @see pressbooks/admin/templates/export.php
	 */
	static function formSubmit() {

		if ( ! current_user_can( 'edit_posts' ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		// Set locale to UTF8 so escapeshellcmd() doesn't strip valid characters.
		setlocale( LC_CTYPE, 'UTF8', 'en_US.UTF-8' );

		// Download
		if ( ! empty( $_GET['download_export_file'] ) ) {
			$filename = sanitize_file_name( $_GET['download_export_file'] );
			static::downloadExportFile( $filename );
		}

		// Delete
		if ( isset( $_POST['delete_export_file'] ) && isset( $_POST['filename'] ) && check_admin_referer( 'pb-delete-export' ) ) {
			$filename = sanitize_file_name( $_POST['filename'] );
			$path = static::getExportFolder();
			unlink( $path . $filename );
			\PressBooks\Redirect\location( get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=pb_export' );
		}

		// Export
		if ( 'yes' == @$_GET['export'] && is_array( @$_POST['export_formats'] ) && check_admin_referer( 'pb-export' ) ) {

			// --------------------------------------------------------------------------------------------------------
			// Define modules

			$x = $_POST['export_formats'];
			$modules = array();

			if ( isset( $x['pdf'] ) ) {
				$modules[] = '\PressBooks\Export\Prince\Pdf';
			}
			if ( isset( $x['epub'] ) ) {
				$modules[] = '\PressBooks\Export\Epub\Epub201'; // Must be set before MOBI
			}
			if ( isset( $x['mobi'] ) ) {
				$modules[] = '\PressBooks\Export\Mobi\Kindlegen'; // Must be set after EPUB
			}
			if ( isset( $x['hpub'] ) ) {
				$modules[] = '\PressBooks\Export\Hpub\Hpub';
			}
			if ( isset( $x['icml'] ) ) {
				$modules[] = '\PressBooks\Export\InDesign\Icml';
			}
			if ( isset( $x['xhtml'] ) ) {
				$modules[] = '\PressBooks\Export\Xhtml\Xhtml11';
			}
			if ( isset( $x['wxr'] ) ) {
				$modules[] = '\PressBooks\Export\WordPress\Wxr';
			}

			// --------------------------------------------------------------------------------------------------------
			// Clear cache? Range is 1 hour.

			$last_export = get_option( 'pressbooks_last_export' );
			$within_range = time() - $last_export;
			if ( $within_range > ( 60 * 60 ) ) {
				\PressBooks\Book::deleteBookObjectCache();
				update_option( 'pressbooks_last_export', time() );
			}

			// --------------------------------------------------------------------------------------------------------
			// Do Export

			@set_time_limit( 300 );

			$redirect_url = get_bloginfo( 'url' ) . '/wp-admin/admin.php?page=pb_export';
			$conversion_error = array();
			$validation_warning = array();

			foreach ( $modules as $module ) {

				/** @var \PressBooks\Export\Export $exporter */
				$exporter = new $module( array() );

				if ( ! $exporter->convert() ) {
					$conversion_error[$module] = $exporter->getOutputPath();
				} else {
					if ( ! $exporter->validate() ) {
						$validation_warning[$module] = $exporter->getOutputPath();
					}
				}
				// Stats hook
				do_action( 'pb_track_export', substr( strrchr( $module, '\\' ), 1 ) );
			}

			// --------------------------------------------------------------------------------------------------------
			// No errors?

			if ( empty( $conversion_error ) && empty( $validation_warning ) ) {
				// Ok!
				\PressBooks\Redirect\location( $redirect_url );
			}

			// --------------------------------------------------------------------------------------------------------
			// Error exceptions

			if ( isset( $validation_warning['\PressBooks\Export\Prince\Pdf'] ) ) {

				// The PDF is garbage and we don't want the user to have it.
				// Delete file. Report error instead of warning.
				unlink( $validation_warning['\PressBooks\Export\Prince\Pdf'] );
				$conversion_error['\PressBooks\Export\Prince\Pdf'] = $validation_warning['\PressBooks\Export\Prince\Pdf'];
				unset ( $validation_warning['\PressBooks\Export\Prince\Pdf'] );
			}

			// --------------------------------------------------------------------------------------------------------
			// Errors :(

			if ( count( $conversion_error ) ) {
				// Conversion error
				\PressBooks\Redirect\location( $redirect_url . '&export_error=true' );
			}

			if ( count( $validation_warning ) ) {
				// Validation warning
				\PressBooks\Redirect\location( $redirect_url . '&export_warning=true' );
			}
		}

	}


	/**
	 * Download an .htaccess protected file from the exports directory.
	 *
	 * @param string $filename sanitized $_GET['download_export_file']
	 */
	protected static function downloadExportFile( $filename ) {

		$filepath = static::getExportFolder() . $filename;
		if ( ! is_readable( $filepath ) ) {
			// Cannot read file
			wp_die( __( 'File not found', 'pressbooks' ) . ": $filename", '', array( 'response' => 404 ) );
		}

		// Force download
		header( 'Content-Description: File Transfer' );
		header( 'Content-Type: ' . static::mimeType( $filepath ) );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Content-Transfer-Encoding: binary' );
		header( 'Expires: 0' );
		header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
		header( 'Pragma: public' );
		header( 'Content-Length: ' . filesize( $filepath ) );
		@ob_clean();
		flush();
		readfile( $filepath );

		exit;
	}


}