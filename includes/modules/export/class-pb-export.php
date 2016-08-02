<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Modules\Export;


use Pressbooks\Book;
use Pressbooks\CustomCss;
use Pressbooks\Container;
use Pressbooks\Metadata;


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
			$fullpath = CustomCss::getCustomCssFolder() . "$type.css";
			if ( ! is_file( $fullpath ) ) $fullpath = false;
		}

		if ( ! $fullpath ) {
			if ( Container::get('Sass')->isCurrentThemeCompatible( 1 ) ) { // Check for v1 SCSS themes
				$fullpath = realpath( get_stylesheet_directory() . "/export/$type/style.scss" );
			} elseif ( Container::get('Sass')->isCurrentThemeCompatible( 2 ) ) { // Check for v2 SCSS themes
				$fullpath = realpath( get_stylesheet_directory() . "/assets/styles/$type/style.scss" );
			} else {
				$fullpath = realpath( get_stylesheet_directory() . "/export/$type/style.css" );
			}
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
			if ( Container::get('Sass')->isCurrentThemeCompatible( 2 ) ) { // Check for v2 themes
				$fullpath = realpath( get_stylesheet_directory() . "/assets/scripts/$type/script.js" );
			} else {
				$fullpath = realpath( get_stylesheet_directory() . "/export/$type/script.js" );
			}
			if ( CustomCss::isCustomCss() && CustomCss::isRomanized() && $type == 'prince' ) {
				$fullpath = realpath( get_stylesheet_directory() . "/export/$type/script-romanize.js" );
			}
		}

		return $fullpath;
	}


	/**
	 * Is section parsing enabled?
	 *
	 * @return bool
	 */
	static function isParsingSubsections() {

		$options = get_option( 'pressbooks_theme_options_global' );

		return (bool) ( @$options['parse_subsections'] );
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

		if ( @$current_user->user_email && get_option( 'pressbooks_email_validation_logs' ) ) {
			$this->errorsEmail[] = $current_user->user_email;
		}

		\Pressbooks\Utility\email_error_log( $this->errorsEmail, $subject, $message );
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
	 * Create a timestamped filename.
	 *
	 * @param string $extension
	 * @param bool $fullpath
	 *
	 * @return string
	 */
	function timestampedFileName( $extension, $fullpath = true ) {
		$book_title = ( get_bloginfo( 'name' ) ) ? get_bloginfo( 'name' ) : __('book', 'pressbooks');
		$book_title_slug = sanitize_file_name( $book_title );
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

		return \Pressbooks\Sanitize\sanitize_xml_id( $id );
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
	 * Will create an html blob of copyright information, returns empty string
	 * if user doesn't want it displayed
	 *
	 * @param array $metadata
	 * @param string $title
	 * @param int $id
	 * @param string $section_author
	 * @return string $html blob
	 * @throws \Exception
	 */
	protected function doCopyrightLicense( $metadata, $title = '', $id = null, $section_author = '' ) {

		$options = get_option( 'pressbooks_theme_options_global' );
		foreach ( array( 'copyright_license' ) as $requiredGlobalOption ) {
			if ( ! isset ( $options[$requiredGlobalOption] ) ) {
				$options[$requiredGlobalOption] = 0;
			}
		}

		$html = $license = $copyright_holder = '';
		$lang = ! empty( $metadata['pb_language'] ) ? $metadata['pb_language'] : 'en';

		// if they don't want to see it, return
		// at minimum we need book copyright information set
		if ( false == $options['copyright_license'] || ! isset( $metadata['pb_book_license'] ) ) {
			return '';
		}

		// if no post $id given, we default to book copyright
		if ( ! empty( $id ) ) {
			$section_license = get_post_meta( $id, 'pb_section_license', true );
			$link = get_permalink( $id );
		} else {
			$section_license = '';
			$link = get_bloginfo( 'url' );
			$title = get_bloginfo( 'name' );
		}

		// Copyright holder, set in order of precedence
		if ( ! empty( $section_author ) ) {
			// section author higher priority than book author, copyrightholder
			$copyright_holder = $section_author;
		} elseif ( isset( $metadata['pb_copyright_holder'] ) ) {
			// book copyright holder higher priority than book author
			$copyright_holder = $metadata['pb_copyright_holder'];
		} elseif ( isset( $metadata['pb_author'] ) ) {
			// book author is the fallback, default
			$copyright_holder = $metadata['pb_author'];
		}

		// Copyright license, set in order of precedence
		if ( ! empty( $section_license ) ) {
			// section copyright higher priority than book
			$license = $section_license;
		} elseif ( isset( $metadata['pb_book_license'] ) ) {
			// book is the fallback, default
			$license = $metadata['pb_book_license'];
		}

		// get xml response from API
		$response = Metadata::getLicenseXml( $license, $copyright_holder, $link, $title, $lang );

		try {
			// convert to object
			$result = simplexml_load_string( $response );

			// evaluate it for errors
			if ( ! false === $result || ! isset( $result->html ) ) {
				throw new \Exception( 'Creative Commons license API not returning expected results at Pressbooks\Metadata::getLicenseXml' );
			} else {
				// process the response, return html
				$html = Metadata::getWebLicenseHtml( $result->html );
			}
		} catch ( \Exception $e ) {
			$this->logError( $e->getMessage() );
		}
		return $html;
	}

	/**
	 * Simple template system.
	 *
	 * @param string $path
	 * @param array $vars (optional)
	 *
	 * @return string
	 * @throws \Exception
	 */
	protected function loadTemplate( $path, array $vars = array() ) {

		return \Pressbooks\Utility\template($path, $vars);
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

		$path = \Pressbooks\Utility\get_media_prefix() . 'exports/';
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
	 * @see pressbooks/templates/admin/export.php
	 */
	static function formSubmit() {

		if ( false == static::isFormSubmission() || false == current_user_can( 'edit_posts' ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		// Set locale to UTF8 so escapeshellcmd() doesn't strip valid characters.
		setlocale( LC_CTYPE, 'UTF8', 'en_US.UTF-8' );
		putenv( 'LC_CTYPE=en_US.UTF-8' );

		// Override some WP behaviours when exporting
		\Pressbooks\Sanitize\fix_audio_shortcode();

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
			\Pressbooks\Redirect\location( get_admin_url( get_current_blog_id(), '/admin.php?page=pb_export' ) );
		}

		// Export
		if ( 'yes' == @$_GET['export'] && is_array( @$_POST['export_formats'] ) && check_admin_referer( 'pb-export' ) ) {

			// --------------------------------------------------------------------------------------------------------
			// Define modules

			$x = $_POST['export_formats'];
			$modules = array();

			if ( isset( $x['pdf'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Prince\Pdf';
			}
			if ( isset( $x['mpdf'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Mpdf\Pdf';
			}
			if ( isset( $x['epub'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Epub\Epub201'; // Must be set before MOBI
			}
			if ( isset( $x['epub3'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Epub\Epub3'; // Must be set before MOBI
			}
			if ( isset( $x['mobi'] ) ) {
				if  ( !isset( $x['epub'] ) ) { // Make sure Epub source file is generated
					$modules[] = '\Pressbooks\Modules\Export\Epub\Epub201'; // Must be set before MOBI
				}
				$modules[] = '\Pressbooks\Modules\Export\Mobi\Kindlegen'; // Must be set after EPUB
			}
			if ( isset( $x['icml'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\InDesign\Icml';
			}
			if ( isset( $x['xhtml'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Xhtml\Xhtml11';
			}
			if ( isset( $x['wxr'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\WordPress\Wxr';
			}
			if ( isset ( $x['vanillawxr'] ) ){
				$modules[] = '\Pressbooks\Modules\Export\WordPress\VanillaWxr';
			}
			if ( isset ( $x['odt'] ) ){
				$modules[] = '\Pressbooks\Modules\Export\Odt\Odt';
			}

			// --------------------------------------------------------------------------------------------------------
			// Clear cache? Range is 1 hour.

			$last_export = get_option( 'pressbooks_last_export' );
			$within_range = time() - $last_export;
			if ( $within_range > ( 60 * 60 ) ) {
				\Pressbooks\Book::deleteBookObjectCache();
				update_option( 'pressbooks_last_export', time() );
			}

			// --------------------------------------------------------------------------------------------------------
			// Do Export

			@set_time_limit( 300 );

			$redirect_url = get_admin_url( get_current_blog_id(), '/admin.php?page=pb_export' );
			$conversion_error = array();
			$validation_warning = array();
			$outputs = array();

			foreach ( $modules as $module ) {

				/** @var \Pressbooks\Modules\Export\Export $exporter */
				$exporter = new $module( array() );

				if ( ! $exporter->convert() ) {
					$conversion_error[$module] = $exporter->getOutputPath();
				} else {
					if ( ! $exporter->validate() ) {
						$validation_warning[$module] = $exporter->getOutputPath();
					}
				}

				// Add to outputs array

				$outputs[$module] = $exporter->getOutputPath();

				// Stats hook
				do_action( 'pressbooks_track_export', substr( strrchr( $module, '\\' ), 1 ) );
			}

			delete_transient( 'dirsize_cache' ); /** @see get_dirsize() */

			// --------------------------------------------------------------------------------------------------------
			// MOBI cleanup

			if ( isset( $x['mobi'] ) && !isset( $x['epub'] ) ) {
				unlink( $outputs['\Pressbooks\Modules\Export\Epub\Epub201'] );
			}

			// --------------------------------------------------------------------------------------------------------
			// No errors?

			if ( empty( $conversion_error ) && empty( $validation_warning ) ) {
				// Ok!
				\Pressbooks\Redirect\location( $redirect_url );
			}

			// --------------------------------------------------------------------------------------------------------
			// Error exceptions

			if ( isset( $validation_warning['\Pressbooks\Modules\Export\Prince\Pdf'] ) ) {

				// The PDF is garbage and we don't want the user to have it.
				// Delete file. Report error instead of warning.
				unlink( $validation_warning['\Pressbooks\Modules\Export\Prince\Pdf'] );
				$conversion_error['\Pressbooks\Modules\Export\Prince\Pdf'] = $validation_warning['\Pressbooks\Modules\Export\Prince\Pdf'];
				unset ( $validation_warning['\Pressbooks\Modules\Export\Prince\Pdf'] );
			}

			// --------------------------------------------------------------------------------------------------------
			// Errors :(

			if ( count( $conversion_error ) ) {
				// Conversion error
				\Pressbooks\Redirect\location( $redirect_url . '&export_error=true' );
			}

			if ( count( $validation_warning ) ) {
				// Validation warning
				\Pressbooks\Redirect\location( $redirect_url . '&export_warning=true' );
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

			$codes = \Pressbooks\L10n\wplang_codes();
			$book_lang = Book::getBookInformation();
			$book_lang = @$book_lang['pb_language'];
			$book_lang = $codes[ $book_lang ];

			foreach ( $compare_with as $compare ) {

				$compare = str_replace( 'pressbooks-', '', $compare );

				if ( strpos( $book_lang, $compare ) === 0 ) {
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
			'/*__INSERT_PDF_HOUSE_STYLE__*/' => PB_PLUGIN_DIR . '/assets/scss/partials/_pdf-house-style.scss',
			'/*__INSERT_EPUB_HOUSE_STYLE__*/' => PB_PLUGIN_DIR . '/assets/scss/partials/_epub-house-style.scss',
			'/*__INSERT_MOBI_HOUSE_STYLE__*/' => PB_PLUGIN_DIR . '/assets/scss/partials/_mobi-house-style.scss',
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
