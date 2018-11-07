<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version))
 */

namespace Pressbooks\Modules\Export\Prince;

use function Pressbooks\Sanitize\normalize_css_urls;
use Pressbooks\Container;
use Pressbooks\Modules\Export\Export;

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
	 * @var string
	 */
	protected $pdfProfile;

	/**
	 * @var string
	 */
	protected $pdfOutputIntent;


	/**
	 * @param array $args
	 */
	function __construct( array $args ) {

		if ( ! defined( 'PB_PRINCE_COMMAND' ) ) {
			define( 'PB_PRINCE_COMMAND', '/usr/bin/prince' );
		}

		$this->exportStylePath = $this->getExportStylePath( 'prince' );
		$this->exportScriptPath = $this->getExportScriptPath( 'prince' );
		$this->pdfProfile = $this->getPdfProfile();
		$this->pdfOutputIntent = $this->getPdfOutputIntent();

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

		$this->themeOptionsOverrides();
		$this->fixLatexDpi();
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
		$filename = $this->generateFileName();
		$this->outputPath = $filename;

		// Fonts
		Container::get( 'GlobalTypography' )->getFonts();

		// CSS
		$this->truncateExportStylesheets( 'prince' );
		$timestamp = time();
		$css = $this->kneadCss();
		$css_file = \Pressbooks\Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp.css";
		\Pressbooks\Utility\put_contents( $css_file, $css );

		// --------------------------------------------------------------------
		// Save PDF as file in exports folder

		$prince = new \PrinceXMLPhp\PrinceWrapper( PB_PRINCE_COMMAND );
		$prince->setHTML( true );
		$prince->setCompress( true );
		$prince->setHttpTimeout( max( ini_get( 'max_execution_time' ), 30 ) );
		if ( defined( 'WP_ENV' ) && ( WP_ENV === 'development' ) ) {
			$prince->setInsecure( true );
		}
		if ( $this->pdfProfile && $this->pdfOutputIntent ) {
			$prince->setPDFProfile( $this->pdfProfile );
			$prince->setPDFOutputIntent( $this->pdfOutputIntent );

		}
		$prince->addStyleSheet( $css_file );
		if ( $this->exportScriptPath ) {
			$prince->addScript( $this->exportScriptPath );
		}
		$prince->setLog( $this->logfile );
		$retval = $prince->convert_file_to_file( $this->url, $this->outputPath, $msg );

		// Prince XML is very flexible. There could be errors but Prince will still render a PDF.
		// We want to log those errors but we won't alert the user.
		if ( is_countable( $msg ) && count( $msg ) ) {
			$this->logError( \Pressbooks\Utility\get_contents( $this->logfile ) );
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
	 * @return string
	 */
	protected function generateFileName() {
		return $this->timestampedFileName( '.pdf' );
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

	/**
	 * @return string
	 */
	protected function getPdfProfile() {
		if ( defined( 'PB_PDF_PROFILE' ) ) {
			return PB_PDF_PROFILE;
		}
		return '';
	}

	/**
	 * @return string
	 */
	protected function getPdfOutputIntent() {
		if ( defined( 'PB_PDF_OUTPUT_INTENT' ) ) {
			return PB_PDF_OUTPUT_INTENT;
		}
		return '';
	}

	/**
	 * Return kneaded CSS string
	 *
	 * @return string
	 */
	protected function kneadCss() {

		$styles = Container::get( 'Styles' );

		$scss = \Pressbooks\Utility\get_contents( $this->exportStylePath );

		$custom_styles = $styles->getPrincePost();
		if ( $custom_styles && ! empty( $custom_styles->post_content ) ) {
			// append the user's custom styles to the theme stylesheet prior to compilation
			$scss .= "\n" . $custom_styles->post_content;
		}

		$css = $styles->customize( 'prince', $scss, $this->cssOverrides );

		$css = normalize_css_urls( $css, $this->urlPath() );

		if ( WP_DEBUG ) {
			Container::get( 'Sass' )->debug( $css, $scss, 'prince' );
		}

		return $css;
	}

	/**
	 * Convert the directory containing `$this->exportStylePath` to a URL that can be used by services like DocRaptor
	 * Useful for sending assets like images/asterisk.png, images/em-dash.png, ...
	 *
	 * @return string
	 */
	protected function urlPath() {
		$dir = str_replace( Container::get( 'Styles' )->getDir(), '', pathinfo( $this->exportStylePath, PATHINFO_DIRNAME ) );
		$dir = ltrim( $dir, '/' );
		$url_path = trailingslashit( get_stylesheet_directory_uri() ) . $dir;
		$url_path = set_url_scheme( $url_path );

		return $url_path;
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

		$hacks = [];
		$hacks = apply_filters( 'pb_pdf_hacks', $hacks );

		// Append endnotes to URL?
		if ( isset( $hacks['pdf_footnotes_style'] ) && 'endnotes' === $hacks['pdf_footnotes_style'] ) {
			$this->url .= '&endnotes=true';
		}

	}

	/**
	 * Increase PB-LaTeX resolution to ~300 dpi
	 *
	 * @see symbionts/pressbooks-latex/automattic-latex-wpcom.php
	 */
	protected function fixLatexDpi() {
		$fix = false;
		if ( ! $fix && ! empty( $_GET['optimize-for-print'] ) ) {
			$fix = true;
		}
		if ( ! $fix && strpos( $this->url, 'optimize-for-print=1' ) !== false ) {
			$fix = true;
		}
		if ( ! $fix && stripos( get_class( $this ), 'print' ) !== false ) {
			$fix = true;
		}

		if ( $fix ) {
			$this->url .= '&pb-latex-zoom=3';
			$this->cssOverrides .= "\n" . 'img.latex { prince-image-resolution: 300dpi; }' . "\n";
		}
	}

}
