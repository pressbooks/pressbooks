<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Export;


use PressBooks\Book;
use PressBooks\CustomCss;


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
		'half-title-page',
		'title-page',
		'copyright-page',
		'toc',
		'pressbooks-promo',
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
	 * Return the fullpath to an export module's style file.
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	function getExportStylePath( $type ) {

		$fullpath = false;

		if ( CustomCss::isCustomCss() ) {
			$fullpath = CustomCss::getCustomCssFolder() . "/$type.css";
			if ( ! is_file( $fullpath ) ) $fullpath = false;
		}

		if ( ! $fullpath ) {
			$fullpath = realpath( get_stylesheet_directory() . "/export/$type/style.css" );
		}

		return $fullpath;
	}


	/**
	 * Return the fullpath to an export module's Javascript file.
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	function getExportScriptPath( $type ) {

		$fullpath = false;

		if ( CustomCss::isCustomCss() ) {
			$fullpath = CustomCss::getCustomCssFolder() . "/$type.js";
			if ( ! is_file( $fullpath ) ) $fullpath = false;
		}

		if ( ! $fullpath ) {
			$fullpath = realpath( get_stylesheet_directory() . "/export/$type/script.js" );
			if ( CustomCss::isCustomCss() && CustomCss::isRomanized() && $type == 'prince' ) {
				$fullpath = realpath( get_stylesheet_directory() . "/export/$type/script-romanize.js" );
			}
		}

		return $fullpath;
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

		return \PressBooks\Utility\create_tmp_file();
	}


	/**
	 * Create a timestamped filename.
	 *
	 * @param string $extension
	 * @param bool $fullpath
	 *
	 * @return string
	 */
	function timestampedFileName( $extension, $fullpath = true ) {

		$book_title_slug = sanitize_file_name( get_bloginfo( 'name' ) );
		$book_title_slug = str_replace( array( '+' ), '', $book_title_slug ); // Remove symbols which confuse Apache (Ie. form urlencoded spaces)
		$book_title_slug = sanitize_file_name( $book_title_slug ); // str_replace() may inadvertently create a new bad filename, sanitize again for good measure.

		if ( $fullpath ) {
			$path = static::getExportFolder();
		} else {
			$path = '';
		}

		$filename = $path . $book_title_slug . '-' . time() . '.' . ltrim( $extension, '.' );

		return $filename;
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
	 * Fix annoying characters that the user probably didn't do on purpose
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	function fixAnnoyingCharacters( $html ) {

		// Replace Non-breaking spaces with normal spaces
		// TODO: Some users want this, others do not want this, make up your mind...
		// $html = preg_replace( '/\xC2\xA0/', ' ', $html );

		return $html;
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

		libxml_use_internal_errors( true );
		$content = iconv( 'UTF-8', 'UTF-8//IGNORE', $content );

		$xsl = new \DOMDocument();
		$xsl->load( $path_to_xsl );

		$proc = new \XSLTProcessor();
		$proc->importStyleSheet( $xsl );

		$old_value = libxml_disable_entity_loader( true );
		$xml = new \DOMDocument();
		$xml->loadXML( $content );
		libxml_disable_entity_loader( $old_value );

		$content = $proc->transformToXML( $xml );

		$errors = libxml_get_errors(); // TODO: Handle errors gracefully
		libxml_clear_errors();

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
	 * Detect MIME Content-type for a file.
	 *
	 * @param string $file fullpath
	 *
	 * @return string
	 */
	static function mimeType( $file ) {

		if ( function_exists( 'finfo_open' ) ) {
			$finfo = finfo_open( FILEINFO_MIME );
			$mime = finfo_file( $finfo, $file );
			finfo_close( $finfo );
		} elseif ( function_exists( 'mime_content_type' ) ) {
			$mime = @mime_content_type( $file ); // Suppress deprecated message
		} else {
			exec( "file -i -b " . escapeshellarg( $file ), $output );
			$mime = $output[0];
		}

		return $mime;
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


	/**
	 * Catch form submissions
	 *
	 * @see pressbooks/admin/templates/export.php
	 */
	static function formSubmit() {

		if ( false == static::isFormSubmission() || false == current_user_can( 'edit_posts' ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		// Set locale to UTF8 so escapeshellcmd() doesn't strip valid characters.
		setlocale( LC_CTYPE, 'UTF8', 'en_US.UTF-8' );
		putenv( 'LC_CTYPE=en_US.UTF-8' );

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
			delete_transient( 'dirsize_cache' ); /** @see get_dirsize() */
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
			if ( isset( $x['epub3'] ) ) {
				$modules[] = '\PressBooks\Export\Epub3\Epub3'; // Must be set before MOBI
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
				do_action( 'pressbooks_track_export', substr( strrchr( $module, '\\' ), 1 ) );
			}

			delete_transient( 'dirsize_cache' ); /** @see get_dirsize() */

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
	 * Hook for add_filter('locale ', ...), change the book language
	 *
	 * @param string $lang
	 *
	 * @return string
	 */
	static function setLocale( $lang ) {

		// Cheap cache
		static $loc = '__UNSET__';

		if ( '__UNSET__' == $loc && function_exists( 'get_available_languages' ) ) {

			$compare_with = get_available_languages( PB_PLUGIN_DIR . '/languages/' );

			$book_lang = Book::getBookInformation();
			$book_lang = @$book_lang['pb_language'];

			foreach ( $compare_with as $compare ) {

				$compare = str_replace( 'pressbooks-', '', $compare );
				list( $check_me ) = explode( '_', $compare );

				// We only care about the first two letters
				if ( strpos( $book_lang, $check_me ) === 0 ) {
					$loc = $compare;
					break;
				}
			}

			if ( '__UNSET__' == $loc ) $loc = 'en_US'; // No match found, default to english
		}

		// Return
		if ( '__UNSET__' == $loc ) {
			return $lang;
		} else {
			return ( $loc ? $loc : $lang );
		}
	}


	/**
	 * Check if a user submitted something to admin.php?page=pb_export
	 *
	 * @return bool
	 */
	static function isFormSubmission() {

		if ( 'pb_export' != @$_REQUEST['page'] ) {
			return false;
		}

		if ( ! empty( $_POST ) ) {
			return true;
		}

		if ( count( $_GET ) > 1 ) {
			return true;
		}

		return false;
	}


	/**
	 * Inject house styles into CSS
	 *
	 * @param string $css
	 *
	 * @return string
	 */
	static function injectHouseStyles( $css ) {

		$scan = array(
			'/*__INSERT_PDF_HOUSE_STYLE__*/' => WP_CONTENT_DIR . '/themes/pdf-house-style.css',
			'/*__INSERT_EPUB_HOUSE_STYLE__*/' => WP_CONTENT_DIR . '/themes/epub-house-style.css',
			'/*__INSERT_MOBI_HOUSE_STYLE__*/' => WP_CONTENT_DIR . '/themes/mobi-house-style.css',
		);

		foreach ( $scan as $token => $replace_with ) {
			if ( is_file( $replace_with ) ) {
				$css = str_replace( $token, file_get_contents( $replace_with ), $css );
			}
		}

		return $css;
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
		set_time_limit( 0 );
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
		while ( @ob_end_flush() ); // Fix out-of-memory problem
		readfile( $filepath );

		exit;
	}


}