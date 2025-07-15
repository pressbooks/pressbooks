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
use function Pressbooks\Media\mime_type;
use function Pressbooks\Redirect\force_download;
use function Pressbooks\Sanitize\sanitize_xml_id;
use function Pressbooks\Utility\create_tmp_file;
use function Pressbooks\Utility\email_error_log;
use function Pressbooks\Utility\get_media_prefix;
use function Pressbooks\Utility\put_contents;
use function Pressbooks\Utility\template;
use function \Pressbooks\Utility\scandir_by_date;
use Generator;
use Pressbooks\Book;
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

	public static bool $switchedLocale;

	public static array $exportConversionError = [];

	public static array $exportValidationWarning = [];

	public static array $exportOutputs = [];

	/**
	 * Email addresses to send log errors.
	 *
	 * @var array
	 */
	public array $errorsEmail = [];

	/**
	 * Reserved HTML IDs.
	 *
	 * @var array
	 */
	protected array $reservedIds = [
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
	protected string $outputPath = '';

	/**
	 * Return $this->outputPath
	 *
	 * @return string
	 */
	function getOutputPath(): string {

		return $this->outputPath;
	}

	/**
	 * Return the fullpath to an export module's style file.
	 *
	 * @param string $type
	 *
	 * @return string
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	function getExportStylePath( string $type ): false|string {

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
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	function getLatestExportStylePath( string $type ): false|string {
		// This method only supports Prince stylesheets at the moment.
		if ( $type === 'prince' ) {
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
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	function getLatestExportStyleUrl( string $type ): false|string {
		// This method only supports Prince stylesheets at the moment.
		if ( $type === 'prince' ) {
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
	 * @param int $max
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	function truncateExportStylesheets( $type, $max = 1 ): void {
		// This method only supports Prince stylesheets at the moment.
		if ( $type === 'prince' ) {
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
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	function getExportScriptPath( $type ): false|string {

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
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	function getExportScriptUrl( $type ): false|string {

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
	public static function shouldParseSubsections(): bool {

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
	public function logError( string $message, array $more_info = [] ): void {

		/**
		 * $var \WP_User $current_user
		 */
		global $current_user;

		$current_user = wp_get_current_user();

		$subject = get_friendly_name_for_module( get_class( $this ) ) . ' ' . __( 'Export Error', 'pressbooks' );

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
	function createTmpFile(): string {

		return create_tmp_file();
	}

	/**
	 * Create a timestamped filename.
	 *
	 * @param string $extension
	 * @param bool $fullpath
	 *
	 * @return string
	 */
	public function timestampedFileName( string $extension, bool $fullpath = true ): string {
		$book_title = ( get_bloginfo( 'name' ) ) ? get_bloginfo( 'name' ) : __( 'book', 'pressbooks' );
		$book_title_slug = sanitize_file_name( $book_title );
		$book_title_slug = str_replace( [ '+' ], '', $book_title_slug ); // Remove symbols which confuse Apache (Ie. form urlencoded spaces)
		$book_title_slug = sanitize_file_name( $book_title_slug ); // str_replace() may inadvertently create a new bad filename, sanitize again for good measure.

		$path = $fullpath ? static::getExportFolder() : '';

		return $path . $book_title_slug . '-' . time() . '.' . ltrim( $extension, '.' );
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
	function nonce( $timestamp ): string {
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
	function verifyNonce( $timestamp, $md5 ): bool {

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
	 * Check a post_name against a list of reserved IDs, sanitize for use as an XML ID.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function preProcessPostName( $id ): string {

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
	protected function createTmpDir(): string {

		$temp_file = tempnam( sys_get_temp_dir(), '' );
		@unlink( $temp_file ); // @codingStandardsIgnoreLine
		mkdir( $temp_file );
		if ( ! is_dir( $temp_file ) ) {
			return '';

		}

		return untrailingslashit( $temp_file );
	}

	/**
	 * Will create an HTML blob of copyright, returns empty string if something goes wrong
	 *
	 * @param array  $metadata
	 * @param string $title          (optional)
	 * @param int    $id             (optional)
	 * @param string $section_author (deprecated)
	 *
	 * @return string $html blob
	 */
	protected function doCopyrightLicense( $metadata, $title = '', $id = 0, $section_author = '' ): string {

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
	protected function doTocLicense( $post_id ): string {
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
	protected function doSectionLevelLicense( $metadata, $post_id ): string {
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
	protected function loadTemplate( $path, array $vars = [] ): string {
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
	public static function mimeType( $file ): string {
		return mime_type( $file );
	}

	/**
	 * Get the fullpath to the Exports folder.
	 * Create if not there. Create .htaccess protection if missing.
	 *
	 * @return string fullpath
	 */
	public static function getExportFolder(): string {

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
		return apply_filters( 'pb_get_export_folder', $path );
	}

	/**
	 * Pre-Export
	 */
	public static function preExport(): void {
		/**
		 * Let other plugins tweak things before exporting for instance H5P export, MathJax, etc.
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
	public static function modules(): array {

			// Default Modules

		$modules = [
			'pdf' => '\Pressbooks\Modules\Export\Prince\Pdf',
			'print_pdf' => '\Pressbooks\Modules\Export\Prince\PrintPdf',
			'epub' => '\Pressbooks\Modules\Export\Epub\Epub',
			'xhtml' => '\Pressbooks\Modules\Export\Xhtml\Xhtml11',
			'wxr' => '\Pressbooks\Modules\Export\WordPress\Wxr',
			'vanillawxr' => '\Pressbooks\Modules\Export\WordPress\VanillaWxr',
			'weblinks' => '\Pressbooks\Modules\Export\ThinCC\WebLinks',
		];

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
		return apply_filters( 'pb_active_export_modules', $modules );
	}

	/**
	 * Post Export
	 */
	public static function postExport(): void {

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
		// TODO: ⚠️ This requires a refactor to use the new BackgroundJob system those add_error() calls are not compatible with the new system.
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
	public static function locale(): string {
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
	 * Download an .htaccess protected file from the exports directory.
	 *
	 * @param string $filename sanitized $_GET['download_export_file']
	 * @param bool   $inline
	 */
	public static function downloadExportFile( $filename, $inline ): void {
		$filepath = static::getExportFolder() . $filename;
		force_download( $filepath, $inline );
	}

	/**
	 * Mandatory convert Generator method, create $this->outputPath
	 *
	 * @return Generator
	 */
	abstract function convert() : Generator;

	/**
	 * Mandatory validate Generator method, check the sanity of $this->outputPath
	 *
	 * @return Generator
	 */
	abstract function validate() : Generator;
}
