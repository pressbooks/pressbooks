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
	 * Fullpath to CSS stylesheet used by Prince.
	 *
	 * @var string
	 */
	public $style;


	/**
	 * Fullpath to JavaScript used by Prince.
	 *
	 * @var string
	 */
	public $script;

	/**
	 * Fullpath to log file used by Prince.
	 *
	 * @var string
	 */
	public $logfile;


	/**
	 * Path to book export theme.
	 *
	 * @var string
	 */
	protected $exportStylePath;


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
		$this->style = $this->exportStylePath . '/style.css';
		$this->script = $this->exportStylePath . '/script.js';

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

		if ( empty( $this->exportStylePath ) || ! is_dir( $this->exportStylePath ) ) {
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
		$prince->addStyleSheet( $this->style );
		$prince->addStylesheet( $css_file );
		$prince->addScript( $this->script );
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
		$freebie_notice = 'This book was produced using PressBooks.com, and PDF rendering was done by PrinceXML.';
		$css .= '#copyright-page .ugc > p:last-of-type::after { display:block; margin-top: 1em; content: "' . $freebie_notice . '" }';

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