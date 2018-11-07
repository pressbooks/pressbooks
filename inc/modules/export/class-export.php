<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export;

use function \Pressbooks\Utility\getset;
use function \Pressbooks\Utility\scandir_by_date;
use Pressbooks\Container;
use Pressbooks\CustomCss;

// IMPORTANT! if this isn't set correctly before include, with a trailing slash, PclZip will fail.
if ( ! defined( 'PCLZIP_TEMPORARY_DIR' ) ) {
	if ( ! empty( $_ENV['TMP'] ) ) {
		define( 'PCLZIP_TEMPORARY_DIR', trailingslashit( realpath( $_ENV['TMP'] ) ) );
	} elseif ( ! empty( $_ENV['TMPDIR'] ) ) {
		define( 'PCLZIP_TEMPORARY_DIR', trailingslashit( realpath( $_ENV['TMPDIR'] ) ) );
	} elseif ( ! empty( $_ENV['TEMP'] ) ) {
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
	public $errorsEmail = [];


	/**
	 * Reserved html IDs.
	 *
	 * @var array
	 */
	protected $reservedIds = [
		'cover-image',
		'half-title-page',
		'title-page',
		'copyright-page',
		'toc',
		'pressbooks-promo',
	];


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
			if ( ! is_file( $fullpath ) ) {
				$fullpath = false;
			}
		}

		if ( ! $fullpath ) {
			// Look for SCSS file
			$fullpath = Container::get( 'Styles' )->getPathToScss( $type );
			if ( ! $fullpath ) {
				// Look For CSS file
				$dir = Container::get( 'Styles' )->getDir();
				$fullpath = realpath( "$dir/export/$type/style.css" );
			}
		}

		return $fullpath;
	}

	/**
	 * Return the fullpath to an export format's latest compiled stylesheet.
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	function getLatestExportStylePath( $type ) {
		// This method only supports Prince stylesheets at the moment.
		if ( in_array( $type, [ 'prince' ], true ) ) {
			foreach ( scandir_by_date( Container::get( 'Sass' )->pathToUserGeneratedCss() ) as $file ) {
				if ( preg_match( '/(' . $type . ')-([0-9]*)/', $file, $matches ) ) {
					return Container::get( 'Sass' )->pathToUserGeneratedCss() . "/$type-{$matches[2]}.css";
				}
			}
		}

		return false;
	}

	/**
	 * Return the URL to an export format's latest compiled stylesheet.
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	function getLatestExportStyleUrl( $type ) {
		// This method only supports Prince stylesheets at the moment.
		if ( in_array( $type, [ 'prince' ], true ) ) {
			foreach ( scandir_by_date( Container::get( 'Sass' )->pathToUserGeneratedCss() ) as $file ) {
				if ( preg_match( '/(' . $type . ')-([0-9]*)/', $file, $matches ) ) {
					return Container::get( 'Sass' )->urlToUserGeneratedCss() . "/$type-{$matches[2]}.css";
				}
			}
		}

		return false;
	}

	/**
	 * Remove all but the most recent compiled stylesheet.
	 *
	 * @param string $type
	 * @param int $max
	 */
	function truncateExportStylesheets( $type, $max = 1 ) {
		// This method only supports Prince stylesheets at the moment.
		if ( in_array( $type, [ 'prince' ], true ) ) {
			$stylesheets = scandir_by_date( Container::get( 'Sass' )->pathToUserGeneratedCss() );
			$max = absint( $max );
			$i = 1;
			foreach ( $stylesheets as $stylesheet ) {
				if ( preg_match( '/(' . $type . ')-([0-9]*)/', $stylesheet, $matches ) ) {
					if ( $i > $max ) {
						unlink( Container::get( 'Sass' )->pathToUserGeneratedCss() . '/' . $stylesheet );
					}
					$i++;
				}
			}
		}
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
			if ( ! is_file( $fullpath ) ) {
				$fullpath = false;
			}
		}

		if ( ! $fullpath ) {
			$dir = Container::get( 'Styles' )->getDir();
			if ( Container::get( 'Styles' )->isCurrentThemeCompatible( 2 ) ) {
				// Check for v2 themes
				$fullpath = realpath( "$dir/assets/scripts/$type/script.js" );
			} else {
				$fullpath = realpath( "$dir/export/$type/script.js" );
			}
			if ( CustomCss::isCustomCss() && CustomCss::isRomanized() && 'prince' === $type ) {
				$fullpath = realpath( get_stylesheet_directory() . "/export/$type/script-romanize.js" );
			}
		}

		return $fullpath;
	}

	/**
	 * Return the public URL to an export module's Javascript file.
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	function getExportScriptUrl( $type ) {

		$url = false;

		$dir = Container::get( 'Styles' )->getDir();
		if ( Container::get( 'Styles' )->isCurrentThemeCompatible( 2 ) && realpath( "$dir/assets/scripts/$type/script.js" ) ) {
			$url = apply_filters( 'pb_stylesheet_directory_uri', get_stylesheet_directory_uri() ) . "/assets/scripts/$type/script.js";
		} elseif ( realpath( "$dir/export/$type/script.js" ) ) {
			$url = apply_filters( 'pb_stylesheet_directory_uri', get_stylesheet_directory_uri() ) . "/export/$type/script.js";
		}
		if ( CustomCss::isCustomCss() && CustomCss::isRomanized() && 'prince' === $type ) {
			$url = get_stylesheet_directory_uri() . "/export/$type/script-romanize.js";
		}

		return $url;
	}

	/**
	 * Is section parsing enabled?
	 *
	 * @return bool
	 */
	static function shouldParseSubsections() {

		$options = get_option( 'pressbooks_theme_options_global' );

		if ( isset( $options['parse_subsections'] ) ) {
			return (bool) ( $options['parse_subsections'] );
		}

		return false;
	}

	/**
	 * Log errors using wp_mail() and error_log(), include useful WordPress info.
	 *
	 * @param string $message
	 * @param array $more_info
	 */
	function logError( $message, array $more_info = [] ) {

		/** $var \WP_User $current_user */
		global $current_user;

		$subject = get_class( $this );

		$info = [
			'time' => strftime( '%c' ),
			'user' => ( isset( $current_user ) ? $current_user->user_login : '__UNKNOWN__' ),
			'site_url' => site_url(),
			'blog_id' => get_current_blog_id(),
			'theme' => '' . wp_get_theme(), // Stringify by appending to empty string
		];

		$message = print_r( array_merge( $info, $more_info ), true ) . $message; // @codingStandardsIgnoreLine
		$exportoptions = get_option( 'pressbooks_export_options' );
		if ( $current_user->user_email && isset( $exportoptions['email_validation_logs'] ) && 1 === absint( $exportoptions['email_validation_logs'] ) ) {
			$this->errorsEmail[] = $current_user->user_email;
		}

		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			\Pressbooks\Utility\email_error_log( $this->errorsEmail, $subject, $message );
		}
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
		$book_title = ( get_bloginfo( 'name' ) ) ? get_bloginfo( 'name' ) : __( 'book', 'pressbooks' );
		$book_title_slug = sanitize_file_name( $book_title );
		$book_title_slug = str_replace( [ '+' ], '', $book_title_slug ); // Remove symbols which confuse Apache (Ie. form urlencoded spaces)
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
		if ( $within_range > ( MINUTE_IN_SECONDS * 5 ) ) {
			return false;
		}

		// Correct md5?
		if ( md5( NONCE_KEY . $timestamp ) !== $md5 ) {
			return false;
		}

		return true;
	}


	/**
	 * Fix annoying characters that the user probably didn't do on purpose
	 *
	 * @deprecated
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	function fixAnnoyingCharacters( $html ) {

		// Replace Non-breaking spaces with normal spaces
		// TODO: Some users want this, others do not want this, make up your mind...
		// $html = preg_replace( '/\xC2\xA0/', ' ', $html ); @codingStandardsIgnoreLine

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

		if ( in_array( $id, $this->reservedIds, true ) ) {
			$id = uniqid( "$id-" );
		}

		return \Pressbooks\Sanitize\sanitize_xml_id( $id );
	}


	/**
	 * Create a temporary directory, no trailing slash!
	 *
	 * @return string
	 */
	protected function createTmpDir() {

		$temp_file = tempnam( sys_get_temp_dir(), '' );
		@unlink( $temp_file ); // @codingStandardsIgnoreLine
		mkdir( $temp_file );
		if ( ! is_dir( $temp_file ) ) {
			return '';

		}

		return untrailingslashit( $temp_file );
	}

	/**
	 * Convert an XML string via XSLT file.
	 *
	 * @param string $content
	 * @param string $path_to_xsl
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
	 * Will create an html blob of copyright, returns empty string if something goes wrong
	 *
	 * @param array $metadata
	 * @param string $title (optional)
	 * @param int $id (optional)
	 * @param string $section_author (deprecated)
	 *
	 * @return string $html blob
	 */
	protected function doCopyrightLicense( $metadata, $title = '', $id = 0, $section_author = '' ) {

		if ( ! empty( $section_author ) ) {
			_deprecated_argument( __METHOD__, '4.1.0' );
		}

		try {
			$licensing = new \Pressbooks\Licensing();
			return $licensing->doLicense( $metadata, $id, $title );
		} catch ( \Exception $e ) {
			$this->logError( $e->getMessage() );
		}
		return '';
	}

	/**
	 * Returns a string of text to be used in TOC, returns empty string if user doesn't want it displayed
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	protected function doTocLicense( $post_id ) {
		$option = get_option( 'pressbooks_theme_options_global' );
		if ( ! empty( $option['copyright_license'] ) ) {
			if ( 1 === absint( $option['copyright_license'] ) ) {
				$section_license = get_post_meta( $post_id, 'pb_section_license', true );
				if ( ! empty( $section_license ) ) {

					$licensing = new \Pressbooks\Licensing();
					$supported_types = $licensing->getSupportedTypes();
					if ( array_key_exists( $section_license, $supported_types ) ) {
						return $supported_types[ $section_license ]['desc'];
					} else {
						return '';
					}
				}
			} elseif ( 2 === absint( $option['copyright_license'] ) ) {
				return '';
			}
		}
		return '';
	}

	/**
	 * Returns a string of text to be used in a section (chapter, front-matter, back-matter, ...)
	 * returns empty string if user doesn't want it displayed
	 *
	 * @param array $metadata
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	protected function doSectionLevelLicense( $metadata, $post_id ) {
		$option = get_option( 'pressbooks_theme_options_global' );
		if ( ! empty( $option['copyright_license'] ) ) {
			if ( 1 === absint( $option['copyright_license'] ) ) {
				return '';
			} elseif ( 2 === absint( $option['copyright_license'] ) ) {
				$section_license = get_post_meta( $post_id, 'pb_section_license', true );
				if ( ! empty( $section_license ) ) {
					try {
						$licensing = new \Pressbooks\Licensing();
						return $licensing->doLicense( $metadata, $post_id );
					} catch ( \Exception $e ) {
						$this->logError( $e->getMessage() );
					}
				}
			}
		}
		return '';
	}

	/**
	 * Simple template system.
	 *
	 * @param string $path
	 * @param array $vars (optional)
	 *
	 * @return string
	 */
	protected function loadTemplate( $path, array $vars = [] ) {
		try {
			return \Pressbooks\Utility\template( $path, $vars );
		} catch ( \Exception $e ) {
			if ( WP_DEBUG ) {
				return "File not found: {$path}";
			} else {
				return '';
			}
		}
	}


	/**
	 * Detect MIME Content-type for a file.
	 *
	 * @param string $file fullpath
	 *
	 * @return string
	 */
	static function mimeType( $file ) {
		return \Pressbooks\Media\mime_type( $file );
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
			wp_mkdir_p( $path );
		}

		$path_to_htaccess = $path . '.htaccess';
		if ( ! file_exists( $path_to_htaccess ) ) {
			// Restrict access
			\Pressbooks\Utility\put_contents( $path_to_htaccess, "deny from all\n" );
		}

		/**
		 * @since 5.3.0
		 *
		 * Filters the export folder path
		 * Use this hook to change the location of the export folder.
		 *
		 * @param string $path The path to the Pressbooks export folder
		 */
		$path = apply_filters( 'pb_get_export_folder', $path );

		return $path;
	}


	/**
	 * Catch form submissions
	 *
	 * @see pressbooks/templates/admin/export.php
	 */
	static function formSubmit() {

		if ( false === static::isFormSubmission() || false === current_user_can( 'edit_posts' ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		// Override some WP behaviours when exporting
		\Pressbooks\Sanitize\fix_audio_shortcode();

		// Download
		if ( ! empty( $_GET['download_export_file'] ) ) {
			$filename = sanitize_file_name( $_GET['download_export_file'] );
			static::downloadExportFile( $filename, false );
			exit;
		}

		// Delete
		if ( isset( $_POST['delete_export_file'] ) && isset( $_POST['filename'] ) && check_admin_referer( 'pb-delete-export' ) ) {
			$filename = sanitize_file_name( $_POST['filename'] );
			unlink( static::getExportFolder() . $filename );
			delete_transient( 'dirsize_cache' ); /** @see get_dirsize() */
			\Pressbooks\Redirect\location( get_admin_url( get_current_blog_id(), '/admin.php?page=pb_export' ) );
			exit;
		}

		// Export
		if ( 'yes' === getset( '_GET', 'export' ) && is_array( getset( '_REQUEST', 'export_formats' ) ) && check_admin_referer( 'pb-export' ) ) {

			// --------------------------------------------------------------------------------------------------------
			// Define modules

			$x = $_REQUEST['export_formats'];
			$modules = [];

			if ( isset( $x['pdf'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Prince\Pdf';
			}
			if ( isset( $x['print_pdf'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Prince\PrintPdf';
			}
			if ( isset( $x['epub'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Epub\Epub201'; // Must be set before MOBI
			}
			if ( isset( $x['epub3'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Epub\Epub3';
			}
			if ( isset( $x['mobi'] ) ) {
				if ( ! isset( $x['epub'] ) ) { // Make sure Epub source file is generated
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
			if ( isset( $x['vanillawxr'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\WordPress\VanillaWxr';
			}
			if ( isset( $x['odt'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Odt\Odt';
			}
			if ( isset( $x['htmlbook'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\HTMLBook\HTMLBook';
			}

			// --------------------------------------------------------------------------------------------------------
			// Other People's Plugins

			/**
			 * Catch enabled custom formats and add their classes to the $modules array.
			 *
			 * For example, here's how one might catch a hypothetical Word exporter:
			 *
			 * add_filter( 'pb_active_export_modules', function ( $modules ) {
			 *    if ( isset( $_POST['export_formats']['docx'] ) ) {
			 *        $modules[] = '\Pressbooks\Modules\Export\Docx\Docx';
			 *    }
			 *    return $modules;
			 * } );
			 *
			 * @since 3.9.8
			 *
			 * @param array $modules
			 */
			$modules = apply_filters( 'pb_active_export_modules', $modules );

			/**
			 * Let other plugins tweak things before exporting
			 *
			 * @since 4.4.0
			 */
			do_action( 'pb_pre_export' );

			// --------------------------------------------------------------------------------------------------------
			// Clear cache? Range is 1 hour.

			$last_export = get_option( 'pressbooks_last_export' );
			$within_range = time() - $last_export;
			if ( $within_range > ( HOUR_IN_SECONDS ) ) {
				\Pressbooks\Book::deleteBookObjectCache();
				update_option( 'pressbooks_last_export', time() );
			}

			// --------------------------------------------------------------------------------------------------------
			// Do Export

			/**
			 * Maximum execution time, in seconds. If set to zero, no time limit
			 * Overrides PHP's max_execution_time of a Nginx->PHP-FPM->PHP configuration
			 * See also request_terminate_timeout (PHP-FPM) and fastcgi_read_timeout (Nginx)
			 *
			 * @since 5.6.0
			 *
			 * @param int $seconds
			 * @param string $some_action
			 *
			 * @return int
			 */
			@set_time_limit( apply_filters( 'pb_set_time_limit', 600, 'export' ) ); // @codingStandardsIgnoreLine

			$redirect_url = get_admin_url( get_current_blog_id(), '/admin.php?page=pb_export' );
			$conversion_error = [];
			$validation_warning = [];
			$outputs = [];

			foreach ( $modules as $module ) {

				/** @var \Pressbooks\Modules\Export\Export $exporter */
				$exporter = new $module( [] );

				if ( ! $exporter->convert() ) {
					$conversion_error[ $module ] = $exporter->getOutputPath();
				} else {
					if ( ! $exporter->validate() ) {
						$validation_warning[ $module ] = $exporter->getOutputPath();
					}
				}

				// Add to outputs array

				$outputs[ $module ] = $exporter->getOutputPath();

				/**
				 * Stats hook
				 *
				 * @param string
				 */
				do_action( 'pressbooks_track_export', substr( strrchr( $module, '\\' ), 1 ) );
			}

			delete_transient( 'dirsize_cache' ); /** @see get_dirsize() */

			// --------------------------------------------------------------------------------------------------------
			// MOBI cleanup

			if ( isset( $x['mobi'] ) && ! isset( $x['epub'] ) ) {
				unlink( $outputs['\Pressbooks\Modules\Export\Epub\Epub201'] );
			}

			// --------------------------------------------------------------------------------------------------------
			// No errors?

			if ( empty( $conversion_error ) && empty( $validation_warning ) ) {
				if ( ! empty( $_REQUEST['preview'] ) && count( $outputs ) === 1 ) {
					// Preview the file, then delete it
					$filename_fullpath = array_values( $outputs );
					$filename_fullpath = array_shift( $filename_fullpath );
					$filename = basename( $filename_fullpath );
					static::downloadExportFile( $filename, true );
					unlink( $filename_fullpath );
				} else {
					// Redirect the user back to the form
					\Pressbooks\Redirect\location( $redirect_url );
				}
				exit;
			}

			// --------------------------------------------------------------------------------------------------------
			// Error exceptions

			if ( isset( $validation_warning['\Pressbooks\Modules\Export\Prince\Pdf'] ) ) {

				// The PDF is garbage and we don't want the user to have it.
				// Delete file. Report error instead of warning.
				unlink( $validation_warning['\Pressbooks\Modules\Export\Prince\Pdf'] );
				$conversion_error['\Pressbooks\Modules\Export\Prince\Pdf'] = $validation_warning['\Pressbooks\Modules\Export\Prince\Pdf'];
				unset( $validation_warning['\Pressbooks\Modules\Export\Prince\Pdf'] );
			}

			if ( isset( $validation_warning['\Pressbooks\Modules\Export\Prince\PrintPdf'] ) ) {

				// The PDF is garbage and we don't want the user to have it.
				// Delete file. Report error instead of warning.
				unlink( $validation_warning['\Pressbooks\Modules\Export\Prince\PrintPdf'] );
				$conversion_error['\Pressbooks\Modules\Export\Prince\PrintPdf'] = $validation_warning['\Pressbooks\Modules\Export\Prince\PrintPdf'];
				unset( $validation_warning['\Pressbooks\Modules\Export\Prince\PrintPdf'] );
			}

			// --------------------------------------------------------------------------------------------------------
			// Handle errors :(

			if ( is_countable( $conversion_error ) && count( $conversion_error ) ) {
				// Conversion error
				\Pressbooks\Redirect\location( $redirect_url . '&export_error=true' );
			}

			if ( is_countable( $validation_warning ) && count( $validation_warning ) ) {
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

		if ( '__UNSET__' === $loc && function_exists( 'get_available_languages' ) ) {

			$compare_with = get_available_languages( PB_PLUGIN_DIR . '/languages/' );
			$codes = \Pressbooks\L10n\wplang_codes();
			$book_lang = $codes[ \Pressbooks\L10n\get_book_language() ];

			foreach ( $compare_with as $compare ) {
				$compare = str_replace( 'pressbooks-', '', $compare );
				if ( strpos( $book_lang, $compare ) === 0 ) {
					$loc = $compare;
					break;
				}
			}
			if ( '__UNSET__' === $loc ) {
				$loc = 'en_US'; // No match found, default to english
			}
		}

		// Return the language
		if ( '__UNSET__' === $loc ) {
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

		if ( empty( $_REQUEST['page'] ) ) {
			return false;
		}

		if ( 'pb_export' !== $_REQUEST['page'] ) {
			return false;
		}

		if ( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			return true;
		}

		if ( count( $_GET ) > 1 ) {
			return true;
		}

		return false;
	}


	/**
	 * Download an .htaccess protected file from the exports directory.
	 *
	 * @param string $filename sanitized $_GET['download_export_file']
	 * @param bool $inline
	 */
	protected static function downloadExportFile( $filename, $inline ) {
		$filepath = static::getExportFolder() . $filename;
		\Pressbooks\Redirect\force_download( $filepath, $inline );
	}
}
