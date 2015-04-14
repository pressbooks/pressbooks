<?php
/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */
namespace PressBooks\Export\Odt;


use \PressBooks\Export\Export;

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
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults

		if ( ! defined( 'PB_SAXON_COMMAND' ) )
			define( 'PB_SAXON_COMMAND', '/usr/bin/java -jar ' . PB_PLUGIN_DIR . '/symbionts/saxonhe/saxon9he.jar' );

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
		
		$contentPath = pathinfo($filename);
		$source = $contentPath['dirname'] . '/source.xhtml';
		file_put_contents( $source, $this->queryXhtml() );
		$xslt = PB_PLUGIN_DIR . '/includes/modules/export/odt/xhtml2odt.xsl';
		$content = $contentPath['dirname'] . '/content.xml';
		$mimeType = $contentPath['dirname'] . "/mimetype";
		$metaFolder = $contentPath['dirname'] . "/META-INF";
		$meta = $contentPath['dirname'] ."/meta.xml";
		$settings = $contentPath['dirname'] . "/settings.xml";
		$styles = $contentPath['dirname'] . "/styles.xml";
		
		$result = exec( PB_SAXON_COMMAND . ' -xsl:' . $xslt .' -s:' . $source .' -o:' . $content );
		
		unlink( $source );
		
		$zip = new \PclZip( $filename );
		
		$list = $zip->add($mimeType . ',' . $content . ',' . $meta . ',' . $settings . ',' . $styles . ',' . $metaFolder, PCLZIP_OPT_NO_COMPRESSION, PCLZIP_OPT_REMOVE_PATH, $contentPath['dirname'] . '/' );
		if ( $list == 0 ) {
			die( "Error : " . $zip->errorInfo(true) );
		}
		
		unlink( $mimeType );
		unlink( $content );
		unlink( $meta );
		unlink( $settings );
		unlink( $styles );
		unlink( $metaFolder . '/manifest.xml' );
		rmdir( $metaFolder);
		
		return true;
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

}