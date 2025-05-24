<?php

namespace Pressbooks\Modules\BackgroundProcessing;

use Pressbooks\Modules\Export\Export;
use Pressbooks\Modules\Export\ExportGenerator;

class BackgroundJob {

	/**
	 * Handles the background processing of an export job.
	 * This function is triggered by WP-Cron.
	 *
	 * @param int $job_id The ID of the export job.
	 */
	public static function handle( $job_id ): void {

		set_time_limit( 0 ); // Allow unlimited execution time for this job ⚠️

		/*
		* A more robust way for cron might be to store the blog_id with the cron schedule args,
		* switch to it, then query the job. However, $job->book_id is what we rely on from class-export.php.
		*
		* For now, we assume job_id is unique enough or the initial query is against a global/main site job table
		* IF it were designed that way. BUT, our design is per-site tables. So, the cron *must* run on, or switch to,
		* the correct blog to find the job.
		*
		* The `wp_schedule_single_event` in `Export::exportGenerator` runs in the context of the current blog,
		* so WP-Cron should trigger this hook in the context of that blog, meaning $wpdb->prefix should be correct.
		* If WP-Cron itself runs all hooks under the main site context, then $job->book_id is essential BEFORE the first query.
		* Let's assume cron triggers in the correct blog context due to how it was scheduled.
		*/

		// For safety, to see where cron is running
		$initial_blog_id = get_current_blog_id();

		$job_table_name = $initial_blog_id . '_pressbooks_export_jobs';

		/*
		 * It's safer to get the job by ID and then use its book_id to switch,
		 * especially if job IDs could clash or cron context is unpredictable.
		 * However, to find the job, we need to know which blog's table to query.
		 * The current scheduling in Export::exportGenerator is blog-specific,
		 *, so this function *should* run in that blog's context.
		 */

		// Let's assume $job_id is for the current blog context WP-Cron is running under. */

		$job = app( 'db' )->table( $job_table_name )
			->where( 'id', $job_id )
			->first();

		if ( ! $job ) {
			return;
		}

		// Now we have $job->book_id, ensure we are on the correct blog.
		$switched_blog = false;
		$actual_book_id_for_job = (int) $job->book_id;

		if ( is_multisite() && get_current_blog_id() !== $actual_book_id_for_job ) {
			switch_to_blog( $actual_book_id_for_job );
			$switched_blog = true;
			$job_table_name = $actual_book_id_for_job . '_pressbooks_export_jobs'; // Update table name after switch
		} elseif ( ! is_multisite() && $actual_book_id_for_job !== 1 && get_current_blog_id() === 1 ) {
			// Single site, but a job somehow has a different book_id. This shouldn't happen so bail out. ☠️
			return;
		}

		if ( $job->status !== 'pending' ) {
			if ( $switched_blog ) {
				restore_current_blog();
			}
			return;
		}

		/*
		 * Update the job status to 'processing' and set initial progress
		 * CURRENT PROGRESS 0%: Initializing export
		 */

		$update_processing_data = [
			'status' => 'processing',
			'progress_percentage' => 0,
			'progress_message' => __( 'Initializing export...', 'pressbooks' ),
			'job_started_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		];

		app( 'db' )->table( $job_table_name )
			->where( 'id', $job_id )
			->update( $update_processing_data );

		/*
		 * If the job class doesn't exist, we can't proceed.
		 * Mark the job as failed and log the error.
		 */

		if ( ! class_exists( $job->export_module_classname ) ) {
			$fail_data = [
				'status' => 'failed',
				'progress_message' => sprintf( __( 'Exporter class %s not found.', 'pressbooks' ), $job->export_module_classname ),
				'log_details' => sprintf( __( 'Exporter class %s not found.', 'pressbooks' ), $job->export_module_classname ),
				'job_completed_at' => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			];
			app( 'db' )->table( $job_table_name )
				->where( 'id', $job_id )
				->update( $fail_data );

			if ( $switched_blog ) {
				restore_current_blog();
			}
			return;
		}

		$constructor_args = [];

		if ( ! empty( $job->export_options ) ) {
			$job_options = json_decode( $job->export_options, true );
			if ( is_array( $job_options ) ) {
				$constructor_args = $job_options;
			}
		}

		/** @var Export $exporter */
		$exporter = new $job->export_module_classname( $constructor_args );

		$original_locale_switched = false;
		$locale_to_use = Export::locale();

		if ( $locale_to_use ) {
			$original_locale_switched = switch_to_locale( $locale_to_use );
		}

		/*
		 * Prepare the export
		 * CURRENT PROGRESS 10%: Initializing export
		 */

		$update_prep_data = [
			'progress_percentage' => 10,
			'progress_message' => __( 'Preparing export...', 'pressbooks' ),
			'updated_at' => current_time( 'mysql', true ),
		];

		app( 'db' )->table( $job_table_name )
			->where( 'id', $job_id )
			->update( $update_prep_data );

		$convert_success = false;
		$validate_success = false;
		$error_message = '';
		$output_path = '';
		$log_content = '';

		try {

			/*
			 * Start the conversion process
			 * CURRENT PROGRESS 30%: Converting content generators get called here
			 */

			$update_conv_start_data = [
				'progress_percentage' => 30, // Starting point for conversion messages
				'progress_message' => __( 'Converting content', 'pressbooks' ),
				'updated_at' => current_time( 'mysql', true ),
			];

			app( 'db' )->table( $job_table_name )
				->where( 'id', $job_id )
				->update( $update_conv_start_data );

			// All exporters should extend the ExportGenerator we are processing all exporters the same way
			if ( ! $exporter instanceof ExportGenerator ) {
				throw new \RuntimeException(
					sprintf(
						'Exporter class %s must implement ExporterGeneratorInterface',
						get_class( $exporter )
					)
				);
			}

			//Run pre-export tasks
			Export::preExport();

			$conversion_generator = $exporter->convertGenerator();

			foreach ( $conversion_generator as $progress_key => $progress_value ) {
				$current_progress_percent = is_int( $progress_key ) ? $progress_key : ( isset( $progress_value['progress'] ) ? (int) $progress_value['progress'] : 30 );
				$current_progress_message = is_string( $progress_value ) ? $progress_value : ( $progress_value['message'] ?? __( 'Processing...', 'pressbooks' ) );
				$update_gen_progress_data = [
					'progress_percentage' => $current_progress_percent,
					'progress_message' => $current_progress_message,
					'updated_at' => current_time( 'mysql', true ),
				];
				app( 'db' )->table( $job_table_name )
					->where( 'id', $job_id )
					->update( $update_gen_progress_data );
			}

				// Check generator status
			$convert_success = $conversion_generator->getReturn();

			if ( $convert_success ) {
				$output_path = $exporter->getOutputPath();

				/*
				 * If conversion was successful, validate the output
				 * CURRENT PROGRESS 70%: Validating file
				 */

				app( 'db' )->table( $job_table_name )
					->where( 'id', $job_id )
					->update([
						'progress_percentage' => 70, // Mark start of validation phase
						'progress_message' => __( 'Conversion successful. Validating file...', 'pressbooks' ),
						'output_file_path' => $output_path,
						'updated_at' => current_time( 'mysql', true ),
					]);

				// Handle validation with generator support
					$validation_generator = $exporter->validateGenerator();

				foreach ( $validation_generator as $progress_key => $progress_value ) {
					$current_progress_percent = is_int( $progress_key ) ? $progress_key : ( isset( $progress_value['progress'] ) ? (int) $progress_value['progress'] : 70 );
					$current_progress_message = is_string( $progress_value ) ? $progress_value : ( $progress_value['message'] ?? __( 'Validating...', 'pressbooks' ) );
					app( 'db' )->table( $job_table_name )
						->where( 'id', $job_id )
						->update([
							'progress_percentage' => $current_progress_percent,
							'progress_message' => $current_progress_message,
							'updated_at' => current_time( 'mysql', true ),
						]);
				}

					$validate_success = $validation_generator->getReturn();

				//Run post-export tasks
				Export::postExport();

			}
		} catch ( \Exception $e ) {
			error_log( 'BackgroundJob::handle(Job ID: ' . $job_id . '): Exception during export: ' . $e->getMessage() );
		}

		sleep( 1 );  // Give some time for the DB to update before we check the status to avoid SSE race conditions 🫣

		/*
		 * If validation finished, update the job status to 'completed'
		 * CURRENT PROGRESS 100%: Validating file
		*/

		$final_status_data = [
			'status' => ( $convert_success && $validate_success ) ? 'completed' : 'failed',
			'progress_percentage' => ( $convert_success ) ? 100 : ( $job->progress_percentage ?: 100 ),
			'output_file_path' => $convert_success ? $output_path : null,
			'job_completed_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		];

		if ( $convert_success && $validate_success ) {
			$final_status_data['progress_message'] = __( 'Export completed successfully.', 'pressbooks' );
			$final_status_data['log_details'] = $log_content ?: 'Successfully completed.';
		} elseif ( $convert_success ) {
			$final_status_data['progress_message'] = __( 'Export completed with validation errors.', 'pressbooks' );
			$final_status_data['log_details'] = $log_content ?: 'Export completed with validation errors.';
		} else {
			$final_status_data['progress_message'] = $error_message ?: __( 'Export failed due to an unspecified error.', 'pressbooks' );
			$final_status_data['log_details'] = $log_content ?: ( $error_message ?: 'Failed without specific error message.' );
		}

		app( 'db' )->table( $job_table_name )
			->where( 'id', $job_id )
			->update( $final_status_data );

		delete_transient( 'dirsize_cache' );

		if ( $original_locale_switched ) {
			restore_current_locale();
		}

		if ( $switched_blog ) {
			restore_current_blog();
		}
	}

	/**
	 * Creates the export jobs table if it doesn't exist.
	 * This function is called on plugin activation.
	 */
	public static function ensureExportsTable(): void {

		$table_name = get_current_blog_id() . '_pressbooks_export_jobs';

		if ( ! app( 'db' )->schema()->hasTable( $table_name ) ) {
			self::createJobTable();
		}
	}

	/**
	 * Creates the export jobs table if it doesn't exist.
	 * This function is called on plugin activation.
	 */
	public static function createJobTable(): void {
		$table_name = get_current_blog_id() . '_pressbooks_export_jobs';
		if ( ! app( 'db' )->getSchemaBuilder()->hasTable( $table_name ) ) {
			app( 'db' )->getSchemaBuilder()->create( $table_name, function ( $table ) {
				$table->bigIncrements( 'id' );
				$table->bigInteger( 'book_id' )->unsigned();
				$table->bigInteger( 'user_id' )->unsigned();
				$table->string( 'export_format', 50 );
				$table->string( 'export_module_classname', 255 );
				$table->longText( 'export_options' )->nullable();
				$table->string( 'status', 20 )->default( 'pending' );
				$table->integer( 'progress_percentage' )->default( 0 );
				$table->text( 'progress_message' )->nullable();
				$table->string( 'output_file_path', 255 )->nullable();
				$table->longText( 'log_details' )->nullable();
				$table->dateTime( 'job_started_at' )->nullable();
				$table->dateTime( 'job_completed_at' )->nullable();
				$table->timestamp( 'created_at' )->useCurrent();
				$table->timestamp( 'updated_at' )->useCurrent()->useCurrentOnUpdate();
				$table->primary( 'id' );
				$table->index( 'book_id' );
				$table->index( 'user_id' );
				$table->index( 'status' );
				$table->index( 'created_at' );
			});
		}
	}

	/**
	 * Safely output Server-Sent Events (SSE) data with proper escaping
	 *
	 * @param array $data The data to send as JSON
	 * @param string $event_type The SSE event type (default: 'export_progress')
	 * @param int|null $id Optional custom ID (defaults to current timestamp)
	 * @return void
	 */
	public static function sendSecureSSE( array $data, string $event_type = 'export_progress', int $id = null ): void {

		$event_type = preg_replace( '/[^a-zA-Z0-9_-]/', '', $event_type );
		if ( empty( $event_type ) ) {
			$event_type = 'message';
		}

		$event_id = $id ? absint( $id ) : absint( time() );

		$json_data = wp_json_encode( $data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

		if ( false === $json_data ) {
			$json_data = wp_json_encode( [ 'error' => 'Failed to encode data' ] );
		}

		// Additional safety: remove any potential newlines that could break an SSE format
		$json_data = str_replace( [ "\n", "\r", "\0" ], '', $json_data );

		// Output the SSE event
		echo 'event: ' . esc_attr( $event_type ) . "\n";
		echo 'id: ' . $event_id . "\n";
		echo 'data: ' . $json_data . "\n\n";

		// Flush output immediately for SSE
		if ( ob_get_level() ) {
			ob_flush();
		}
		flush();
	}

	/**
	 * AJAX handler for checking existing export jobs.
	 */
	public static function checkExistingJobs(): void {
		check_ajax_referer( 'pb-export-book', '_wpnonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'pressbooks' ) ], 403 );
			return;
		}

		self::ensureExportsTable();

		$book_id = get_current_blog_id();
		$user_id = get_current_user_id();

		$table_name = $book_id . '_pressbooks_export_jobs';

		$jobs = app( 'db' )->table( $table_name )
			->select([
				'id as job_id',
				'book_id',
				'export_format as module_slug',
				'status',
				'progress_percentage',
				'progress_message',
			])
			->where( 'book_id', $book_id )
			->where( 'user_id', $user_id )
			->whereNotIn( 'status', [ 'completed', 'failed', 'cancelled' ] )
			->orderBy( 'created_at', 'DESC' )
			->get();

		if ( $jobs->isNotEmpty() ) {
			foreach ( $jobs as $job ) {
				$job->sse_nonce = wp_create_nonce( 'pressbooks_export_status_' . $job->job_id );
			}
		}

		wp_send_json_success( [ 'jobs' => $jobs ] );
	}

	/**
	 * Streams the status of all active export jobs for the current user via SSE.
	 * This provides a single connection point for the client to receive updates
	 * for multiple concurrent export jobs.
	 */
	public static function stream_all_user_jobs_status(): void {

		// Security check
		//check_ajax_referer( 'pressbooks_user_export_feed_nonce', 'nonce' );

		// error_log("SSE Stream: Nonce check passed."); // DEBUG

		// Basic headers for SSE
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' ); // For Nginx

		// Disable output buffering for PHP
		if ( ob_get_level() > 0 ) {
			for ( $i = 0; $i < ob_get_level(); $i++ ) {
				ob_end_flush();
			}
		}
		@ini_set( 'output_buffering', 'Off' );
		@ini_set( 'zlib.output_compression', 0 );
		@ini_set( 'implicit_flush', 1 );
		ob_implicit_flush( true );

		$current_user_id = get_current_user_id();
		$book_id         = filter_input( INPUT_GET, 'book_id', FILTER_VALIDATE_INT );

		if ( ! $current_user_id || ! $book_id ) {
			self::sendSecureSSE( [ 'error' => 'Missing user or book ID.' ], 'error' );
			// error_log("SSE Stream: Missing user or book ID. Exiting."); // DEBUG
			exit;
		}

		$switched = false;
		if ( is_multisite() && get_current_blog_id() !== $book_id ) {
			switch_to_blog( $book_id );
			$switched = true;
		}

		self::ensureExportsTable();

		if ( ! current_user_can( 'edit_posts' ) ) {
			status_header( 403 );
			self::sendSecureSSE(
				data: [
					'message' => __( 'Permission denied to view job status for this book.', 'pressbooks' ),
				],
				event_type: 'error'
			);
			if ( $switched ) {
				restore_current_blog();
			}
			wp_die();
		}

		set_time_limit( 0 );

		$table_name = $book_id . '_pressbooks_export_jobs';
		$last_sent_statuses = [];

		while ( true ) {
			$db = app( 'db' );

			if ( connection_status() !== CONNECTION_NORMAL || connection_aborted() ) {
				break; // Client disconnected
			}

			$active_jobs = $db->table( $table_name )
				->where( 'user_id', $current_user_id )
				->where( 'book_id', $book_id )
				->whereIn( 'status', [ 'pending', 'processing', 'running', 'completed' ] )
				->orderBy( 'created_at', 'DESC' )
				->where( 'updated_at', '>=', date( 'Y-m-d H:i:s', strtotime( '-1 minutes' ) ) ) // Only jobs updated in the last minute
				->get();

			if ( ! $active_jobs->isEmpty() ) {
				foreach ( $active_jobs as $job ) {
					$current_job_state_for_comparison = clone $job;
					unset( $current_job_state_for_comparison->updated_at );
					$current_job_state_json = wp_json_encode( $current_job_state_for_comparison );

					$should_send = ! isset( $last_sent_statuses[ $job->id ] ) || $last_sent_statuses[ $job->id ] !== $current_job_state_json;

					if ( $should_send ) {
						$job_data_to_send = [
							'job_id' => $job->id,
							'book_id' => $job->book_id,
							'status' => $job->status,
							'progress_percentage' => $job->progress_percentage,
							'progress_message' => $job->progress_message,
							'format_name' => Export::getFriendlyNameForModule( $job->export_module_classname ), // Get user-friendly name
							'module_slug' => $job->export_format,
							'file_name' => null,
							'download_url' => null,
							'error_message' => null,
						];

						if ( $job->status === 'completed' ) {
							$job_data_to_send['file_name'] = basename( $job->output_file_path );
							$download_nonce = wp_create_nonce( 'download_export_job_' . $job->id );
							$job_data_to_send['download_url'] = admin_url( 'admin.php?page=pb_export&download_export_file=' . basename( $job->output_file_path ) . '&job_id=' . $job->id . '&_wpnonce=' . $download_nonce );
							$job_data_to_send['file_size'] = file_exists( $job->output_file_path ) ? size_format( filesize( $job->output_file_path ), 2 ) : '';
						} elseif ( $job->status === 'failed' ) {
							$log_details = json_decode( $job->log_details, true );
							if ( is_array( $log_details ) && isset( $log_details['error'] ) ) {
								$job_data_to_send['error_message'] = $log_details['error'];
							} elseif ( is_string( $log_details ) ) { // Fallback if log_details is just a string
								$job_data_to_send['error_message'] = $log_details;
							} else {
								$job_data_to_send['error_message'] = $job->progress_message;
							}
						}
						self::sendSecureSSE( $job_data_to_send, 'export_job_update', $job->id );
						$last_sent_statuses[ $job->id ] = $current_job_state_json;
					}
				}
			}

			if ( ob_get_level() > 0 ) {
				ob_flush();
			}
			flush();
		}
		exit;
	}
}
