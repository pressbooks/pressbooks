<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotValidated
// @phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r
// @phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged

namespace Pressbooks\Modules\Export;

use function Pressbooks\add_error;
use function Pressbooks\L10n\get_book_language;
use function Pressbooks\L10n\wplang_codes;
use function Pressbooks\Redirect\force_download;
use function Pressbooks\Sanitize\fix_audio_shortcode;
use function Pressbooks\Sanitize\sanitize_xml_id;
use function Pressbooks\Utility\create_tmp_file;
use function Pressbooks\Utility\email_error_log;
use function Pressbooks\Utility\get_media_prefix;
use function Pressbooks\Utility\put_contents;
use function Pressbooks\Utility\template;
use function \Pressbooks\Utility\getset;
use function \Pressbooks\Utility\scandir_by_date;
use Pressbooks\Book;
use Pressbooks\Container;
use Pressbooks\CustomCss;
use Pressbooks\Modules\BackgroundProcessing\BackgroundJob;

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
	 * @var bool
	 */
	static $switchedLocale;

	/**
	 * @var array
	 */
	static $exportConversionError = [];

	/**
	 * @var array
	 */
	static $exportValidationWarning = [];

	/**
	 * @var []
	 */
	static $exportOutputs = [];

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
	 * Stores arguments for the current export modules being processed.
	 * Used to pass arguments to background jobs.
	 * @var array
	 */
	protected static $currentExportModuleArgs = [];

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
					return Container::get( 'Sass' )->urlToUserGeneratedCss( true ) . "/$type-{$matches[2]}.css";
				}
			}
		}

		return false;
	}

	/**
	 * Remove all but the most recent compiled stylesheet.
	 *
	 * @param string $type
	 * @param int    $max
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
	 * @param array  $more_info
	 */
	function logError( $message, array $more_info = [] ) {

		/**
	* $var \WP_User $current_user
*/
		global $current_user;

		$subject = get_class( $this );

		$info = [
			'time' => date( 'D M H:i:s Y' ),
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

		if ( defined( 'WP_TESTS_MULTISITE' ) ) {
			// Unit tests
			if ( empty( $more_info['warning'] ) ) {
				error_log( "\n{$subject}\n{$message}\n" ); // @codingStandardsIgnoreLine
			}
		} else {
			email_error_log( $this->errorsEmail, $subject, $message );
		}
	}

	/**
	 * Create a temporary file that automatically gets deleted on __sleep()
	 *
	 * @return string fullpath
	 */
	function createTmpFile() {

		return create_tmp_file();
	}

	/**
	 * Create a timestamped filename.
	 *
	 * @param string $extension
	 * @param bool   $fullpath
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

		return sanitize_xml_id( $id );
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
	 * Will create an html blob of copyright, returns empty string if something goes wrong
	 *
	 * @param array  $metadata
	 * @param string $title          (optional)
	 * @param int    $id             (optional)
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
	 * @param int   $post_id  Post ID.
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
	 * @param array  $vars (optional)
	 *
	 * @return string
	 */
	protected function loadTemplate( $path, array $vars = [] ) {
		try {
			return template( $path, $vars );
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

		$path = get_media_prefix() . 'exports/';
		if ( ! file_exists( $path ) ) {
			wp_mkdir_p( $path );
		}

		$path_to_htaccess = $path . '.htaccess';
		if ( ! file_exists( $path_to_htaccess ) ) {
			// Restrict access
			put_contents( $path_to_htaccess, "deny from all\n" );
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
	 * Get an array of module classnames that should be processed in the background.
	 *
	 * @return array
	 */
	protected static function getBackgroundExportTypes(): array {
		return apply_filters('pb_background_export_types', [
			'\\Pressbooks\\Modules\\Export\\Prince\\Pdf',
			'\\Pressbooks\\Modules\\Export\\Prince\\PrintPdf',
			'\\Pressbooks\\Modules\\Export\\Prince\\Docraptor',
			'\\Pressbooks\\Modules\\Export\\Prince\\DocraptorPrint',
			'\\Pressbooks\\Modules\\Export\\Epub\\Epub',
			'\\Pressbooks\\Modules\\Export\\Xhtml\\Xhtml11',
			'\\Pressbooks\\Modules\\Export\\WordPress\\Wxr',
			'\\Pressbooks\\Modules\\Export\\WordPress\\VanillaWxr',
		]);
	}

	/**
	 * Gets a simplified slug for an export module classname.
	 * Example: \Pressbooks\Modules\Export\Prince\Pdf -> pdf
	 *
	 * @param string $module_classname
	 * @return string
	 */
	protected static function getExportFormatSlugFromClassname(string $module_classname): string {
		$parts = explode('\\', $module_classname);
		$slug = strtolower(end($parts));
		// Specific overrides if class name doesn't directly map
		if ($slug === 'printpdf') return 'print-pdf';
		if ($slug === 'docraptorprint') return 'docraptor-print-pdf';
		if ($slug === 'xhtml11') return 'xhtml';
		if ($slug === 'vanillawxr') return 'vanilla-wxr';
		// Add more specific mappings as needed
		return $slug;
	}

	/**
	 * Gets a user-friendly name for an export module.
	 * Uses the existing get_name_from_module_classname if available and suitable,
	 * or provides a fallback.
	 *
	 * @param string $module_classname
	 * @return string
	 */
	public static function getFriendlyNameForModule(string $module_classname): string {
		if (function_exists('\Pressbooks\Modules\Export\get_name_from_module_classname')) {
			// This function is in namespace.php, ensure it's loaded.
			// It returns names like "Digital PDF", "EPUB" etc.
			return \Pressbooks\Modules\Export\get_name_from_module_classname($module_classname);
		}
		$parts = explode('\\', $module_classname);
		return end($parts); // Fallback to class name
	}

	/**
	 * Get the public URL to the exports folder.
	 *
	 * @return string
	 */
	public static function getExportFolderUrl(): string {
		$export_path = self::getExportFolder(); // This is a file path
		$upload_dir = wp_get_upload_dir(); // Correct way to get base paths/URLs

		$upload_base_path = trailingslashit($upload_dir['basedir']);
		$upload_base_url = trailingslashit($upload_dir['baseurl']);

		// Ensure $export_path is absolute before attempting to make it relative to $upload_base_path
		// realpath() can return false if path does not exist, handle this.
		$real_export_path = realpath($export_path);
		if ($real_export_path === false) {
			 // If path doesn't exist yet (e.g. first export), try to construct based on known structure
			if (is_multisite()) {
				$path_fragment = 'sites/' . get_current_blog_id() . '/pressbooks/exports/';
			} else {
				$path_fragment = 'pressbooks/exports/';
			}
			return $upload_base_url . $path_fragment;
		}

		$real_export_path = trailingslashit($real_export_path);

		if (strpos($real_export_path, $upload_base_path) === 0) {
			$relative_path = str_replace($upload_base_path, '', $real_export_path);
			return $upload_base_url . $relative_path;
		} else {
			// Fallback or error: Export path is not within the uploads directory.
			// This might indicate a custom export folder configuration.
			// For now, return empty or log an error. Depending on `pb_get_export_folder` filter.
			// A more robust solution would be needed if exports can be outside uploads.
			// However, Pressbooks' get_media_prefix() usually points within uploads.
			return ''; // Or apply a filter to allow defining this URL
		}
	}

	/**
	 * Pre-Export
	 */
	public static function preExport() {
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
			Book::deleteBookObjectCache();
			update_option( 'pressbooks_last_export', time() );
		}

		static::$switchedLocale = switch_to_locale( self::locale() );
	}

	/**
	 * Define modules
	 *
	 * @return array
	 */
	static function modules() {
		$modules = [];
		if ( is_array( getset( '_GET', 'export_formats' ) ) && check_admin_referer( 'pb-export' ) ) {

			// --------------------------------------------------------------------------------------------------------
			// Define modules

			$x = $_GET['export_formats'];

			if ( isset( $x['pdf'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Prince\Pdf';
			}
			if ( isset( $x['print_pdf'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Prince\PrintPdf';
			}
			if ( isset( $x['epub'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\Epub\Epub';
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
			if ( isset( $x['weblinks'] ) ) {
				$modules[] = '\Pressbooks\Modules\Export\ThinCC\WebLinks';
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
		}

		return $modules;
	}

	/**
	 * Catch form submissions
	 *
	 * @see pressbooks/templates/admin/export.blade.php
	 */
	static function formSubmit() {

		if ( false === static::isFormSubmission() || false === current_user_can( 'edit_posts' ) ) {
			// Don't do anything in this function, bail.
			return;
		}

		// Store export arguments if present, to be used by exportGenerator
		// This is a simplistic example; needs to align with how your form actually submits options.
		static::$currentExportModuleArgs = $_POST['export_options'] ?? []; // Example: if you have specific options per module in form

		// Override some WP behaviours when exporting
		\Pressbooks\Sanitize\fix_audio_shortcode();

		// Download
		if ( ! empty( $_GET['download_export_file'] ) ) {
			$filename = sanitize_file_name( $_GET['download_export_file'] );
			// Add security for job-based downloads
			if (isset($_GET['job_id']) && isset($_GET['_wpnonce'])) {
				$job_id = absint($_GET['job_id']);
				if (wp_verify_nonce(sanitize_key($_GET['_wpnonce']), 'download_export_job_' . $job_id)) {
					// Potentially check if current user owns the job or has rights
					global $wpdb;
					$job = $wpdb->get_row($wpdb->prepare("SELECT user_id, output_file_path FROM {$wpdb->prefix}pressbooks_export_jobs WHERE id = %d", $job_id));
					if ($job && $job->user_id == get_current_user_id() && basename($job->output_file_path) === $filename) {
						static::downloadExportFile( $filename, false ); // Original method assumes file is in default export dir
						exit;
					} else {
						wp_die(__( 'Invalid job ID or permission denied for download.', 'pressbooks' ), 'Error', ['response' => 403]);
					}
				} else {
					wp_die(__( 'Invalid download link.', 'pressbooks' ), 'Error', ['response' => 403]);
				}
			} else {
				// Fallback to old download mechanism if no job_id (e.g. for non-background processed files)
				// This part might need to be phased out or also secured if direct downloads are generally disallowed.
				static::downloadExportFile( $filename, false );
				exit;
			}
		}
	}

	/**
	 * Post Export
	 */
	public static function postExport() {

		$conversion_error = static::$exportConversionError;
		$validation_warning = static::$exportValidationWarning;
		$outputs = static::$exportOutputs;

		delete_transient( 'dirsize_cache' );
		/**
		 * @see get_dirsize()
		 **/

		if ( static::$switchedLocale ) {
			restore_previous_locale();
		}

		// --------------------------------------------------------------------------------------------------------
		// No errors?

		if ( empty( $conversion_error ) && empty( $validation_warning ) ) {
			// Redirect the user back to the form
			return;
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
			add_error( __( 'The export failed. See logs for more details.', 'pressbooks' ) );
		}

		if ( is_countable( $validation_warning ) && count( $validation_warning ) ) {
			// Validation warning
			$exportoptions = get_option( 'pressbooks_export_options', [] );
			if ( ! empty( $exportoptions ) && 1 === (int) $exportoptions['email_validation_logs'] || is_super_admin() ) {
				$export_warning = sprintf(
					'<p>%s</p>%s',
					__( 'Warning: The export has validation errors. See logs for more details.', 'pressbooks' ),
					( isset( $exportoptions['email_validation_logs'] ) && 1 === (int) $exportoptions['email_validation_logs'] ) ? '<p>' . __( 'Emailed to:', 'pressbooks' ) . ' ' . wp_get_current_user()->user_email . '</p>' : ''
				);
				add_error( $export_warning );
			}
		}
	}

	/**
	 * @return string
	 */
	static function locale() {
		$loc = 'en_US';
		if ( function_exists( 'get_available_languages' ) ) {
			$compare_with = get_available_languages( PB_PLUGIN_DIR . '/languages/' );
			$codes = wplang_codes();
			$book_lang = $codes[ get_book_language() ];
			foreach ( $compare_with as $compare ) {
				$compare = str_replace( 'pressbooks-', '', $compare );
				if ( str_starts_with( $book_lang, $compare ) ) {
					$loc = $compare;
					break;
				}
			}
		}
		return $loc;
	}

	/**
	 * Check if a user submitted something to admin.php?page=pb_export
	 *
	 * @return bool
	 */
	static function isFormSubmission() {

		// EventSource (Progress bar)
		if ( wp_doing_ajax() ) {
			if ( empty( $_REQUEST['action'] ) ) {
				return false;
			}

			if ( 'export-book' !== $_REQUEST['action'] ) {
				return false;
			}

			return true;
		}

		// Delete, Download, Etc.
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
	 * @param bool   $inline
	 */
	protected static function downloadExportFile( $filename, $inline ) {
		$filepath = static::getExportFolder() . $filename;
		force_download( $filepath, $inline );
	}

	/**
	 * Processes export format requests, queues background jobs, and schedules them.
	 * This method is designed to be reusable by different AJAX handlers or processes.
	 *
	 * @param array $export_formats_input Associative array of export formats to process, e.g., ['pdf' => 'pdf', 'epub' => 'epub'].
	 * @param array $export_options_input Associative array of export options.
	 * @return array An array of results, with each item detailing the outcome for a format.
	 */
	public static function processAndQueueJobRequests(array $export_formats_input, array $export_options_input): array {
		// Ensure the export jobs table exists before proceeding
		BackgroundJob::ensureExportsTable();

		// Permission check should be done by the calling AJAX handler if it's user-initiated.
		// Nonce checks should also be done by the calling AJAX handler.

		$results = [];
		$background_pdf_types = self::getBackgroundExportTypes(); // Class names of background types
		$available_modules = self::getAvailableExportModules();    // [slug => classname]

		// Clear previous static errors/outputs for this new request context if they were used by other parts.
		// However, this method should be self-contained and not rely on static::$exportConversionError etc.
		// For now, let's assume this method doesn't interact with those static properties directly.
		// static::$currentExportModuleArgs = $export_options_input; // This was how ajax_submit_export_job set it.

		// Switch to the correct locale for export related operations if needed.
		// This was done in ajax_submit_export_job. If it affects getFriendlyNameForModule or other helpers, keep it.
		$locale_switched = switch_to_locale( self::locale() );

		foreach ( array_keys( $export_formats_input ) as $format_slug_key ) {
			$format_slug = sanitize_text_field( $format_slug_key ); // Sanitize the key itself if it comes from user input.
			$module_classname = $available_modules[ $format_slug ] ?? null;

			if ( ! $module_classname || ! class_exists( $module_classname ) ) {
				$results[] = [
					'event_type' => 'job_queue_failed', // Consistent event_type for easier parsing
					'message' => sprintf( __( 'Invalid or unsupported export format: %s', 'pressbooks' ), esc_html( $format_slug ) ),
					'module_slug' => $format_slug,
					'status' => 'error',
				];
				continue;
			}

			// Only proceed if this module type is meant for background processing.
			// Other types might be handled synchronously by other mechanisms (not covered by this method).
			if ( in_array( $module_classname, $background_pdf_types, true ) ) {
				$constructor_args = self::getConstructorArgsForModule( $module_classname, $export_options_input );

				$book_id = get_current_blog_id();
				$user_id = get_current_user_id();

				global $wpdb;
				$table_name = $wpdb->prefix . 'pressbooks_export_jobs';

				$insert_data = [
					'book_id' => $book_id,
					'user_id' => $user_id,
					'export_format' => $format_slug,
					'export_module_classname' => $module_classname,
					'export_options' => wp_json_encode( $constructor_args ),
					'status' => 'pending',
					'created_at' => current_time( 'mysql', true ),
					'updated_at' => current_time( 'mysql', true ),
				];
				$insert_result = $wpdb->insert( $table_name, $insert_data );
				$job_id = $wpdb->insert_id;

				$friendly_name = self::getFriendlyNameForModule( $module_classname );

				if ( $insert_result && $job_id ) {
					wp_schedule_single_event( time(), 'pressbooks_process_export_job', [ 'job_id' => $job_id ] );
					$results[] = [
						'event_type' => 'job_queued',
						'book_id' => $book_id,
						'job_id' => $job_id,
						'message' => sprintf( __( '%s export has been queued (Job ID: %d).', 'pressbooks' ), $friendly_name, $job_id ),
						'module_slug' => $format_slug,
						'module_classname' => $module_classname,
						'format_name' => $friendly_name,
						'status' => 'success',
						// 'sse_nonce' => wp_create_nonce( 'pressbooks_export_status_' . $job_id ), // Nonce generation might be handler-specific
					];
				} else {
					$results[] = [
						'event_type' => 'job_queue_failed',
						'message' => sprintf( __( 'Failed to queue %s export. Database error: %s', 'pressbooks' ), $friendly_name, esc_html($wpdb->last_error) ),
						'module_slug' => $format_slug,
						'module_classname' => $module_classname,
						'format_name' => $friendly_name,
						'status' => 'error',
						'error_details' => $wpdb->last_error,
					];
				}
			} else {
				// This format type is not designated for background processing via this mechanism.
				$results[] = [
					'event_type' => 'job_type_skipped',
					'message' => sprintf( __( '%s format is not processed as a background job by this system.', 'pressbooks' ), self::getFriendlyNameForModule( $module_classname ) ),
					'module_slug' => $format_slug,
					'module_classname' => $module_classname,
					'format_name' => self::getFriendlyNameForModule( $module_classname ),
					'status' => 'skipped',
				];
			}
		}

		if ( $locale_switched ) {
			restore_previous_locale();
		}

		return $results;
	}

	/**
	 * Gets the constructor arguments for a given export module.
	 * This is a placeholder and might need more sophisticated logic based on your actual needs.
	 *
	 * @param string $module_classname
	 * @param array $global_export_options General options passed in the AJAX request.
	 * @return array
	 */
	protected static function getConstructorArgsForModule(string $module_classname, array $global_export_options = []) {
		// Basic implementation: return global options. Could be customized per module.
		// For example, if certain options are only relevant to PDF, or if specific $_POST fields need to be mapped.
		// This was previously handled somewhat in exportGenerator with $constructor_args = static::$currentExportModuleArgs[0] ?? [];
		// Now static::$currentExportModuleArgs is just $_POST['export_options']
		return $global_export_options; // Or process/filter as needed
	}

	/**
	 * Helper to get a map of all available export format slugs to their classnames.
	 * This might involve calling parts of your existing `modules()` or `get_export_formats_map()` logic.
	 * For now, a simplified version based on what `getFriendlyNameForModule` might imply.
	 * You should adapt this to accurately reflect your system's module registration.
	 *
	 * @return array [slug => classname, ...]
	 */
	protected static function getAvailableExportModules(): array
	{
		// Map of export format slugs to their class names - only include formats that are in the form
		$slug_to_classname = [
			'pdf' => '\\Pressbooks\\Modules\\Export\\Prince\\Pdf',
			'print_pdf' => '\\Pressbooks\\Modules\\Export\\Prince\\PrintPdf',
			'epub' => '\\Pressbooks\\Modules\\Export\\Epub\\Epub',
			'xhtml' => '\\Pressbooks\\Modules\\Export\\Xhtml\\Xhtml11',
			'wxr' => '\\Pressbooks\\Modules\\Export\\WordPress\\Wxr',
			'vanillawxr' => '\\Pressbooks\\Modules\\Export\\WordPress\\VanillaWxr',
		];

		// Allow plugins to add their own export formats
		return apply_filters('pb_available_export_module_slug_to_classname_map', $slug_to_classname);
	}

	/**
	 * AJAX handler for submitting export jobs.
	 * This method now uses the centralized processAndQueueJobRequests method.
	 */
	public static function ajax_submit_export_job() {
		// Nonce check specific to this AJAX action
		check_ajax_referer( 'pb-export-book', 'pb_export_nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'pressbooks' ) ], 403 );
			return; // Important to return/exit after wp_send_json_error
		}

		// Sanitize and prepare inputs from $_POST
		$export_formats_from_post = isset( $_POST['export_formats'] ) && is_array( $_POST['export_formats'] ) ? $_POST['export_formats'] : [];
		// Sanitize keys of export_formats_from_post as they are used in loops
		$sanitized_export_formats = [];
		foreach ($export_formats_from_post as $key => $value) {
		    // Assuming $key is the format slug and $value might be '1' or the slug itself if it's checkbox-like.
		    // The crucial part is the key.
		    $sanitized_export_formats[sanitize_text_field($key)] = sanitize_text_field($value);
		}

		if ( empty( $sanitized_export_formats ) ) {
			wp_send_json_error( [ 'message' => __( 'No export formats selected.', 'pressbooks' ) ], 400 );
			return;
		}

		$export_options_from_post = isset( $_POST['export_options'] ) && is_array( $_POST['export_options'] ) ? $_POST['export_options'] : [];
		// Potentially sanitize $export_options_from_post if necessary, depending on its structure and usage.

		// Call the centralized processing method
		$results = self::processAndQueueJobRequests( $sanitized_export_formats, $export_options_from_post );

		// Check if any jobs were actually queued successfully based on the 'status' field in results
		$has_successful_queues = false;
		$successfully_queued_count = 0;
		foreach ( $results as $result ) {
			if ( isset( $result['status'] ) && $result['status'] === 'success' && $result['event_type'] === 'job_queued' ) {
				$has_successful_queues = true;
				$successfully_queued_count++;
			}
		}

		if ( $has_successful_queues ) {
			wp_send_json_success( [
				'message' => sprintf( _n( '%d export job queued.', '%d export jobs processed.', $successfully_queued_count, 'pressbooks' ), $successfully_queued_count ),
				'results' => $results,
				'reload_on_complete' => true, // This flag seems to be part of the original design
				'total_jobs' => $successfully_queued_count,
			] );
		} else {
			// If all failed or were skipped
			$error_message = __( 'No export jobs were successfully queued.', 'pressbooks' );
			$specific_errors = [];
			foreach ( $results as $result ) {
				if ( isset( $result['status'] ) && $result['status'] === 'error' && isset($result['message']) ) {
					$specific_errors[] = $result['message'];
				}
			}
			if ( ! empty( $specific_errors ) ) {
				$error_message .= ' ' . __( 'Details:', 'pressbooks' ) . ' ' . implode( '; ', $specific_errors );
			}
			wp_send_json_error( [
				'message' => $error_message,
				'results' => $results,
				'reload_on_complete' => false,
			], 400 );
		}
		// Ensure exit after sending JSON response.
		exit;
	}
}
