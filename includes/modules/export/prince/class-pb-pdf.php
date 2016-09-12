<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */
namespace Pressbooks\Modules\Export\Prince;


use Pressbooks\Modules\Export\Export;
use Pressbooks\Container;
use PrinceXMLPhp\PrinceWrapper;

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
		$this->pdfProfile = $this->getPdfProfile();

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
		$filename = $this->timestampedFileName( '.pdf' );
		$this->outputPath = $filename;

		// CSS File
		$css = $this->kneadCss();
		$css_file = $this->createTmpFile();
		file_put_contents( $css_file, $css );

		// Save PDF as file in exports folder
		$prince = new \PrinceXMLPhp\PrinceWrapper( PB_PRINCE_COMMAND );
		$prince->setHTML( true );
		$prince->setCompress( true );
		if ( defined( 'WP_ENV' ) && WP_ENV == 'development' ) {
			$prince->setInsecure( true );
		}
		if ( $this->pdfProfile ) {
			$prince->setOptions( "--pdf-profile='" . $this->pdfProfile . "'" );
		}
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
	 * @param array $more_info (unused, overridden)
	 */
	function logError( $message, array $more_info = array() ) {

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

		$mime = static::mimeType( $file );

		return ( strpos( $mime, 'application/pdf' ) !== false );
	}

	protected function getPdfProfile() {
		if ( defined( 'PB_PDF_PROFILE' ) ) {
			return PB_PDF_PROFILE;
		}
		return null;
	}

	/**
	 * Return kneaded CSS string
	 *
	 * @return string
	 */
	protected function kneadCss() {

		$sass = Container::get( 'Sass' );
		$scss_dir = pathinfo( $this->exportStylePath, PATHINFO_DIRNAME );

		if ( $sass->isCurrentThemeCompatible( 2 ) ) {
			// Prepend override variables (see: http://sass-lang.com/documentation/file.SASS_REFERENCE.html#variable_defaults_)
			$scss = $this->cssOverrides . "\n" . file_get_contents( $this->exportStylePath );
		} else {
			$scss = file_get_contents( $this->exportStylePath ) . "\n". $this->cssOverrides;
			// Append override rules.
		}

		if ( $sass->isCurrentThemeCompatible( 1 ) ) {
			$css = $sass->compile( $scss, [
				$sass->pathToUserGeneratedSass(),
				$sass->pathToPartials(),
				$sass->pathToFonts(),
				get_stylesheet_directory(),
			] );
		} elseif ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$css = $sass->compile( $scss, $sass->defaultIncludePaths( 'prince' ) );
		} else {
			$css = static::injectHouseStyles( $scss );
		}

		// Search for url("*"), url('*'), and url(*)
		$url_regex = '/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i';
		$css = preg_replace_callback( $url_regex, function ( $matches ) use ( $scss_dir ) {

			$url = $matches[3];

			if ( preg_match( '#^themes-book/pressbooks-book/fonts/[a-zA-Z0-9_-]+(\.woff|\.otf|\.ttf)$#i', $url ) ) {
				$my_asset = realpath( PB_PLUGIN_DIR . $url );
				if ( $my_asset ) {
					return 'url(' . PB_PLUGIN_DIR . $url . ')';
				}
			} elseif ( ! preg_match( '#^https?://#i', $url ) ) {
				$my_asset = realpath( "$scss_dir/$url" );
				if ( $my_asset ) {
					return "url($scss_dir/$url)";
				}
			}

			return $matches[0]; // No change

		}, $css );

		if ( WP_DEBUG ) {
			Container::get('Sass')->debug( $css, $scss, 'prince' );
		}

		return $css;
	}


	/**
	 * Override based on Theme Options
	 */
	protected function themeOptionsOverrides() {

		// --------------------------------------------------------------------
		// CSS

		$scss = '';
		$scss = apply_filters( 'pb_pdf_css_override', $scss ) . "\n";

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
		if ( $hacks['pdf_footnotes_style'] == 'endnotes' ) {
			$this->url .= '&endnotes=true';
		}

	}


}
