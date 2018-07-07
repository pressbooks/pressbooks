<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */

namespace Pressbooks\Modules\Export\Prince;

use Pressbooks\Container;

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
		$this->url .= '&style=docraptor&script=prince';
	}


	/**
	 * @since 5.4.0
	 *
	 * Create $this->outputPath.
	 *
	 * @return bool|string
	 */
	public function convert() {

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
		$css = $this->kneadCss();
		$css_file = \Pressbooks\Container::get( 'Sass' )->pathToUserGeneratedCss() . '/docraptor.css';
		\Pressbooks\Utility\put_contents( $css_file, $css );

		// --------------------------------------------------------------------
		// Save PDF as file in exports folder

		$configuration = \DocRaptor\Configuration::getDefaultConfiguration();
		$configuration->setUsername( DOCRAPTOR_API_KEY );

		$docraptor = new \DocRaptor\DocApi();
		$prince_options = new \DocRaptor\PrinceOptions();
		$prince_options->setNoCompress( false );
		$prince_options->setHttpTimeout( max( ini_get( 'max_execution_time' ), 30 ) );
		$prince_options->setProfile( $this->pdfProfile );
		$retval = false;

		try {
			$doc = new \DocRaptor\Doc();
			if ( defined( 'WP_TESTS_MULTISITE' ) ) {
				// Unit tests
				$document_content = str_replace( '</head>', "<style>$css</style></head>", \Pressbooks\Utility\get_contents( $this->url ) );
				$doc->setTest( true );
				$doc->setDocumentContent( $document_content );
			} elseif ( defined( 'WP_ENV' ) && ( WP_ENV === 'development' || WP_ENV === 'staging' ) ) {
				// Localhost
				$args['sslverify'] = false;
				$response = wp_remote_get( $this->url, $args );
				if ( is_wp_error( $response ) ) {
					$this->logError( $response->get_error_message() );
					return false;
				}
				$document_content = str_replace( '</head>', "<style>$css</style></head>", $response['body'] );
				$doc->setTest( true );
				$doc->setDocumentContent( $document_content );
			} else {
				// The real thing
				$doc->setTest( false );
				$doc->setDocumentUrl( $this->url );
			}
			$doc->setName( get_bloginfo( 'name' ) );
			$doc->setPrinceOptions( $prince_options );

			$create_response = $docraptor->createAsyncDoc( $doc );
			$done = false;
			while ( ! $done ) {
				$status_response = $docraptor->getAsyncDocStatus( $create_response->getStatusId() );
				switch ( $status_response->getStatus() ) {
					case 'completed':
						if ( ! function_exists( 'download_url' ) ) {
							require_once( ABSPATH . 'wp-admin/includes/file.php' );
						}
						$result = \download_url( $status_response->getDownloadUrl() );
						if ( is_wp_error( $result ) ) {
							$_SESSION['pb_errors'][] = __( 'Your PDF could not be retrieved.', 'pressbooks-docraptor' );
						} else {
							copy( $result, $this->outputPath );
							unlink( $result );
							$retval = true;
						}
						$done = true;
						$exportoptions = get_option( 'pressbooks_export_options' );
						if ( isset( $exportoptions['email_validation_logs'] ) && 1 === absint( $exportoptions['email_validation_logs'] ) ) {
							$msg = $this->getDetailedLog( $create_response->getStatusId() );
							\Pressbooks\Utility\put_contents( $this->logfile, $msg );
						}
						break;
					case 'failed':
						$msg = $status_response;
						\Pressbooks\Utility\put_contents( $this->logfile, $msg );
						$done = true;
						break;
					default:
						sleep( 1 );
				}
			}
		} catch ( \DocRaptor\ApiException $exception ) {
			$msg = $exception->getResponseBody();
			\Pressbooks\Utility\put_contents( $this->logfile, $msg );
		}

		if ( ! empty( $msg ) ) {
			$this->logError( \Pressbooks\Utility\get_contents( $this->logfile ) );
		}

		return $retval;
	}

	/**
	 * When given a DocRaptor async status ID, return the document generation log for the relevant job.
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	protected function getDetailedLog( $id ) {
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
	public static function hasDependencies() {
		if ( false !== \Pressbooks\Utility\check_xmllint_install() ) {
			return true;
		}
		return false;
	}
}
