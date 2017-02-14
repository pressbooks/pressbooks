<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */
namespace Pressbooks\Modules\Export\Prince;


use Pressbooks\Container;

class PrintPdf extends Pdf {

	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

		// Some defaults

		if ( ! defined( 'PB_PRINCE_COMMAND' ) ) {
			define( 'PB_PRINCE_COMMAND', '/usr/bin/prince' );
		}

		$this->exportStylePath = $this->getExportStylePath( 'prince' );
		$this->exportScriptPath = $this->getExportScriptPath( 'prince' );
		$this->pdfProfile = 'PDF/X-1a';
		$this->pdfOutputIntent = '/usr/lib/prince/icc/USWebCoatedSWOP.icc';

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

		// Set logfile
		$this->logfile = $this->createTmpFile();

		// Set filename
		$filename = $this->timestampedFileName( '._print.pdf' );
		$this->outputPath = $filename;

		// Fonts
		Container::get( 'GlobalTypography' )->getFonts();

		// CSS File
		$css = $this->kneadCss();
		$css_file = $this->createTmpFile();
		file_put_contents( $css_file, $css );

		// Save PDF as file in exports folder
		$prince = new \PrinceXMLPhp\PrinceWrapper( PB_PRINCE_COMMAND );
		$prince->setHTML( true );
		$prince->setCompress( true );
		if ( defined( 'WP_ENV' ) && WP_ENV == 'development' || WP_ENV == 'staging' ) {
			$prince->setInsecure( true );
		}
		$prince->setOptions( '--pdf-profile=' . $this->pdfProfile . ' --pdf-output-intent=' . $this->pdfOutputIntent );
		$prince->addStyleSheet( $css_file );
		if ( $this->exportScriptPath ) {
			$prince->addScript( $this->exportScriptPath );
		}
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
	 * Override based on Theme Options
	 */
	protected function themeOptionsOverrides() {

		$sass = \Pressbooks\Container::get( 'Sass' );

		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$extra = "/* Print Overrides */\n\$prince-image-resolution: 300dpi; \n";
		} else {
			$extra = "/* Print Overrides */\nimg { prince-image-resolution: 300dpi; } \n";
		}

		$scss = '';
		$scss = apply_filters( 'pb_pdf_css_override', $scss ) . "\n";

		$scss = $sass->applyOverrides( $scss, $extra );

		// Copyright
		// Please be kind, help Pressbooks grow by leaving this on!
		if ( empty( $GLOBALS['PB_SECRET_SAUCE']['TURN_OFF_FREEBIE_NOTICES_PDF'] ) ) {
			$freebie_notice = __( 'This book was produced using Pressbooks.com, and PDF rendering was done by PrinceXML.', 'pressbooks' );
			$scss .= '#copyright-page .ugc > p:last-of-type::after { display:block; margin-top: 1em; content: "' . $freebie_notice . '" }' . "\n";
		}

		$this->cssOverrides = $scss;

		// --------------------------------------------------------------------
		// Hacks

		$hacks = array();
		$hacks = apply_filters( 'pb_pdf_hacks', $hacks );

		// Append endnotes to URL?
		if ( 'endnotes' == $hacks['pdf_footnotes_style'] ) {
			$this->url .= '&endnotes=true';
		}

	}
}
