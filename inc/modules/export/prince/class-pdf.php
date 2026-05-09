<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\Prince;

use function Pressbooks\Sanitize\normalize_css_urls;
use function Pressbooks\Utility\get_contents;
use function Pressbooks\Utility\put_contents;
use Generator;
use Pressbooks\Container;
use Pressbooks\Modules\Export\Export;
use PrinceXMLPhp\PrinceWrapper;

class Pdf extends Export {

	/**
	 * Service URL
	 *
	 * @var string
	 */
	public string $url;

	/**
	 * Fullpath to log file used by Prince.
	 *
	 * @var string
	 */
	public string $logfile;

	/**
	 * Fullpath to book CSS file.
	 *
	 * @var string
	 */
	protected string|false $exportStylePath;

	/**
	 * Fullpath to book JavaScript file.
	 *
	 * @var string
	 */
	protected string|false $exportScriptPath;

	/**
	 * CSS overrides
	 *
	 * @var string
	 */
	protected string $cssOverrides;

	/**
	 * @var string
	 */
	protected string $pdfProfile;

	/**
	 * @var string
	 */
	protected string $pdfOutputIntent;

	/**
	 * @param array $args
	 */
	public function __construct( array $args ) {

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
	public function logError( $message, array $more_info = [] ): void {

		$more_info['url'] = $this->url;

		parent::logError( $message, $more_info );
	}

	/**
	 * @return string
	 */
	protected function generateFileName(): string {
		return $this->timestampedFileName( '.pdf' );
	}

	/**
	 * Verify if body is actual PDF
	 *
	 * @param string $file
	 *
	 * @return bool
	 */
	protected function isPdf( $file ): bool {

		$mime = static::mimeType( $file );

		return ( str_contains( $mime, 'application/pdf' ) );
	}

	/**
	 * @return string
	 */
	protected function getPdfProfile(): string {
		return defined( 'PB_PDF_PROFILE' ) ? PB_PDF_PROFILE : '';
	}

	/**
	 * @return string
	 */
	protected function getPdfOutputIntent(): string {
		return defined( 'PB_PDF_OUTPUT_INTENT' ) ? PB_PDF_OUTPUT_INTENT : '';
	}

	/**
	 * Return kneaded CSS string
	 *
	 * @return string
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 */
	protected function kneadCss(): string {

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
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
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
	protected function themeOptionsOverrides(): void {

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
			$_GET['endnotes'] = 'true';
		}

	}

	/**
	 * For expensive functions we use a generator to allow the caller to yield control back to the event loop.
	 *
	 * @return Generator
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @throws \Exception
	 */
	public function convert(): Generator {

		if ( empty( $this->exportStylePath ) || ! is_file( $this->exportStylePath ) ) {
			$this->logError( '$this->exportStylePath must be set before calling convert().' );
			yield 'error' => '$this->exportStylePath must be set before calling convert().';
			return false;
		}

		yield 35 => __( 'Setting up conversion...', 'pressbooks' );

		// Set logfile
		$this->logfile = $this->createTmpFile();

		// Set filename
		$filename = $this->generateFileName();
		$this->outputPath = $filename;

		yield 40 => __( 'Loading fonts...', 'pressbooks' );
		// Fonts
		Container::get( 'GlobalTypography' )->getFonts();

		yield 50 => __( 'Generating CSS...', 'pressbooks' );
		// CSS
		$this->truncateExportStylesheets( 'prince' );
		$timestamp = time();
		$css = $this->kneadCss();
		$css_file = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp.css";
		$scoped_file = Container::get( 'Sass' )->pathToUserGeneratedCss() . '/scopedstyles.css';
		put_contents( $css_file, $css );

		yield 55 => __( 'Loading Converter...', 'pressbooks' );
		// Initialize Prince
		$prince = new PrinceWrapper( PB_PRINCE_COMMAND );
		$prince->setHTML( true );
		$prince->setCompress( true );
		$prince->setHttpTimeout( defined( 'WP_TESTS_MULTISITE' ) ? 5 : 600 ); // 5 seconds for tests, 10 minutes for production
		if ( defined( 'WP_ENV' ) && ( WP_ENV === 'development' ) ) {
			$prince->setInsecure( true );
		}

		yield 56 => __( 'Setting up PDF options...', 'pressbooks' );
		// PDF Profile configuration
		if ( $this->pdfProfile && $this->pdfOutputIntent ) {
			$prince->setPDFProfile( $this->pdfProfile );
			$prince->setPDFOutputIntent( $this->pdfOutputIntent );
		} elseif ( stripos( get_class( $this ), 'print' ) === false && empty( $this->pdfProfile ) ) {
			$prince->setPDFProfile( 'PDF/UA-1' );
		}

		yield 60 => __( 'Adding stylesheets and scripts...', 'pressbooks' );
		// Add resources
		$prince->addStyleSheet( $css_file );
		$prince->addStyleSheet( $scoped_file );
		/** @var Assets $assets */
		$assets = app( 'Assets' );
		$js_path = $assets->getAssetUrl( 'assets/src/scripts/export-footnotes.js' );
		$prince->addScript( $js_path );

		if ( $this->exportScriptPath ) {
			$prince->addScript( $this->exportScriptPath );
		}
		$prince->setLog( $this->logfile );

		yield 65 => __( 'Creating file...', 'pressbooks' );
		// Convert
		$retval = $prince->convert_file_to_file( $this->url, $this->outputPath, $msg );

		if ( is_countable( $msg ) && count( $msg ) ) {
			$this->logError( get_contents( $this->logfile ), [ 'warning' => 1 ] );
			yield 80 => __( 'Conversion completed with warnings.', 'pressbooks' );
		} else {
			yield 80 => __( 'Conversion completed successfully.', 'pressbooks' );
		}

		return $retval;
	}

	public function validate(): Generator {
		yield 90 => __( 'Validating PDF.', 'pressbooks' );
		if ( ! $this->isPdf( $this->outputPath ) ) {
			$this->logError( get_contents( $this->logfile ) );
			yield 'error' => __( 'PDF validation failed.', 'pressbooks' );
			return false;
		}

		yield 100 => __( 'PDF Validation successful.', 'pressbooks' );
		return true;
	}
}
