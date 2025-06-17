<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */

namespace Pressbooks\Modules\Export\Prince;

use DocRaptor\ApiException;
use DocRaptor\Doc;
use DocRaptor\DocApi;
use DocRaptor\PrinceOptions;
use function Pressbooks\Utility\check_xmllint_install;
use function Pressbooks\Utility\get_contents;
use function Pressbooks\Utility\put_contents;
use Generator;
use Pressbooks\Container;
use Pressbooks\Modules\Export\Xhtml\Xhtml11;

class Docraptor extends Pdf {

	/**
	 * @since 5.4.0
	 *
	 * Constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {

		parent::__construct( $args );
		$_GET['style'] = 'prince';
		$_GET['script'] = 'prince';
		$_GET['movefootnotes'] = 'true';
		$_GET['optimize-for-print'] = 'false';
	}

	/**
	 * For expensive functions we use a generator to allow the caller to yield control back to the event loop.
	 * @return Generator
	 * @throws ContainerExceptionInterface
	 * @throws NotFoundExceptionInterface
	 * @since 5.4.0
	 *
	 * Create $this->outputPath.
	 *
	 */
	public function convert(): Generator {

		yield 35 => __( 'Starting conversion...', 'pressbooks' );

		// Sanity check
		if ( empty( $this->exportStylePath ) || ! is_file( $this->exportStylePath ) ) {
			$this->logError( '$this->exportStylePath must be set before calling convert().' );
			return false;
		}

		yield 40 => __( 'Creating temporary files...', 'pressbooks' );
		// Set logfile
		$this->logfile = $this->createTmpFile();

		// Set filename
		$filename = $this->generateFileName();
		$this->outputPath = $filename;

		yield 45 => __( 'Loading fonts...', 'pressbooks' );
		// Fonts
		Container::get( 'GlobalTypography' )->getFonts();

		yield 50 => __( 'Generating CSS...', 'pressbooks' );
		// CSS
		$this->truncateExportStylesheets( 'prince' );
		$timestamp = time();
		$css = $this->kneadCss();
		$css_file = Container::get( 'Sass' )->pathToUserGeneratedCss() . "/prince-$timestamp.css";
		$scoped_file = Container::get( 'Sass' )->pathToUserGeneratedCss() . '/scopedstyles.css';
		if ( is_file( $scoped_file ) && is_readable( $scoped_file ) ) {
			$scoped_css_content = get_contents( $scoped_file );
			if ( $scoped_css_content ) {
				$css .= "\n\n/* Scoped Styles */\n" . $scoped_css_content;
			}
		}
		put_contents( $css_file, $css );

		yield 55 => __( 'Configuring DocRaptor...', 'pressbooks' );
		// Save PDF as file in exports folder
		$docraptor = new DocApi();
		$docraptor->getConfig()->setUsername( DOCRAPTOR_API_KEY );

		$prince_options = new PrinceOptions();
		$prince_options->setNoCompress( false );
		$prince_options->setJavascript( true );
		if ( $this->pdfProfile && $this->pdfOutputIntent ) {
			$prince_options->setProfile( $this->pdfProfile );
			// DocRaptor doesn't let us setPDFOutputIntent like Prince does, we cheat with a CSS hack later
			// @see \Pressbooks\Modules\Export\Prince\DocraptorPrint::themeOptionsOverrides
		} elseif ( stripos( get_class( $this ), 'print' ) === false && empty( $this->pdfProfile ) ) {
			// PDF (for digital distribution) without any PB_PDF_PROFILE
			// Use PDF/UA-1, enhanced for accessibility.
			$prince_options->setProfile( 'PDF/UA-1' );
		}
		$retval = false;

		yield 58 => __( 'Rendering content...', 'pressbooks' );
		try {
			$doc = new Doc();
			if ( defined( 'WP_TESTS_MULTISITE' ) ) {
				// Unit tests
				$document_content = str_replace( '</head>', "<style>$css</style></head>", get_contents( $this->url ) );
				$doc->setTest( true );
				$doc->setDocumentContent( $document_content );
			} else {
				// The real thing
				$doc->setTest( defined( 'WP_ENV' ) && ( WP_ENV === 'development' ) );
				$xhtml = new Xhtml11( [
					'no-export' => true,
					'endnotes' => true,
				] );
				$generator = $xhtml->convert();
				$document_content = '';
				while ( $generator->valid() ) {
					yield $generator->key() => $generator->current();
					$generator->next();
				}
				$document_content = $xhtml->transformOutput;
				$document_content = str_replace( '</head>', "<style>$css</style></head>", $document_content );
				$doc->setDocumentContent( $document_content );
			}
			$doc->setName( get_bloginfo( 'name' ) );
			$doc->setPrinceOptions( $prince_options );
			$doc->setPipeline( defined( 'DOCRAPTOR_PIPELINE' ) ? DOCRAPTOR_PIPELINE : '9.2' ); // Prince 14.3, see: https://docraptor.com/documentation/api#api_pipeline

			yield 80 => __( 'Converting document...', 'pressbooks' );
			$create_response = $docraptor->createAsyncDoc( $doc );
			$done = false;
			while ( ! $done ) {
				$status_response = $docraptor->getAsyncDocStatus( $create_response->getStatusId() );
				switch ( $status_response->getStatus() ) {
					case 'completed':
						yield 90 => __( 'Fetching converted file...', 'pressbooks' );
						if ( ! function_exists( 'download_url' ) ) {
							require_once( ABSPATH . 'wp-admin/includes/file.php' );
						}
						$result = \download_url( $status_response->getDownloadUrl() );
						if ( is_wp_error( $result ) ) {
							$_SESSION['pb_errors'][] = __( 'Your PDF could not be retrieved.', 'pressbooks' );
						} else {
							copy( $result, $this->outputPath );
							unlink( $result );
							$retval = true;
						}
						$done = true;
						$exportoptions = get_option( 'pressbooks_export_options' );
						if ( isset( $exportoptions['email_validation_logs'] ) && 1 === absint( $exportoptions['email_validation_logs'] ) ) {
							$msg = $this->getDetailedLog( $create_response->getStatusId() );
							put_contents( $this->logfile, $msg );
						}
						break;
					case 'failed':
						$msg = $status_response;
						put_contents( $this->logfile, $msg );
						$done = true;
						break;
					default:
						sleep( 1 );
				}
			}
		} catch ( ApiException $exception ) {
			$msg = $exception->getResponseBody();
			put_contents( $this->logfile, $exception->getResponseBody() );
		} catch ( \Exception $e ) {
			$msg = $e->getMessage();
			put_contents( $this->logfile, $msg );
		}

		if ( ! empty( $msg ) ) {
			$this->logError( get_contents( $this->logfile ) );
		}

		yield 80 => __( 'Conversion complete.', 'pressbooks' );
		return $retval;
	}

	/**
	 * When given a DocRaptor async status ID, return the document generation log for the relevant job.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function getDetailedLog( $id ): string {
		// @see: https://docraptor.com/documentation/api#doc_log_listing
		$response = wp_remote_get( esc_url( 'https://docraptor.com/doc_logs.json?per_page=25&user_credentials=' . DOCRAPTOR_API_KEY ) );
		if ( is_wp_error( $response ) ) {
			return $response->get_error_message();
		}
		$logs = json_decode( $response['body'] );
		if ( $logs ) {
			foreach ( $logs as $log ) {
				if ( $log->status_id == $id ) { // @codingStandardsIgnoreLine
					return $log->generation_log;
				}
			}
		}
		return '';
	}

	/**
	 * @since 5.4.0
	 *
	 * Dependency check.
	 *
	 * @return bool
	 */
	public static function hasDependencies(): bool {
		return check_xmllint_install() !== false;
	}
}
