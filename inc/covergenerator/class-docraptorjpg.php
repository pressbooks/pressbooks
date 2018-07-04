<?php

namespace Pressbooks\Covergenerator;

use Pressbooks\Modules\Export\Export;
use function Pressbooks\Utility\create_tmp_file;
use function Pressbooks\Utility\template;

class DocraptorJpg extends Generator {


	/**
	 * @var Input
	 */
	protected $input;

	/**
	 * @var string
	 */
	protected $pdfProfile;

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
		'subtitle',
	];

	/**
	 * Required SASS variables (no dollar sign)
	 *
	 * @var array
	 */
	protected $requiredSassVars = [];

	/**
	 * Optional SASS variables (no dollar sign)
	 *
	 * @var array
	 */
	protected $optionalSassVars = [
		'text-transform',
		'trim-bleed',
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

		$this->pdfProfile = apply_filters( 'pb_pdf_for_digital_profile', 'PDF/X-4' );

		if ( ! defined( 'PB_PRINCE_COMMAND' ) ) {
			define( 'PB_PRINCE_COMMAND', '/usr/bin/prince' );
		}

		if ( ! defined( 'PB_CONVERT_COMMAND' ) ) {
			define( 'PB_CONVERT_COMMAND', '/usr/bin/convert' );
		}

		if ( ! defined( 'PB_PDFTOPPM_COMMAND' ) ) {
			define( 'PB_PDFTOPPM_COMMAND', '/usr/bin/pdftoppm' );
		}

		parent::__construct( $input );
	}



	/**
	 * Generate Ebook JPG cover
	 *
	 * @throws \Exception
	 */
	public function generate() {
		// Configure service
		$configuration = \DocRaptor\Configuration::getDefaultConfiguration();
		if ( defined( 'DOCRAPTOR_API_KEY' ) ) {
			$configuration->setUsername( DOCRAPTOR_API_KEY );
		}

		$tmp_pdf_path = create_tmp_file();

		$docraptor = new \DocRaptor\DocApi();
		$prince_options = new \DocRaptor\PrinceOptions();
		$prince_options->setProfile( $this->pdfProfile );

		try {
			$doc = new \DocRaptor\Doc();
			if ( WP_ENV === 'development' ) {
				$doc->setTest( true );
			} else {
				$doc->setTest( false );
			}
			$doc->setDocumentContent( $this->generateHtml() );
			$doc->setName( get_bloginfo( 'name' ) . ' Cover' );
			$doc->setPrinceOptions( $prince_options );
			$create_response = $docraptor->createAsyncDoc( $doc );

			$done = false;

			while ( ! $done ) {
				$status_response = $docraptor->getAsyncDocStatus( $create_response->getStatusId() );
				switch ( $status_response->getStatus() ) {
					case 'completed':
						$doc_response = $docraptor->getAsyncDoc( $status_response->getDownloadId() );
						// @codingStandardsIgnoreStart
						$retval = fopen( $tmp_pdf_path, 'wb' );
						fwrite( $retval, $doc_response );
						fclose( $retval );
						// @codingStandardsIgnoreEnd
						$done = true;
						break;
					case 'failed':
						wp_die( $status_response );
						$done = true;
						break;
					default:
						sleep( 1 );
				}
			}
		} catch ( \DocRaptor\ApiException $exception ) {
			$message = "<h1>{$exception->getMessage()}</h1><p>{$exception->getCode()}</p><p>{$exception->getResponseBody()}</p>";
			wp_die( $message );
		}

		$output_path = $this->timestampedFileName( 'jpg' );

		$this->convert( $tmp_pdf_path, $output_path );

		delete_transient( 'dirsize_cache' ); /** @see get_dirsize() */
	}


	/**
	 * Generate CSS for Ebook JPG cover
	 *
	 * @return string the generated CSS
	 */
	protected function generateCss() {
		$sass = \Pressbooks\Container::get( 'Sass' );

		$scss = $this->getScssVars();

		if ( $sass->isCurrentThemeCompatible( 1 ) ) {
			$scss .= "@import 'fonts-prince'; \n";
		} elseif ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$scss .= "@import 'fonts'; \n";
		}

		$scss .= \Pressbooks\Utility\get_contents( PB_PLUGIN_DIR . 'assets/src/styles/partials/_covergenerator-jpg.scss' );

		if ( $sass->isCurrentThemeCompatible( 1 ) ) {
			$includes = [
				$sass->pathToUserGeneratedSass(),
				$sass->pathToPartials(),
				$sass->pathToFonts(),
				get_stylesheet_directory(),
			];
		} elseif ( $sass->isCurrentThemeCompatible( 2 ) ) {
			$includes = $sass->defaultIncludePaths( 'prince' );
		} else {
			$includes = [];
		}

		if ( \Pressbooks\CustomCss::isCustomCss() ) {
			$base = \Pressbooks\CustomCss::getBaseTheme( 'epub' );
			$includes[] = get_theme_root( $base ) . '/' . $base;
		}

		$css = $sass->compile( $scss, $includes );
		$css = \Pressbooks\Sanitize\normalize_css_urls( $css );

		if ( WP_DEBUG ) {
			\Pressbooks\Container::get( 'Sass' )->debug( $css, $scss, 'cover-jpg' );
		}

		return $css;
	}


	/**
	 * Generate HTML for Ebook JPG cover
	 *
	 * @return string the generated Html
	 */
	protected function generateHtml() {
		$vars = $this->getHtmlTemplateVars();
		$vars['css'] = apply_filters( 'pb_epub_cover_css_override', $this->generateCss() );

		$html = template( PB_PLUGIN_DIR . 'templates/covergenerator/jpg-cover.php', $vars );

		return $html;
	}


	// Bonus info, originally tried cropping the PDF print cover to get the Ebook JPG cover, but the inch-to-pixel math wasn't working ( CSS Standard: 1 inch = 96 px, ImageMagick: 1 inch ~= 73.8 ?! )
	// Here's the command I was using for anyone from the future who wants to try:
	// $ convert -verbose -crop {WIDTH}x{HEIGHT}-0-0 -gravity SouthEast +repage PDF-print-cover.pdf output.png

	/**
	 * Use ImageMagick to convert a PDF to a JPG (max 2MB)
	 *
	 * @param string $path_to_pdf Input path to PDF
	 * @param string $path_to_jpg Output path to JPG
	 * @param string $resize
	 *
	 * @throws \Exception
	 */
	public function convert( $path_to_pdf, $path_to_jpg, $resize = '2500x3750' ) {

		// Convert using Imagemagick (TODO: fix jagged ugly fonts)
		// $command = PB_CONVERT_COMMAND . ' ' . escapeshellarg( $pathToPdf ) . " -density 96 -resize {$resize} -define jpeg:extent=2MB " . escapeshellarg( $pathToJpg );

		// Convert using pdfToPpm
		list( $x, $y ) = explode( 'x', $resize );
		$path_to_jpg = rtrim( $path_to_jpg, '.jpg' ); // Remove extension because it is auto-generated by pdfToPpm
		$command = PB_PDFTOPPM_COMMAND . ' -jpeg -singlefile -scale-to-x ' . (int) $x . ' -scale-to-y ' . (int) $y . ' ' . escapeshellarg( $path_to_pdf ) . ' ' . escapeshellarg( $path_to_jpg );

		// Execute command
		$output = [];
		$return_var = 0;
		exec( $command, $output, $return_var ); // @codingStandardsIgnoreLine

		$post = ( new \Pressbooks\Metadata() )->getMetaPost();

		if ( $post ) {
			$jpg = \Pressbooks\Utility\create_tmp_file();

			copy( $path_to_jpg . '.jpg', $jpg );

			$old_id = \Pressbooks\Image\attachment_id_from_url( get_post_meta( $post->ID, 'pb_cover_image', true ) );
			if ( $old_id ) {
				wp_delete_attachment( $old_id, true );
			}

			if ( ! function_exists( 'media_handle_sideload' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/image.php' );
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
				require_once( ABSPATH . 'wp-admin/includes/media.php' );
			}

			$pid = media_handle_sideload(
				[
					'name' => 'cover.jpg',
					'tmp_name' => $jpg,
				], 0
			);
			if ( is_wp_error( $pid ) ) {
				throw new \Exception(
					$pid->get_error_message(),
					$pid->get_error_code()
				);
			}

			$src = wp_get_attachment_url( $pid );

			if ( false === $src ) {
				throw new \Exception( 'No attachment url.' );
			}

			update_post_meta( $post->ID, 'pb_cover_image', $src );
		}

		if ( ! empty( $output ) ) {
			// TODO: Throw Exception
			// @codingStandardsIgnoreStart
			error_log( $command );
			error_log( print_r( $output, true ) );
			// @codingStandardsIgnoreEnd
		}
	}
}
