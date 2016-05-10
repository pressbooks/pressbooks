<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */
namespace Pressbooks\Modules\Export\Odt;


use \Pressbooks\Modules\Export\Export;

require_once( ABSPATH . 'wp-admin/includes/class-pclzip.php' );

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
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults
		if ( strtoupper( substr( PHP_OS, 0, 3 ) ) === 'WIN' ) {
			if ( ! defined( 'PB_SAXON_COMMAND' ) )
			define( 'PB_SAXON_COMMAND', 'java -jar ' . PB_PLUGIN_DIR . 'vendor/pressbooks/saxon-he/saxon9he.jar' );

		} else {
			if ( ! defined( 'PB_SAXON_COMMAND' ) )
			define( 'PB_SAXON_COMMAND', '/usr/bin/java -jar ' . PB_PLUGIN_DIR . 'vendor/pressbooks/saxon-he/saxon9he.jar' );
		}
		// Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
		$timestamp = time();
		$md5 = $this->nonce( $timestamp );
		$this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}";

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

		$filename = $this->timestampedFileName( '.odt' );
		$this->outputPath = $filename;

		// Save ODT as file in exports folder

		$contentPath	= pathinfo($filename);
		$source			= $contentPath['dirname'] . '/source.xhtml';

		file_put_contents( $source, $this->queryXhtml() );

		$xslt			= PB_PLUGIN_DIR . 'includes/modules/export/odt/xhtml2odt.xsl';
		$content		= $contentPath['dirname'] . '/content.xml';
		$mimetype		= $contentPath['dirname'] . "/mimetype";
		$metafolder		= $contentPath['dirname'] . "/META-INF";
		$meta 			= $contentPath['dirname'] ."/meta.xml";
		$settings 		= $contentPath['dirname'] . "/settings.xml";
		$styles 		= $contentPath['dirname'] . "/styles.xml";
		$mediafolder	= $contentPath['dirname'] . '/media/';

		if ( is_dir( $mediafolder ) ) {
			$this->deleteDirectory( $mediafolder );
		}

		$urlcontent = file_get_contents( $source );

		$urlcontent = preg_replace( "/xmlns\=\"http\:\/\/www\.w3\.org\/1999\/xhtml\"/i", '', $urlcontent );

		$old_value = libxml_disable_entity_loader( true );
		$doc = new \DOMDocument();
		$doc->loadXML( $urlcontent, LIBXML_NOBLANKS | LIBXML_NOENT | LIBXML_NONET | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING );
		libxml_disable_entity_loader( $old_value );
		$xpath = new \DOMXPath( $doc );

		$tables = $xpath->query( '//table' );

		foreach ( $tables as $table ) {
			$columncount = 0;
			$columns = $xpath->query( '//tr[1]/*', $table );
			foreach ( $columns as $column ) {
				if ( $column->hasAttribute( 'colspan' ) ) {
					$columncount = $columncount + $column->getAttribute( 'colspan' );
				} else {
					$columncount++;
				}
			}
			$table->setAttribute( 'colcount', $columncount );
		}

		if ( !file_exists( $metafolder ) ) {
			mkdir( $metafolder );
		}

		$images = $xpath->query( '//img' );
		$coverimages = $xpath->query( '//meta[@name="pb-cover-image"]' );
		if ( ( $images->length > 0 ) || ( $coverimages->length > 0 ) ) {
			if ( !file_exists( $mediafolder ) ) {
				mkdir( $mediafolder );
			}
		}

		foreach ( $images as $image ) {
			$src = $image->getAttribute('src');
 			$image_filename = $this->fetchAndSaveUniqueImage( $src, $mediafolder );
			if ( $image_filename ) {
				// Replace with new image
				$image->setAttribute( 'src', $image_filename );
			}
		}

		foreach ( $coverimages as $coverimage ) {
			$src = $coverimage->getAttribute('content');
 			$cover_filename = $this->fetchAndSaveUniqueImage( $src, $mediafolder );
			if ( $cover_filename ) {
				// Replace with new image
				$coverimage->setAttribute( 'src', $cover_filename );
			}
		}

		file_put_contents( $source, $doc->saveXML() );

		try {
			$result = exec( PB_SAXON_COMMAND . ' -xsl:' . $xslt .' -s:' . $source .' -o:' . $content );
		} catch ( \Exception $e ) {
			$this->logError( $e->getMessage() );
			unlink( $source );
			if ( is_dir( $mediafolder ) ) {
				$this->deleteDirectory( $mediafolder );
			}
			return false;
		}

		$files = [
			'content' => $content,
			'mimetype' => $mimetype,
			'meta' => $meta,
			'settings' => $settings,
			'styles' => $styles,
			'metafolder' => $metafolder
		];
		$msg = '';
		foreach ( $files as $key => $file ) {
			if ( !file_exists( $file ) ) {
				$msg .= ' [ ' . $key . ' ]';
			}
		}

		if ( !empty( $msg ) ) {
			$this->logError( 'Transformation failed, encountered a problem with' .  $msg );
			unlink( $source );
			if ( is_dir( $mediafolder ) ) {
				$this->deleteDirectory( $mediafolder );
			}
			return false;
		}

		$zip = new \PclZip( $filename );

		if ( $images->length > 0 ) {
			$list = $zip->add( $mimetype . ',' . $content . ',' . $meta . ',' . $settings . ',' . $styles . ',' . $mediafolder . ',' . $metafolder, PCLZIP_OPT_NO_COMPRESSION, PCLZIP_OPT_REMOVE_PATH, $contentPath['dirname'] . '/' );
		} else {
			$list = $zip->add( $mimetype . ',' . $content . ',' . $meta . ',' . $settings . ',' . $styles . ',' . $metafolder, PCLZIP_OPT_NO_COMPRESSION, PCLZIP_OPT_REMOVE_PATH, $contentPath['dirname'] . '/' );
		}

		if ( $list == 0 ) {
			$this->logError( $zip->errorInfo( true ) );
			unlink( $source );
			if ( is_dir( $mediafolder ) ) {
				$this->deleteDirectory( $mediafolder );
			}
			return false;
		}

		unlink( $source );
		unlink( $mimetype );
		unlink( $content );
		unlink( $meta );
		unlink( $settings );
		unlink( $styles );
		unlink( $metafolder . '/manifest.xml' );
		rmdir( $metafolder);

		if ( is_dir( $mediafolder ) ) {
			$this->deleteDirectory( $mediafolder );
		}

		return true;
	}

	/* Recursive Directory Deletion for media folder */

	public static function deleteDirectory( $dirpath ) {
		if ( !is_dir( $dirpath ) ) {
			throw new \InvalidArgumentException( "$dirpath must be a directory." );
		}
		if ( substr( $dirpath, strlen( $dirpath ) - 1, 1 ) != '/' ) {
			$dirpath .= '/';
		}
		$files = glob( $dirpath . '*', GLOB_MARK );
		foreach ( $files as $file ) {
			if ( is_dir( $file ) ) {
				self::deleteDirectory( $file );
			} else {
				unlink( $file );
			}
		}
		rmdir( $dirpath );
	}

	/**
	 * Query the access protected "format/xhtml" URL, return the results.
	 *
	 * @return bool|string
	 */
	protected function queryXhtml() {

		$args = array( 'timeout' => $this->timeout );
		$response = wp_remote_get( $this->url, $args );

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			$this->logError( $response->get_error_message() );

			return false;
		}

		// Server error?
		if ( 200 != wp_remote_retrieve_response_code( $response ) ) {
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

			$this->logError( file_get_contents( $this->logfile ) );

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
	function logError( $message, array $more_info = array() ) {

		$more_info = array(
			'url' => $this->url,
		);

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
		static $already_done = array();
		if ( isset( $already_done[$url] ) ) {
			return $already_done[$url];
		}

		$response = wp_remote_get( $url, array( 'timeout' => $this->timeout ) );

		// WordPress error?
		if ( is_wp_error( $response ) ) {
			// TODO: handle $response->get_error_message();
			$already_done[$url] = '';
			return '';
		}

		// Basename without query string
		$filename = explode( '?', basename( $url ) );

		// isolate latex image service from WP, add file extension
		if ( 's.wordpress.com' == parse_url( $url, PHP_URL_HOST ) && 'latex.php' == $filename[0] ) {
			$filename = md5( array_pop( $filename ) );
			// content-type = 'image/png'
			$type = explode( '/', $response['headers']['content-type'] );
			$type = array_pop( $type );
			$filename = $filename . "." . $type;
		} else {
			$filename = array_shift( $filename );
			$filename = sanitize_file_name( urldecode( $filename ) );
			$filename = \Pressbooks\Sanitize\force_ascii( $filename );
		}

		$tmp_file = \Pressbooks\Utility\create_tmp_file();
		file_put_contents( $tmp_file, wp_remote_retrieve_body( $response ) );

		if ( ! \Pressbooks\Image\is_valid_image( $tmp_file, $filename ) ) {
			$already_done[$url] = '';
			return ''; // Not an image
		}

		if ( $this->compressImages ) {
			$format = explode( '.', $filename );
			$format = strtolower( end( $format ) ); // Extension
			\Pressbooks\Image\resize_down( $format, $tmp_file );
		}

		// Check for duplicates, save accordingly
		if ( ! file_exists( "$fullpath/$filename" ) ) {
			copy( $tmp_file, "$fullpath/$filename" );
		} elseif ( md5( file_get_contents( $tmp_file ) ) != md5( file_get_contents( "$fullpath/$filename" ) ) ) {
			$filename = wp_unique_filename( $fullpath, $filename );
			copy( $tmp_file, "$fullpath/$filename" );
		}

		$already_done[$url] = $filename;
		return $filename;
	}

}
