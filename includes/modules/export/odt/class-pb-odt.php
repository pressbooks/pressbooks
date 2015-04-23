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
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			if ( ! defined( 'PB_SAXON_COMMAND' ) )
			define( 'PB_SAXON_COMMAND', 'java -jar ' . PB_PLUGIN_DIR . 'symbionts/saxonhe/saxon9he.jar' );

		} else {
			if ( ! defined( 'PB_SAXON_COMMAND' ) )
			define( 'PB_SAXON_COMMAND', '/usr/bin/java -jar ' . PB_PLUGIN_DIR . 'symbionts/saxonhe/saxon9he.jar' );
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
		
		$contentPath = pathinfo($filename);
		$source = $contentPath['dirname'] . '/source.xhtml';
		file_put_contents( $source, $this->queryXhtml() );
		$xslt = PB_PLUGIN_DIR . 'includes/modules/export/odt/xhtml2odt.xsl';
		$content = $contentPath['dirname'] . '/content.xml';
		$mimeType = $contentPath['dirname'] . "/mimetype";
		$metaFolder = $contentPath['dirname'] . "/META-INF";
		$meta = $contentPath['dirname'] ."/meta.xml";
		$settings = $contentPath['dirname'] . "/settings.xml";
		$styles = $contentPath['dirname'] . "/styles.xml";
		$graphicFolder = $contentPath['dirname'] . '/media/';
		
		/*get all images and save it to the link folder to zip*/
		$urlContent = file_get_contents($source);
		
		$urlContent = preg_replace("/xmlns\=\"http\:\/\/www\.w3\.org\/1999\/xhtml\"/i", '', $urlContent);
		
		$doc = new \DOMDocument();
		$doc->loadXML($urlContent);
		$xpath = new \DOMXPath($doc);
		
		$tables = $xpath->query('//table');
		//to get column count of a table
		foreach ($tables as $table){	
			$colCount = 0;
			$columns = $xpath->query('//tr[1]/*', $table);
			foreach ($columns as $column){
				if ($column->hasAttribute('colspan')){
					$colCount = $colCount + $column->getAttribute('colspan');
				}else{
					$colCount++;
				}
			}
			$table->setAttribute('colcount', $colCount);
		}
		
		file_put_contents( $source, $doc->saveXML() );
		
		$images = $xpath->query('//img');
		
		$coverImages = $xpath->query('//meta[@name="pb-cover-image"]');
		if (($images->length > 0) || ($coverImages->length > 0)){
			//create a folder named as 'media'
			$graphicPath = $contentPath['dirname'] . '/media/';
			mkdir($graphicPath);
		}
		foreach ($images as $image){
			//copy all images to the media folder
			$src = $image->getAttribute('src');
			$realPath = realpath($src);
			if ($realPath){
				$imgFileName = $graphicPath . basename($realPath);
				if (! copy($realPath, $imgFileName)){
					$this->logError( 'cannot copy image file ' .  basename($realPath));
					return false;
				}
			}else{
				$imgContent = file_get_contents($src);
				if ((!$imgContent) || ($imgContent == "")){
					$this->logError( 'cannot copy image file ' .  basename($src));
					return false;
				}
				$imgFileName = $graphicPath . basename($src);
				file_put_contents($imgFileName, $imgContent);
			}
		}
		
		foreach ($coverImages as $coverImage){
			//copy cover image to the media folder
			$src = $coverImage->getAttribute('content');
			$realPath = realpath($src);
			if ($realPath){
				$imgFileName = $graphicPath . basename($realPath);
				if (! copy($realPath, $imgFileName)){
					$this->logError( 'cannot copy image file ' .  basename($realPath));
					return false;
				}
			}else{
				$imgContent = file_get_contents($src);
				if ((!$imgContent) || ($imgContent == "")){
					$this->logError( 'cannot copy image file ' .  basename($src));
					return false;
				}
				$imgFileName = $graphicPath . basename($src);
				file_put_contents($imgFileName, $imgContent);
			}
		}
		
		try {
			$result = exec( PB_SAXON_COMMAND . ' -xsl:' . $xslt .' -s:' . $source .' -o:' . $content);
		} catch ( \Exception $e ) {
			$this->logError( $e->getMessage() );
			return false;
		}

		if ((!file_exists($content)) ||  (!file_exists($mimeType)) || (!file_exists($meta)) || (!file_exists($settings)) || (!file_exists($styles)) || (!file_exists($metaFolder))){
			$this->logError( 'Transformation failed' );
			return false;
		}
		
		$zip = new \PclZip( $filename );
		
		if ($images->length > 0){
			$list = $zip->add($mimeType . ',' . $content . ',' . $meta . ',' . $settings . ',' . $styles . ',' . $graphicFolder . ',' . $metaFolder, PCLZIP_OPT_NO_COMPRESSION, PCLZIP_OPT_REMOVE_PATH, $contentPath['dirname'] . '/' );
		}else{
			$list = $zip->add($mimeType . ',' . $content . ',' . $meta . ',' . $settings . ',' . $styles . ',' . $metaFolder, PCLZIP_OPT_NO_COMPRESSION, PCLZIP_OPT_REMOVE_PATH, $contentPath['dirname'] . '/' );
		}
		
		if ( $list == 0 ) {
			$this->logError( $zip->errorInfo(true) );
			//die( "Error : " . $zip->errorInfo(true) );
			return false;
		}
		unlink( $source );
		unlink( $mimeType );
		unlink( $content );
		unlink( $meta );
		unlink( $settings );
		unlink( $styles );
		unlink( $metaFolder . '/manifest.xml' );
		rmdir( $metaFolder);
		if ($images->length > 0){
			$this -> deleteDir( $graphicPath );
		}
		
		return true;
	}

	/*Recursive Directory Deletion for media folder */
	public static function deleteDir($dirPath) {
			if (! is_dir($dirPath)) {
				throw new InvalidArgumentException("$dirPath must be a directory");
		}
		if (substr($dirPath, strlen($dirPath) - 1, 1) != '/') {
			$dirPath .= '/';
		}
		$files = glob($dirPath . '*', GLOB_MARK);
		foreach ($files as $file) {
			if (is_dir($file)) {
				self::deleteDir($file);
			} else {
				unlink($file);
			}
		}
		rmdir($dirPath);
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