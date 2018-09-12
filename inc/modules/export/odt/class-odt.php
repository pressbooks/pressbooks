<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version))
 */

namespace Pressbooks\Modules\Export\Odt;

use function Pressbooks\Utility\str_ends_with;
use Pressbooks\Modules\Export\Export;

class Odt extends Export {

	/**
	 * Timeout in seconds.
	 * Used with wp_remote_get()
	 *
	 * @var int
	 */
	public $timeout = 90;


	/**
	 * Service URL
	 *
	 * @var string
	 */
	public $url;


	/**
	 * Fullpath to log file used by Saxon.
	 *
	 * @var string
	 */
	public $logfile;


	/**
	 * Compress images?
	 *
	 * @var bool
	 */
	public $compressImages = false;

	/**
	 * Temporary directory used to build ODT, no trailing slash!
	 *
	 * @var string
	 */
	protected $tmpDir;


	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults

		if ( ! class_exists( '\PclZip' ) ) {
			require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
		}

		if ( ! defined( 'PB_SAXON_COMMAND' ) ) {
			define( 'PB_SAXON_COMMAND', '/usr/bin/java -jar /opt/saxon-he/saxon-he.jar' );
		}

		// Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
		$timestamp = time();
		$md5 = $this->nonce( $timestamp );
		$this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}";
		if ( ! empty( $_REQUEST['preview'] ) ) {
			$this->url .= '&' . http_build_query(
				[
					'preview' => $_REQUEST['preview'],
				]
			);
		}

	}

	/**
	 * Delete temporary directories when done.
	 */
	function __destruct() {
		$this->deleteTmpDir();
	}

	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {

		// Set logfile
		$this->logfile = $this->createTmpFile();

		// Set filename
		$this->outputPath = $this->timestampedFileName( '.odt' );

		// Set temp folder
		$this->tmpDir = $this->createTmpDir();

		$source = $this->tmpDir . '/source.xhtml';
		if ( defined( 'WP_TESTS_MULTISITE' ) ) {
			\Pressbooks\Utility\put_contents( $source, \Pressbooks\Utility\get_contents( $this->url ) );
		} else {
			\Pressbooks\Utility\put_contents( $source, $this->queryXhtml() );
		}

		$xslt = PB_PLUGIN_DIR . 'inc/modules/export/odt/xhtml2odt.xsl';
		$content = $this->tmpDir . '/content.xml';
		$mimetype = $this->tmpDir . '/mimetype';
		$metafolder = $this->tmpDir . '/META-INF';
		$meta = $this->tmpDir . '/meta.xml';
		$settings = $this->tmpDir . '/settings.xml';
		$styles = $this->tmpDir . '/styles.xml';
		$mediafolder = $this->tmpDir . '/media/';

		$urlcontent = \Pressbooks\Utility\get_contents( $source );
		$urlcontent = preg_replace( '/xmlns\="http\:\/\/www\.w3\.org\/1999\/xhtml"/i', '', $urlcontent );

		if ( empty( $urlcontent ) ) {
			$this->logError( 'source.xhtml is empty' );
			return false;
		}

		libxml_use_internal_errors( true );
		$old_value = libxml_disable_entity_loader( true );
		$doc = new \DOMDocument();
		$doc->recover = true; // Try to parse non-well formed documents
		$doc->loadXML( $urlcontent, LIBXML_NOBLANKS | LIBXML_NOENT | LIBXML_NONET | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_disable_entity_loader( $old_value );
		$xpath = new \DOMXPath( $doc );

		$tables = $xpath->query( '//table' );

		foreach ( $tables as $table ) {
			/** @var \DOMElement $table */
			$columncount = 0;
			$columns = $xpath->query( '//tr[1]/*', $table );
			foreach ( $columns as $column ) {
				/** @var \DOMElement $column */
				if ( $column->hasAttribute( 'colspan' ) ) {
					$columncount = $columncount + (int) $column->getAttribute( 'colspan' );
				} else {
					$columncount++;
				}
			}
			$table->setAttribute( 'colcount', $columncount );
		}

		if ( ! file_exists( $metafolder ) ) {
			mkdir( $metafolder );
		}

		$images = $xpath->query( '//img' );
		$coverimages = $xpath->query( '//meta[@name="pb-cover-image"]' );
		if ( ( $images->length > 0 ) || ( $coverimages->length > 0 ) ) {
			if ( ! file_exists( $mediafolder ) ) {
				mkdir( $mediafolder );
			}
		}
		foreach ( $images as $image ) {
			/** @var \DOMElement $image */
			$src = $image->getAttribute( 'src' );
			$image_filename = $this->fetchAndSaveUniqueImage( $src, $mediafolder );
			if ( $image_filename ) {
				// Replace with new image
				$image->setAttribute( 'src', $image_filename );
			}
		}

		foreach ( $coverimages as $coverimage ) {
			/** @var \DOMElement $coverimage */
			$src = $coverimage->getAttribute( 'content' );
			$cover_filename = $this->fetchAndSaveUniqueImage( $src, $mediafolder );
			if ( $cover_filename ) {
				// Replace with new image
				$coverimage->setAttribute( 'src', $cover_filename );
			}
		}

		\Pressbooks\Utility\put_contents( $source, $doc->saveXML() );

		$errors = libxml_get_errors(); // TODO: Handle errors gracefully
		libxml_clear_errors();

		$output = [];
		$command = PB_SAXON_COMMAND . ' -xsl:' . escapeshellcmd( $xslt ) . ' -s:' . escapeshellcmd( $source ) . ' -o:' . escapeshellcmd( $content ) . ' 2>&1';
		exec( $command, $output );

		$files = [
			'content' => $content,
			'mimetype' => $mimetype,
			'meta' => $meta,
			'settings' => $settings,
			'styles' => $styles,
			'metafolder' => $metafolder,
		];
		$msg = '';
		foreach ( $files as $key => $file ) {
			if ( ! file_exists( $file ) ) {
				$msg .= ' [ ' . $key . ' ]';
			}
		}

		if ( ! empty( $msg ) ) {
			$this->logError( "Transformation failed, encountered a problem with $msg \n\n" . implode( "\n", $output ) );
			return false;
		}

		$zip = new \PclZip( $this->outputPath );

		if ( $images->length > 0 ) {
			$list = $zip->add( $mimetype . ',' . $content . ',' . $meta . ',' . $settings . ',' . $styles . ',' . $mediafolder . ',' . $metafolder, PCLZIP_OPT_NO_COMPRESSION, PCLZIP_OPT_REMOVE_PATH, $this->tmpDir . '/' );
		} else {
			$list = $zip->add( $mimetype . ',' . $content . ',' . $meta . ',' . $settings . ',' . $styles . ',' . $metafolder, PCLZIP_OPT_NO_COMPRESSION, PCLZIP_OPT_REMOVE_PATH, $this->tmpDir . '/' );
		}

		if ( 0 === absint( $list ) ) {
			$this->logError( $zip->errorInfo( true ) );
			return false;
		}

		return true;
	}

	/**
	 * Query the access protected "format/xhtml" URL, return the results.
	 *
	 * @return bool|string
	 */
	protected function queryXhtml() {

		$args = [
			'timeout' => $this->timeout,
		];
		$response = wp_remote_get( $this->url, $args );

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			$this->logError( $response->get_error_message() );

			return false;
		}

		// Server error?
		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			$this->logError( wp_remote_retrieve_response_message( $response ) );

			return false;
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	function validate() {

		// Is this an ODT?
		if ( ! $this->isOdt( $this->outputPath ) ) {

			$this->logError( \Pressbooks\Utility\get_contents( $this->logfile ) );

			return false;
		}

		return true;
	}


	/**
	 * Add $this->url as additional log info, fallback to parent.
	 *
	 * @param $message
	 * @param array $more_info (unused, overridden)
	 */
	function logError( $message, array $more_info = [] ) {

		$more_info = [
			'url' => $this->url,
		];

		parent::logError( $message, $more_info );
	}


	/**
	 * Verify if body is actual ODT
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	protected function isOdt( $file ) {

		$mime = static::mimeType( $file );

		return ( strpos( $mime, 'application/vnd.oasis.opendocument.text' ) !== false );
	}

	/**
	 * Fetch an image with wp_remote_get(), save it to $fullpath with a unique name.
	 * Will return an empty string if something went wrong.
	 *
	 * @param $url string
	 * @param $fullpath string
	 *
	 * @return string filename
	 */
	protected function fetchAndSaveUniqueImage( $url, $fullpath ) {

		// Cheap cache
		static $already_done = [];
		if ( isset( $already_done[ $url ] ) ) {
			return $already_done[ $url ];
		}

		$response = wp_remote_get(
			$url, [
				'timeout' => $this->timeout,
			]
		);

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			// TODO: Better handling in the event of $response->get_error_message();
			$already_done[ $url ] = '';
			return '';
		}

		// Basename without query string
		$filename = explode( '?', basename( $url ) );

		// isolate latex image service from WP, add file extension
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ( str_ends_with( $host, 'wordpress.com' ) || str_ends_with( $host, 'wp.com' ) ) && 'latex.php' === $filename[0] ) {
			$filename = md5( array_pop( $filename ) );
			// content-type = 'image/png'
			$type = explode( '/', $response['headers']['content-type'] );
			$type = array_pop( $type );
			$filename = $filename . '.' . $type;
		} else {
			$filename = array_shift( $filename );
			$filename = explode( '#', $filename )[0]; // Remove trailing anchors
			$filename = sanitize_file_name( urldecode( $filename ) );
			$filename = \Pressbooks\Sanitize\force_ascii( $filename );
		}

		// A book with a lot of images can trigger "Fatal Error Too many open files" because tmpfiles are not closed until PHP exits
		// Use a $resource_key so we can close the tmpfile ourselves
		$resource_key = uniqid( 'tmpfile-odt-', true );
		$tmp_file = \Pressbooks\Utility\create_tmp_file( $resource_key );
		\Pressbooks\Utility\put_contents( $tmp_file, wp_remote_retrieve_body( $response ) );

		if ( ! \Pressbooks\Image\is_valid_image( $tmp_file, $filename ) ) {
			$already_done[ $url ] = '';
			fclose( $GLOBALS[ $resource_key ] ); // @codingStandardsIgnoreLine
			return ''; // Not an image
		}

		if ( $this->compressImages ) {
			$format = explode( '.', $filename );
			$format = strtolower( end( $format ) ); // Extension
			try {
				\Pressbooks\Image\resize_down( $format, $tmp_file );
			} catch ( \Exception $e ) {
				return '';
			}
		}

		// Check for duplicates, save accordingly
		if ( ! file_exists( "$fullpath/$filename" ) ) {
			copy( $tmp_file, "$fullpath/$filename" );
		} elseif ( md5( \Pressbooks\Utility\get_contents( $tmp_file ) ) !== md5( \Pressbooks\Utility\get_contents( "$fullpath/$filename" ) ) ) {
			$filename = wp_unique_filename( $fullpath, $filename );
			copy( $tmp_file, "$fullpath/$filename" );
		}
		fclose( $GLOBALS[ $resource_key ] ); // @codingStandardsIgnoreLine

		$already_done[ $url ] = $filename;
		return $filename;
	}

	/**
	 * Delete temporary directories
	 */
	protected function deleteTmpDir() {
		// Cleanup temporary directory, if any
		if ( ! empty( $this->tmpDir ) ) {
			\Pressbooks\Utility\rmrdir( $this->tmpDir );
		}
		// Cleanup deprecated junk, if any
		$exports_folder = untrailingslashit( pathinfo( $this->outputPath, PATHINFO_DIRNAME ) );
		if ( ! empty( $exports_folder ) ) {
			\Pressbooks\Utility\rmrdir( "{$exports_folder}/META-INF" );
			\Pressbooks\Utility\rmrdir( "{$exports_folder}/media" );
		}
	}

	/**
	 * Dependency check.
	 *
	 * @return bool
	 */
	static function hasDependencies() {
		if ( false !== \Pressbooks\Utility\check_saxonhe_install() ) {
			return true;
		}

		return false;
	}

}
