<?php

namespace Pressbooks\Covergenerator;

use function Pressbooks\Utility\template;
use Pressbooks\Container;

class PrincePdf extends Generator {

	/**
	 * @var string
	 */
	protected $pdfProfile;

	/**
	 * @var string
	 */
	protected $pdfOutputIntent;

	/**
	 * Required HTML variables
	 *
	 * @var array
	 */
	protected $requiredHtmlVars = [
		'title',
		'author',
	];

	/**
	 * Optional HTML variables
	 *
	 * @var array
	 */
	protected $optionalHtmlVars = [
		'about',
		'subtitle',
		'spine_title',
		'spine_author',
		'isbn_image',
	];

	/**
	 * Required SASS variables (no dollar sign)
	 *
	 * @var array
	 */
	protected $requiredSassVars = [
		'trim-width',
		'trim-height',
		'spine-width',
	];

	/**
	 * Optional SASS variables (no dollar sign)
	 *
	 * @var array
	 */
	protected $optionalSassVars = [
		'text-transform',
		'trim-bleed',
		'spine-background-color',
		'spine-font-color',
		'back-background-color',
		'back-font-color',
		'front-background-color',
		'front-font-color',
		'front-background-image',
	];


	/**
	 * Constructor
	 *
	 * @param Input $input
	 */
	public function __construct( Input $input ) {
		parent::__construct( $input );
		$this->pdfProfile = apply_filters( 'pb_pdf_for_print_profile', 'PDF/X-4' );
		$this->pdfOutputIntent = '/usr/lib/prince/icc/USWebCoatedSWOP.icc';
	}


	/**
	 * Generate PDF print cover
	 *
	 * @throws \Exception
	 *
	 * @return string Output path
	 */
	public function generate() {
		$output_path = $this->timestampedFileName( 'pdf' );
		$success = $this->generateWithPrince( $this->pdfProfile, $this->pdfOutputIntent, $this->generateHtml(), $output_path );
		if ( true !== $success ) {
			throw new \Exception( 'Failed to create PDF file' );
		}
		delete_transient( 'dirsize_cache' ); /** @see get_dirsize() */
		return $output_path;
	}


	/**
	 * Generate CSS for PDF print cover
	 *
	 * @return string Path to generated CSS file
	 */
	protected function generateCss() {
		$styles = Container::get( 'Styles' );
		$scss = $this->getScssVars();

		if ( $styles->isCurrentThemeCompatible( 1 ) ) {
			$scss .= "@import 'fonts-prince'; \n";
		} elseif ( $styles->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "@import 'fonts'; \n";
		}
		$scss .= \Pressbooks\Utility\get_contents( PB_PLUGIN_DIR . 'assets/covergenerator/_pdf.scss' );

		$css = $styles->customize( 'prince', $scss );
		$css = \Pressbooks\Sanitize\normalize_css_urls( $css );

		if ( WP_DEBUG ) {
			Container::get( 'Sass' )->debug( $css, $scss, 'cover-pdf' );
		}

		return $css;
	}

	/**
	 * Generate HTML for PDF print cover
	 *
	 * @return string Path to generated CSS Html file
	 */
	protected function generateHtml() {
		$vars = $this->getHtmlTemplateVars();
		$vars['css'] = apply_filters( 'pb_pdf_cover_css_override', $this->generateCss() );
		$html = template( PB_PLUGIN_DIR . 'templates/covergenerator/pdf-cover.php', $vars );
		return $html;
	}
}
