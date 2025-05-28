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
use Pressbooks\Modules\BackgroundProcessing\BackgroundJob;
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
	public static function init() {
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
		add_action( 'wp_ajax_import-book', [ $obj, 'importBook' ] );
		add_action( 'wp_ajax_cover-generator', [ $obj, 'coverGenerator' ] );
		add_action( 'wp_ajax_pb_sse_exports', [ $obj, 'ajaxStreamUserJobStatuses' ] );
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
	private function emitMessage( $data, string $event_type = 'message', ?int $id = null ): void {
		$msg = 'event: ' . esc_attr( $event_type ) . "\n";
		if ( null !== $id ) {
			$msg .= 'id: ' . absint( $id ) . "\n";
		}
		$json_data = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $json_data ) {
			$json_data = wp_json_encode( [ 'error' => 'Failed to encode data' ] );
		}
		// Additional safety: remove any potential newlines that could break an SSE format
		$json_data = str_replace( [ "\n", "\r", "\0" ], '', $json_data );

		$msg .= 'data: ' . $json_data . "\n\n";
		// TODO: Review if this padding is still needed or if it should be part of setupHeaders only.
		// For now, keeping it here as per original sendSecureSSE which seemed to imply per-message needs.
		// However, the original emitMessage in this class had similar padding in setupHeaders.
		// $msg .= ':' . str_repeat( ' ', 2048 ) . "\n\n"; // Original emitMessage had this in setupHeaders

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
	 * This method now calls Export::processAndQueueJobRequests to handle job queuing,
	 * then sends initial SSE feedback, and finally starts streaming all job statuses.
	 */
	public function exportBook() {
		// Nonce check for this AJAX action (exportBook)
		check_admin_referer( 'pb-export' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			$this->emitOneTimeError( __( 'Permission denied.', 'pressbooks' ) );
			exit;
		}

		// Prepare inputs from $_GET for the queuing method
		// processAndQueueJobRequests expects an associative array for formats.
		$export_formats_from_get = isset( $_GET['export_formats'] ) && is_array( $_GET['export_formats'] ) ? $_GET['export_formats'] : [];

		// Ensure $export_formats_from_get is associative [slug => value] for processAndQueueJobRequests
		// If it comes as a simple indexed array of slugs, convert it.
		// Based on original code, it was array_keys(array_map('sanitize_text_field', $export_formats_from_get))
		// which implies the keys are what matter.
		$sanitized_export_formats_for_queuing = [];
		foreach ( $export_formats_from_get as $key => $value ) {
			// If $value is the slug (e.g. from a GET request like ?export_formats[]=pdf&export_formats[]=epub)
			// and $key is numeric, use $value as the slug for the associative array.
			// If $key is already the slug (e.g. ?export_formats[pdf]=1), then $key is the slug.
			$slug = is_numeric( $key ) ? sanitize_text_field( $value ) : sanitize_text_field( $key );
			$sanitized_export_formats_for_queuing[ $slug ] = $slug; // Value can just be the slug itself
		}

		if ( empty( $sanitized_export_formats_for_queuing ) ) {
			$this->emitOneTimeError( __( 'No export format was selected.', 'pressbooks' ) );
			exit;
		}

		$export_options_from_get = isset( $_GET['export_options'] ) && is_array( $_GET['export_options'] ) ? $_GET['export_options'] : [];
		// Consider if $export_options_from_get needs deeper sanitization based on its expected structure.

		// Setup SSE headers before calling the job queuing, so we can send initial feedback.
		$this->setupHeaders();

		// Call the centralized job processing and queuing method from the Export class
		$queuing_results = Export::processAndQueueJobRequests( $sanitized_export_formats_for_queuing, $export_options_from_get );

		// Send initial SSE messages based on the queuing results
		if ( ! empty( $queuing_results ) ) {
			foreach ( $queuing_results as $result ) {
				$event_type = 'info'; // Default event type for SSE messages
				$sse_data = [
					'action' => $result['event_type'], // e.g., 'job_queued', 'job_queue_failed', 'job_type_skipped'
					'message' => $result['message'],
					'module_slug' => $result['module_slug'] ?? null,
					'format_name' => $result['format_name'] ?? null,
				];

				if ( isset( $result['job_id'] ) ) {
					$sse_data['job_id'] = $result['job_id'];
				}

				if ( isset( $result['status'] ) ) {
					if ( $result['status'] === 'error' ) {
						$event_type = 'error';
					} elseif ( $result['status'] === 'success' && $result['event_type'] === 'job_queued' ) {
						$event_type = 'export_job_update'; // Match event type used by streamUserJobStatuses for consistency
					}
				}
				$this->emitMessage( $sse_data, $event_type, $result['job_id'] ?? null );
			}
		}

		// After attempting to queue all selected jobs and sending initial feedback,
		// start the continuous status stream for all user jobs.
		$current_book_id = get_current_blog_id();
		$current_user_id = get_current_user_id();
		$this->streamUserJobStatuses( $current_book_id, $current_user_id );

		// streamUserJobStatuses has an infinite loop. Exit explicitly if it ever returns.
		exit;
	}

	/**
	 * Streams the status of all active export jobs for a given user and book via SSE.
	 * This provides a single connection point for the client to receive updates
	 * for multiple concurrent export jobs.
	 *
	 * @param int $book_id
	 * @param int $user_id
	 */
	public function streamUserJobStatuses( int $book_id, int $user_id ): void {
		// Headers are likely already sent by a precursor emit() call from exportBook,
		// but if this method were to be called directly via a new AJAX action,
		// we might need to call $this->setupHeaders(); Ensure it's idempotent or called conditionally.
		// For now, assuming headers are managed by the calling context (like exportBook's initial emit).

		// Disable output buffering for PHP - this should ideally be in setupHeaders or managed once.
		if ( ob_get_level() > 0 ) {
			for ( $i = 0; $i < ob_get_level(); $i++ ) {
				ob_end_flush();
			}
		}
		@ini_set( 'output_buffering', 'Off' );
		@ini_set( 'zlib.output_compression', 0 );
		@ini_set( 'implicit_flush', 1 );
		ob_implicit_flush( true );

		if ( ! $user_id || ! $book_id ) {
			$this->emitMessage( [ 'error' => 'Missing user or book ID.' ], 'error' );
			exit;
		}

		$switched = false;
		if ( is_multisite() && get_current_blog_id() !== $book_id ) {
			switch_to_blog( $book_id );
			$switched = true;
		}

		// Use BackgroundJob::ensureExportsTable statically as it's a static method.
		BackgroundJob::ensureExportsTable();

		if ( ! current_user_can( 'edit_posts' ) ) {
			// Consider if status_header(403) is appropriate here if headers are already sent.
			// If called from exportBook, headers are text/event-stream 200 OK.
			// Sending a 403 might break the SSE client. An error event is safer.
			$this->emitMessage(
				data: [
					'message' => __( 'Permission denied to view job status for this book.', 'pressbooks' ),
				],
				event_type: 'error'
			);
			if ( $switched ) {
				restore_current_blog();
			}
			flush(); // Ensure message is sent before exit
			exit; // Exit after sending permission error
		}

		set_time_limit( 0 ); // Already set in setupHeaders, but good for standalone.

		$table_name = $book_id . '_pressbooks_export_jobs';
		$last_sent_statuses = [];

		while ( true ) {
			$db = app( 'db' );

			if ( connection_status() !== CONNECTION_NORMAL || connection_aborted() ) {
				break; // Client disconnected
			}

			// Fetch jobs that are active or recently completed/failed to ensure client gets final status.
			// The original query also included 'completed', let's ensure we handle its lifecycle.
			// Consider if 'failed', 'canceled' also need to be streamed for a short period.
			// The original query: whereIn( 'status', [ 'pending', 'processing', 'running', 'completed' ] )
			// and where( 'updated_at', '>=', date( 'Y-m-d H:i:s', strtotime( '-1 minutes' ) ) )
			// This means completed jobs are only sent if updated in the last minute.

			$active_jobs = $db->table( $table_name )
				->where( 'user_id', $user_id )
				->where( 'book_id', $book_id )
				// Fetch all states that a client might care about if recently updated.
				->whereIn( 'status', [ 'pending', 'processing', 'completed', 'failed' ] )
				->orderBy( 'created_at', 'DESC' )
				// Optimization: Only fetch jobs updated recently or those not yet in a final state for the client.
				// This needs careful handling to ensure final states are not missed.
				// For now, let's stick to a time window for updates to avoid sending old, unchanged completed/failed jobs forever.
				->where(function ( $query ) use ( $last_sent_statuses ) {
					$query->where( 'updated_at', '>=', gmdate( 'Y-m-d H:i:s', strtotime( '-1 minutes' ) ) ) // Increased window slightly
						->orWhereNotIn( 'status', [ 'completed', 'failed', 'cancelled' ] ); // Or if it's still an active job
				})
				->get();

			if ( ! $active_jobs->isEmpty() ) {
				foreach ( $active_jobs as $job ) {
					$current_job_state_for_comparison = clone $job;
					$current_job_state_json = wp_json_encode( $current_job_state_for_comparison );

					$should_send = ! isset( $last_sent_statuses[ $job->id ] ) || $last_sent_statuses[ $job->id ] !== $current_job_state_json;

					if ( $should_send ) {
						$job_data_to_send = [
							'job_id' => $job->id,
							'book_id' => (int) $job->book_id,
							'status' => $job->status,
							'progress_percentage' => (int) $job->progress_percentage,
							'progress_message' => $job->progress_message,
							'format_name' => Export::getFriendlyNameForModule( $job->export_module_classname ),
							'module_slug' => $job->export_format,
							'file_name' => null,
							'download_url' => null,
							'error_message' => null,
						];
						$this->emitMessage( $job_data_to_send, 'export_job_update', $job->id );
						$last_sent_statuses[ $job->id ] = $current_job_state_json;
					}
				}

				// If all fetched jobs are in a terminal state and have been sent,
				// we might consider closing the stream if no other activity is expected.
				// However, new jobs can be initiated, so the stream should likely stay open
				// until client disconnects or a specific 'close' signal is implemented.
				// For now, rely on client disconnect or connection_aborted().
			} else {
				// No active jobs currently matching criteria for this user/book.
				// Send a keep-alive comment if needed, or just wait.
				// SSE specifications suggest sending a comment (line starting with ':') periodically
				// to prevent proxies from closing the connection.
				echo ": keepalive\n\n";
			}

			// Flush output buffer
			if ( ob_get_level() > 0 ) {
				ob_flush();
			}
			flush();

			// Wait before checking again
			sleep( apply_filters( 'pb_sse_export_job_update_interval', 1 ) ); // 1 second interval
		}

		if ( $switched ) {
			restore_current_blog();
		}
	}

	/**
	 * AJAX handler for streaming user job statuses.
	 * This is intended to be hooked to wp_ajax_pb_sse_exports.
	 */
	public function ajaxStreamUserJobStatuses(): void {
		// TODO: Add nonce check if this endpoint is directly exposed via AJAX.
		// check_ajax_referer( 'pressbooks_user_export_feed_nonce', 'nonce' ); // Example, use appropriate nonce

		$book_id = filter_input( INPUT_GET, 'book_id', FILTER_VALIDATE_INT );
		$user_id = get_current_user_id();

		if ( ! $book_id ) {
			// Send an immediate error if book_id is missing, as streamUserJobStatuses expects it.
			// Setup headers first to ensure client can receive SSE.
			$this->setupHeaders(); // Ensure headers are set for SSE response.
			$this->emitMessage( [ 'error' => 'Missing book ID for job status stream.' ], 'error' );
			flush();
			exit;
		}

		// Setup SSE headers. setupHeaders() should be idempotent or safe to call multiple times.
		$this->setupHeaders();
		$this->streamUserJobStatuses( $book_id, $user_id );
		// streamUserJobStatuses has its own exit conditions (client disconnect, error).
		exit; // Ensure script termination after stream ends or if it exits early.
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
