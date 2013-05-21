<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version))
 */
namespace PressBooks\Export\Prince;


use \PressBooks\Export\Export;

class Pdf extends Export {

	/**
	 * Service URL
	 *
	 * @var string
	 */
	public $url;


	/**
	 * Fullpath to log file used by Prince.
	 *
	 * @var string
	 */
	public $logfile;


	/**
	 * Fullpath to book CSS file.
	 *
	 * @var string
	 */
	protected $exportStylePath;


	/**
	 * Fullpath to book JavaScript file.
	 *
	 * @var string
	 */
	protected $exportScriptPath;


	/**
	 * CSS overrides
	 *
	 * @var string
	 */
	protected $cssOverrides;


	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults

		if ( ! defined( 'PB_PRINCE_COMMAND' ) )
			define( 'PB_PRINCE_COMMAND', '/usr/bin/prince' );

		$this->exportStylePath = $this->getExportStylePath( 'prince' );
		$this->exportScriptPath = $this->getExportScriptPath( 'prince' );

		// Set the access protected "format/xhtml" URL with a valid timestamp and NONCE
		$timestamp = time();
		$md5 = $this->nonce( $timestamp );
		$this->url = home_url() . "/format/xhtml?timestamp={$timestamp}&hashkey={$md5}";

		$this->themeOptionsOverrides();
	}


	/**
	 * Create $this->outputPath
	 *
	 * @return bool
	 */
	function convert() {

		// Sanity check

		if ( empty( $this->exportStylePath ) || ! is_file( $this->exportStylePath ) ) {
			$this->logError( '$this->exportStylePath must be set before calling convert().' );

			return false;
		}

		// Convert

		require_once( PB_PLUGIN_DIR . 'symbionts/prince/prince.php' );

		// Set logfile
		$this->logfile = $this->createTmpFile();

		// Set filename
		$filename = $this->timestampedFileName( '.pdf' );
		$this->outputPath = $filename;

		// CSS Overrides
		$css_file = $this->createTmpFile();
		file_put_contents( $css_file, $this->cssOverrides );

		// Save PDF as file in exports folder
		$prince = new \Prince( PB_PRINCE_COMMAND );
		$prince->setHTML( true );
		$prince->setCompress( true );
		$prince->addStyleSheet( $this->exportStylePath );
		$prince->addStylesheet( $css_file );
		$prince->addScript( $this->exportScriptPath );
		$prince->setLog( $this->logfile );
		$retval = $prince->convert_file_to_file( $this->url, $this->outputPath, $msg );

		// Prince XML is very flexible. There could be errors but Prince will still render a PDF.
		// We want to log those errors but we won't alert the user.
		if ( count( $msg ) ) {
			$this->logError( file_get_contents( $this->logfile ) );
		}

		return $retval;
	}


	/**
	 * Check the sanity of $this->outputPath
	 *
	 * @return bool
	 */
	function validate() {

		// Is this a PDF?
		if ( ! $this->isPdf( $this->outputPath ) ) {

			$this->logError( file_get_contents( $this->logfile ) );

			return false;
		}

		return true;
	}


	/**
	 * Add $this->url as additional log info, fallback to parent.
	 *
	 * @param $message
	 */
	function logError( $message ) {

		$more_info = array(
			'url' => $this->url,
		);

		parent::logError( $message, $more_info );
	}


	/**
	 * Verify if body is actual PDF
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	protected function isPdf( $file ) {

		$mime = $this->mimeType( $file );

		return ( strpos( $mime, 'application/pdf' ) !== false );
	}


	/**
	 * Override based on Theme Options
	 */
	protected function themeOptionsOverrides() {

		// --------------------------------------------------------------------
		// CSS

		$css = '';
		$css = apply_filters( 'pb_pdf_css_override', $css ) . "\n";

		// Copyright
		// Please be kind, help PressBooks grow by leaving this on!
		if ( empty( $GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES'] ) ) {
			$freebie_notice = 'This book was produced using PressBooks.com, and PDF rendering was done by PrinceXML.';
			$css .= '#copyright-page .ugc > p:last-of-type::after { display:block; margin-top: 1em; content: "' . $freebie_notice . '" }' . "\n";
		}

		$this->cssOverrides = $css;


		// --------------------------------------------------------------------
		// Hacks

		$hacks = array();
		$hacks = apply_filters( 'pb_pdf_hacks', $hacks );

		// Append endnotes to URL?
		if ( 2 == @$hacks['pdf_footnotes_style'] ) {
			$this->url .= '&endnotes=true';
		}

	}


}