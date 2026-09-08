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

use Pressbooks\Modules\BackgroundProcessing\BackgroundJob;
use Pressbooks\Modules\Import\Import;
use function Pressbooks\Modules\Export\get_friendly_name_for_module;

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
	public static function hooks( EventStreams $obj ) {
		add_action( 'wp_ajax_import-book', [ $obj, 'importBook' ] );
		add_action( 'wp_ajax_cover-generator', [ $obj, 'coverGenerator' ] );
		add_action( 'wp_ajax_pb_sse_exports', [ $obj, 'ajaxStreamUserExportsJobs' ] );
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
	private function emitMessage( $data, string $event_type = 'message' ): void {
		$msg = 'event: ' . esc_attr( $event_type ) . "\n";

		$json_data = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		if ( false === $json_data ) {
			$json_data = wp_json_encode( [ 'error' => 'Failed to encode data' ] );
		}
		$json_data = str_replace( [ "\n", "\r", "\0" ], '', $json_data );

		$msg .= 'data: ' . $json_data . "\n\n";
		if ( ob_get_level() ) {
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
	 * Streams the status of all active export jobs for a given user and book via SSE.
	 * This provides a single connection point for the client to receive updates
	 * for multiple concurrent export jobs.
	 *
	 * @param int $book_id
	 * @param int $user_id
	 */
	public function streamUserJobStatuses( int $book_id, int $user_id ): void {

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

		BackgroundJob::ensureExportsTable();

		if ( ! current_user_can( 'edit_posts' ) ) {
			$this->emitMessage(
				data: [
					'message' => __( 'Permission denied to view job status for this book.', 'pressbooks' ),
				],
				event_type: 'error'
			);
			if ( $switched ) {
				restore_current_blog();
			}
			flush();
			exit;
		}

		set_time_limit( 0 );

		$last_sent_statuses = [];

		while ( true ) {
			$db = app( 'db' );

			if ( connection_status() !== CONNECTION_NORMAL || connection_aborted() ) {
				break; // Client disconnected
			}

			$active_jobs = $db->table( BackgroundJob::JOBS_TABLE_NAME )
				->where( 'user_id', $user_id )
				->where( 'book_id', $book_id )
				->where(function ( $query ) {
					$query->whereIn( 'status', [ 'pending', 'processing', 'completed' ] )
						// Include jobs that are 'failed' but were updated in the last minute to display recent errors
						->orWhere(function ( $sub ) {
							$sub->where( 'status', 'failed' )
								->where( 'updated_at', '>=', date( 'Y-m-d H:i:s', strtotime( '-1 minute' ) ) );
						});
				})
				->orderBy( 'created_at', 'DESC' )
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
							'format_name' => get_friendly_name_for_module( $job->export_module_classname ),
							'module_slug' => $job->export_format,
							'file_name' => null,
							'download_url' => null,
							'error_message' => null,
						];
						$jobs_to_send[] = $job_data_to_send;
						$last_sent_statuses[ $job->id ] = $current_job_state_json;
					}
					if ( $job->status === 'completed' ) {
						app( 'db' )->table( BackgroundJob::JOBS_TABLE_NAME )
							->where( 'id', $job->id )
							->update( [ 'status' => 'done' ] );
					}
				}

				if ( ! empty( $jobs_to_send ) ) {
					$this->emitMessage( $jobs_to_send, 'export_job_updates' );
				}
			} else {
				echo ": keepalive\n\n";
			}

			if ( ob_get_level() > 0 ) {
				ob_flush();
			}
			flush();
			sleep( apply_filters( 'pb_sse_export_job_update_interval', 1 ) );
		}

		if ( $switched ) {
			restore_current_blog();
		}
	}

	/**
	 * AJAX handler for streaming user job statuses.
	 * This is intended to be hooked to wp_ajax_pb_sse_exports.
	 */
	public function ajaxStreamUserExportsJobs(): void {

		check_ajax_referer( 'pressbooks_user_export_feed', 'nonce' );

		$book_id = filter_input( INPUT_GET, 'book_id', FILTER_VALIDATE_INT );
		$user_id = get_current_user_id();

		if ( ! $book_id ) {
			$this->setupHeaders();
			$this->emitMessage( [ 'error' => 'Missing book ID for job status stream.' ], 'error' );
			flush();
			exit;
		}

		$this->setupHeaders();
		$this->streamUserJobStatuses( $book_id, $user_id );
		exit;
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
