<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\Prince;

use function Pressbooks\Sanitize\normalize_css_urls;
use function Pressbooks\Utility\get_contents;
use function Pressbooks\Utility\put_contents;
use PressbooksMix\Assets;
use Pressbooks\Container;
use Pressbooks\Modules\Export\ExportGenerator;
use PrinceXMLPhp\PrinceWrapper;

class Pdf extends ExportGenerator {

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

		$this->themeOptionsOverrides();
	}

	/**
	 * Add $this->url as additional log info, fallback to parent.
	 *
	 * @param $message
	 * @param array $more_info (unused, overridden)
	 */
	function logError( $message, array $more_info = [] ) {

		$more_info['url'] = $this->url;

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

		$scss = get_contents( $this->exportStylePath );

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
		return set_url_scheme( $url_path );
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
			$freebie_notice = __( 'This book was produced with Pressbooks (https://pressbooks.com) and rendered with Prince.', 'pressbooks' );
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

	public function convertGenerator(): \Generator {
		// Sanity check
		if ( empty( $this->exportStylePath ) || ! is_file( $this->exportStylePath ) ) {
			$this->logError( '$this->exportStylePath must be set before calling convert().' );
			yield 'error' => '$this->exportStylePath must be set before calling convert().';
			return false;
		}

		yield 35 => 'Setting up conversion...';

		// Set logfile
		$this->logfile = $this->createTmpFile();

		// Set filename
		$filename = $this->generateFileName();
		$this->outputPath = $filename;

		yield 40 => 'Loading fonts...';
		// Fonts
		Container::get( 'GlobalTypography' )->getFonts();

		yield 50 => 'Processing CSS...';
		// CSS
		$this->truncateExportStylesheets( 'prince' );
		$timestamp = time();
		$css = $this->kneadCss();
		$css_file = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp.css";
		$scoped_file = Container::get( 'Sass' )->pathToUserGeneratedCss() . '/scopedstyles.css';
		put_contents( $css_file, $css );

		yield 55 => 'Initializing Prince...';
		// Initialize Prince
		$prince = new PrinceWrapper( PB_PRINCE_COMMAND );
		$prince->setHTML( true );
		$prince->setCompress( true );
		$prince->setHttpTimeout( 900 );
		if ( defined( 'WP_ENV' ) && ( WP_ENV === 'development' ) ) {
			$prince->setInsecure( true );
		}

		yield 56 => 'Configuring PDF settings...';
		// PDF Profile configuration
		if ( $this->pdfProfile && $this->pdfOutputIntent ) {
			$prince->setPDFProfile( $this->pdfProfile );
			$prince->setPDFOutputIntent( $this->pdfOutputIntent );
		} elseif ( stripos( get_class( $this ), 'print' ) === false && empty( $this->pdfProfile ) ) {
			$prince->setPDFProfile( 'PDF/UA-1' );
		}

		yield 60 => 'Adding stylesheets and scripts...';
		// Add resources
		$prince->addStyleSheet( $css_file );
		$prince->addStyleSheet( $scoped_file );
		$assets = new Assets( 'pressbooks', 'plugin' );
		$js_path = $assets->getPath( 'scripts/export-footnotes.js' );
		$prince->addScript( $js_path );

		if ( $this->exportScriptPath ) {
			$prince->addScript( $this->exportScriptPath );
		}
		$prince->setLog( $this->logfile );

		yield 65 => 'Converting to PDF...';
		// Convert
		$retval = $prince->convert_file_to_file( $this->url, $this->outputPath, $msg );

		if ( is_countable( $msg ) && count( $msg ) ) {
			$this->logError( get_contents( $this->logfile ), [ 'warning' => 1 ] );
			yield 'warning' => 'Conversion completed with warnings';
		} else {
			yield 'success' => 'Conversion completed successfully';
		}

		return $retval;
	}

	function validateGenerator(): \Generator {
		if ( ! $this->isPdf( $this->outputPath ) ) {
			$this->logError( get_contents( $this->logfile ) );
			yield 'error' => 'The output file is not a valid PDF.';
			return false;
		}

		yield 70 => 'PDF validation successful';
		return true;
	}

	function convert(): bool|\Generator {
		$generator = $this->convertGenerator();
		while ( $generator->valid() ) {
			yield $generator->current();
			$generator->next();
		}
	}

	function validate(): bool|\Generator {
		$generator = $this->validateGenerator();
		while ( $generator->valid() ) {
			yield $generator->current();
			$generator->next();
		}
	}
}
