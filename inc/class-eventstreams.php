<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.MissingUnslash
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotValidated
// @phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged

namespace Pressbooks;

use function Pressbooks\Utility\getset;
use Pressbooks\Cloner\Cloner;
use Pressbooks\Modules\Export\Export;
use Pressbooks\Modules\Import\Import;

class EventStreams {

	/**
	 * @var EventStreams
	 */
	private static $instance = null;

	/**
	 * @var array
	 */
	public $msgStack = [];

	/**
	 * @return EventStreams
	 */
	static public function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
			self::hooks( self::$instance );
		}
		return self::$instance;
	}

	/**
	 * @param EventStreams $obj
	 */
	static public function hooks( EventStreams $obj ) {
		add_action( 'wp_ajax_clone-book', [ $obj, 'cloneBook' ] );
		add_action( 'wp_ajax_export-book', [ $obj, 'exportBook' ] );
		add_action( 'wp_ajax_import-book', [ $obj, 'importBook' ] );
		add_action( 'wp_ajax_cover-generator', [ $obj, 'coverGenerator' ] );
	}

	/**
	 */
	public function __construct() {
	}

	/**
	 * This method accepts a generator that yields a key/value pair
	 * The key is an integer between 1-100 that represents percentage completed
	 * The value is a string of information for the user
	 * Emits event-stream responses (SSE)
	 *
	 * @param \Generator $generator
	 * @param bool $auto_complete
	 * @return bool
	 */
	public function emit( \Generator $generator, $auto_complete = false ) {
		$this->setupHeaders();
		try {
			foreach ( $generator as $percentage => $info ) {
				$data = [
					'action' => 'updateStatusBar',
					'percentage' => $percentage,
					'info' => $info,
				];
				$this->emitMessage( $data );
			}
		} catch ( \Exception $e ) {
			$error = [
				'action' => 'complete',
				'error' => $e->getMessage(),
			];
		}

		flush();
		if ( ! empty( $error ) ) {
			// Something went wrong
			$this->emitMessage( $error );
			return false;
		} elseif ( $auto_complete ) {
			$this->emitComplete();
		}
		// No errors
		return true;
	}

	/**
	 * Emit a Server-Sent Events message.
	 *
	 * @param mixed $data Data to be JSON-encoded and sent in the message.
	 */
	private function emitMessage( $data ) {
		$msg = "event: message\n";
		$msg .= 'data: ' . wp_json_encode( $data ) . "\n\n";
		$msg .= ':' . str_repeat( ' ', 2048 ) . "\n\n";
		// Buffers are nested. While one buffer is active, flushing from child buffers are not really sent to the browser,
		// but rather to the parent buffer. Only when there is no parent buffer are contents sent to the browser.
		if ( ob_get_level() ) {
			// Keep for later
			$this->msgStack[] = $msg;
		} else {
			// Flush to browser
			foreach ( $this->msgStack as $stack ) {
				echo $stack;
			}
			$this->msgStack = []; // Reset
			echo $msg;
			flush();
		}
	}

	/**
	 * Emit an error, one time, complete with headers.
	 * Useful when you want to tell `EventSource` to abort before staring anything, such as failing form validation.
	 *
	 * @param $error
	 */
	public function emitOneTimeError( $error ) {
		$this->setupHeaders();
		$this->emitMessage(
			[
				'action' => 'complete',
				'error' => $error,
			]
		);
	}

	/**
	 * Emit successful complete message
	 */
	public function emitComplete() {
		$complete = [
			'action' => 'complete',
			'error' => false,
		];
		$this->emitMessage( $complete );
	}

	/**
	 *
	 */
	private function setupHeaders() {
		// Turn off PHP output compression
		@ini_set( 'output_buffering', 'off' );
		@ini_set( 'zlib.output_compression', false );
		if ( $GLOBALS['is_nginx'] ) {
			@header( 'X-Accel-Buffering: no' );
			@header( 'Content-Encoding: none' );
		}
		// Start the event stream
		@header( 'Content-Type: text/event-stream' );

		// 2KB padding for IE
		echo ':' . str_repeat( ' ', 2048 ) . "\n\n";

		// Time to run the generator
		ignore_user_abort( true );
		set_time_limit( apply_filters( 'pb_set_time_limit', 0, 'sse' ) );

		// Flush and end all output buffer
		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			wp_ob_end_flush_all();
		}
		flush();
		$this->msgStack = []; // Reset
	}

	/**
	 * Clone a book
	 */
	public function cloneBook() {
		check_admin_referer( 'pb-cloner' );

		$source_url = $_GET['source_book_url'] ?? '';

		$target_url = Cloner::validateNewBookName( $_GET['target_book_url'] );
		if ( is_wp_error( $target_url ) ) {
			$this->emitOneTimeError( $target_url->get_error_message() );
			return;
		}

		$target_title = $_GET['target_book_title'] ?? '';

		$cloner = new Cloner( $source_url, $target_url, $target_title );
		$everything_ok = $this->emit( $cloner->cloneBookGenerator() );

		if ( $everything_ok ) {
			$cloned_items = $cloner->getClonedItems();
			$notice = sprintf(
				__( 'Cloning succeeded! Cloned %1$s, %2$s, %3$s, %4$s, %5$s, %6$s, %7$s, and %8$s to %9$s.', 'pressbooks' ),
				sprintf( _n( '%s term', '%s terms', count( getset( $cloned_items, 'terms', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'terms', [] ) ) ),
				sprintf( _n( '%s front matter', '%s front matter', count( getset( $cloned_items, 'front-matter', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'front-matter', [] ) ) ),
				sprintf( _n( '%s part', '%s parts', count( getset( $cloned_items, 'parts', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'parts', [] ) ) ),
				sprintf( _n( '%s chapter', '%s chapters', count( getset( $cloned_items, 'chapters', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'chapters', [] ) ) ),
				sprintf( _n( '%s back matter', '%s back matter', count( getset( $cloned_items, 'back-matter', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'back-matter', [] ) ) ),
				sprintf( _n( '%s media attachment', '%s media attachments', count( getset( $cloned_items, 'media', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'media', [] ) ) ),
				sprintf( _n( '%s H5P element', '%s H5P elements', count( getset( $cloned_items, 'h5p', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'h5p', [] ) ) ),
				sprintf( _n( '%s glossary term', '%s glossary terms', count( getset( $cloned_items, 'glossary', [] ) ), 'pressbooks' ), count( getset( $cloned_items, 'glossary', [] ) ) ),
				sprintf( '<a href="%1$s"><em>%2$s</em></a>', trailingslashit( $cloner->getTargetBookUrl() ) . 'wp-admin/', $cloner->getTargetBookTitle() )
			);
			$source_theme = $cloner->getSourceTheme();
			if ( ! empty( $source_theme ) ) {
				$theme_notice = ! $cloned_items['theme'] ?
					sprintf(
						__( ' The source book\'s theme, \'%1$s (%2$s)\', was not available on this network and could not be applied. Contact your network manager with questions about theme availability.', 'pressbooks' ),
						$source_theme['name'],
						$source_theme['version']
					) :
					__( 'The source book\'s theme, theme settings, and custom styles were successfully applied.', 'pressbooks' );
				$notice .= " $theme_notice";
			}
			\Pressbooks\add_notice( $notice );
		}

		// Tell the browser to stop reconnecting.
		$this->emitComplete();
		status_header( 204 );

		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit; // Short circuit wp_die(0);
		}
	}

	/**
	 * Export book
	 */
	public function exportBook() {
		check_admin_referer( 'pb-export' );

		if ( ! is_array( getset( '_GET', 'export_formats' ) ) ) {
			$this->emitOneTimeError( __( 'No export format was selected.', 'pressbooks' ) );
			return;
		}

		// Backwards compatibility with older plugins
		foreach ( $_GET['export_formats'] as $k => $v ) {
			$_POST['export_formats'][ $k ] = $v;
		}

		Export::preExport();
		$this->emit( Export::exportGenerator( Export::modules() ) );
		Export::postExport();

		// Tell the browser to stop reconnecting.
		$this->emitComplete();
		status_header( 204 );

		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit; // Short circuit wp_die(0);
		}
	}

	/**
	 * Import book
	 */
	public function importBook() {
		check_admin_referer( 'pb-import' );

		// Because there's a maximum $_GET length, and our form often exceeds it, we can't send ?url=parameters directly to EventSource
		// The workaround is to submit using jQuery Form Plugin ($_POST), set a transient, callback EventSource on done ($_GET), pick up where we left off
		// This code is for the $_GET parts:
		$_POST = get_transient( 'pressbooks_current_import_POST' );
		delete_transient( 'pressbooks_current_import_POST' );

		$at_least_one = false;
		if ( isset( $_POST['chapters'] ) ) {
			foreach ( $_POST['chapters'] as $k => $v ) {
				if ( is_array( $v ) && ! empty( $v['import'] ) ) {
					$at_least_one = true;
				}
			}
		}

		if ( ! $at_least_one ) {
			$this->emitOneTimeError( __( 'No chapters were selected for import.', 'pressbooks' ) );
			return;
		}

		$current_import = get_option( 'pressbooks_current_import' );
		if ( is_array( $current_import ) ) {
			Import::preImport();
			$this->emit( Import::doImportGenerator( $current_import ) );
			Import::postImport();
		}

		// Tell the browser to stop reconnecting.
		$this->emitComplete();
		status_header( 204 );

		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit; // Short circuit wp_die(0);
		}
	}

	public function coverGenerator() {
		check_admin_referer( 'pb-generate-cover' );

		if ( empty( current_user_can( 'edit_posts' ) ) ) {
			$this->emitOneTimeError( __( 'You do not have sufficient permissions to access this page.', 'pressbooks' ) );
			return;
		}

		$format = $_GET['format'] ?? '';
		$this->emit( \Pressbooks\Covergenerator\Generator::formGenerator( $format ), true );

		// Tell the browser to stop reconnecting.
		status_header( 204 );

		if ( ! defined( 'WP_TESTS_MULTISITE' ) ) {
			exit; // Short circuit wp_die(0);
		}
	}

}
