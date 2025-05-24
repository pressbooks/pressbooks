<?php

namespace Pressbooks\Modules\BackgroundProcessing;

use Pressbooks\Modules\Export\Export;

class BackgroundJob {

	/**
	 * Handles the background processing of an export job.
	 * This function is triggered by WP-Cron.
	 *
	 * @param int $job_id The ID of the export job.
	 */
	public static function handle( $job_id ): void {
		error_log("BackgroundJob::handle(Job ID: {$job_id}): STARTING. Initial blog ID: " . get_current_blog_id()); // DETAILED DEBUG

		set_time_limit( 0 ); // Allow unlimited execution time for this job ⚠️

		/*
		* Note: $wpdb->prefix will be determined by the blog context AFTER switch_to_blog.
		* Fetch job details first using the global $wpdb which might be on the wrong site initially if cron runs from main site.
		*
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

		// For initial log before a potential switch
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
		$actual_book_id_for_job = (int) $job->book_id; // Store before potential switch for logging

		if ( is_multisite() && get_current_blog_id() !== $actual_book_id_for_job ) {
			error_log("BackgroundJob::handle(Job ID: {$job_id}): Mismatch current blog (" . get_current_blog_id() . ") and job's book_id ({$actual_book_id_for_job}). Switching blog."); // DETAILED DEBUG
			switch_to_blog( $actual_book_id_for_job );
			$switched_blog = true;
			$job_table_name = $actual_book_id_for_job . '_pressbooks_export_jobs'; // Update table name after switch
			error_log("BackgroundJob::handle(Job ID: {$job_id}): Switched to blog {$actual_book_id_for_job}. Current blog now: " . get_current_blog_id() . ". Table name: {$job_table_name}"); // DETAILED DEBUG
		} elseif ( ! is_multisite() && $actual_book_id_for_job !== 1 && get_current_blog_id() === 1 ) {
			// Single site, but a job somehow has a different book_id. This shouldn't happen.
			error_log("BackgroundJob::handle(Job ID: {$job_id}): ERROR - Single site, but job book_id ({$actual_book_id_for_job}) is not 1. Current blog: " . get_current_blog_id() . ". Aborting."); // DETAILED DEBUG
			return;
		}

		if ( $job->status !== 'pending' ) {
			error_log("BackgroundJob::handle(Job ID: {$job_id}): Job status is '{$job->status}', not 'pending'. Aborting. Current blog: " . get_current_blog_id()); // DETAILED DEBUG
			if ( $switched_blog ) {
				restore_current_blog();
			}
			return;
		}

		// Mark as processing
		$update_processing_data = [
			'status' => 'processing',
			'progress_percentage' => 0,
			'progress_message' => __( 'Initializing export...', 'pressbooks' ),
			'job_started_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		];
        try {
            $update_result = app( 'db' )->table( $job_table_name )
                ->where( 'id', $job_id )
                ->update($update_processing_data);
        }catch ( \Exception $e ) {
            error_log("BackgroundJob::handle(Job ID: {$job_id}): EXCEPTION CAUGHT during DB update to 'processing'. Message: " . $e->getMessage() . " Current blog: " . get_current_blog_id()); // DETAILED DEBUG
            $update_result = false;
        }

        error_log("BackgroundJob::handle(Job ID: {$job_id}): DB updated to status 'processing', progress 0%. Result: " . ($update_result ? 'Success (rows: ' . $update_result . ')' : 'Failure') . ". Current blog: " . get_current_blog_id()); // DETAILED DEBUG

		if ( ! class_exists( $job->export_module_classname ) ) {
			$fail_data = [
				'status' => 'failed',
				'progress_message' => sprintf( __( 'Exporter class %s not found.', 'pressbooks' ), $job->export_module_classname ),
				'log_details' => sprintf( __( 'Exporter class %s not found.', 'pressbooks' ), $job->export_module_classname ),
				'job_completed_at' => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			];
			$update_result_fail = app( 'db' )->table( $job_table_name )
				->where( 'id', $job_id )
				->update($fail_data);
			error_log("BackgroundJob::handle(Job ID: {$job_id}): Exporter class {$job->export_module_classname} not found. DB updated to status 'failed'. Result: " . ($update_result_fail ? 'Success (rows: ' . $update_result_fail . ')' : 'Failure') . ". Current blog: " . get_current_blog_id()); // DETAILED DEBUG
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

		$update_prep_data = [
			'progress_percentage' => 10,
			'progress_message' => __( 'Preparing export...', 'pressbooks' ),
			'updated_at' => current_time( 'mysql', true ),
		];
		$update_result_prep = app( 'db' )->table( $job_table_name )
			->where( 'id', $job_id )
			->update($update_prep_data);
		error_log("BackgroundJob::handle(Job ID: {$job_id}): DB updated to progress 10% ('Preparing export...'). Result: " . ($update_result_prep ? 'Success (rows: ' . $update_result_prep . ')' : 'Failure') . ". Current blog: " . get_current_blog_id()); // DETAILED DEBUG

		$convert_success = false;
		$validate_success = false;
		$error_message = '';
		$output_path = '';
		$log_content = '';

		try {
			error_log("BackgroundJob::handle() [Job ID: {$job_id}] - Entering TRY block. About to check for convertGenerator. Current blog: " . get_current_blog_id()); // DEBUG + Current Blog
			error_log("BackgroundJob::handle() [Job ID: {$job_id}] - Exporter classname: {$job->export_module_classname}"); // DEBUG

			// Set initial progress for the conversion phase
			$update_conv_start_data = [
				'progress_percentage' => 30, // Starting point for conversion messages
				'progress_message' => __( 'Converting content (this may take a while, you can leave this page and return back later)...', 'pressbooks' ),
				'updated_at' => current_time( 'mysql', true ),
			];
			$update_result_conv_start = app( 'db' )->table( $job_table_name )
				->where( 'id', $job_id )
				->update($update_conv_start_data);
			error_log("BackgroundJob::handle(Job ID: {$job_id}): DB updated to progress 30% ('Converting content...'). Result: " . ($update_result_conv_start ? 'Success (rows: ' . $update_result_conv_start . ')' : 'Failure') . ". Current blog: " . get_current_blog_id()); // DETAILED DEBUG

			if ( method_exists( $exporter, 'convertGenerator' ) ) {
				error_log("BackgroundJob::handle() [Job ID: {$job_id}] - convertGenerator method EXISTS for {$job->export_module_classname}. Calling it now. Current blog: " . get_current_blog_id()); // DEBUG + Current Blog
				$conversion_generator = $exporter->convertGenerator();
				foreach ( $conversion_generator as $progress_key => $progress_value ) {
					error_log("BackgroundJob::handle() [Job ID: {$job_id}] - convertGenerator progress_key: " . print_r($progress_key, true) . " value: " . print_r($progress_value, true) ); // DEBUG
					$current_progress_percent = is_int($progress_key) ? $progress_key : (isset($progress_value['progress']) ? (int)$progress_value['progress'] : 30);
					$current_progress_message = is_string($progress_value) ? $progress_value : (isset($progress_value['message']) ? $progress_value['message'] : __( 'Processing...', 'pressbooks' ) );
					$update_gen_progress_data = [
						'progress_percentage' => $current_progress_percent,
						'progress_message' => $current_progress_message,
						'updated_at' => current_time( 'mysql', true ),
					];
					$update_result_gen_progress = app( 'db' )->table( $job_table_name )
						->where( 'id', $job_id )
						->update($update_gen_progress_data);
					// No detailed log here to avoid flooding if generator yields many small steps
				}
				$convert_success = true;
				error_log("BackgroundJob::handle() [Job ID: {$job_id}] - convertGenerator loop FINISHED for {$job->export_module_classname}. convert_success: true. Current blog: " . get_current_blog_id()); // DEBUG + Current Blog
			}

			error_log("BackgroundJob::handle() [Job ID: {$job_id}] - After conversion attempt, convert_success: " . ($convert_success ? 'true' : 'false')); // DEBUG
			error_log("BackgroundJob::handle(Job ID: {$job_id}): After conversion attempt, convert_success: " . ($convert_success ? 'true' : 'false') . ". Current blog: " . get_current_blog_id()); // DETAILED DEBUG

			if ( $convert_success ) {
				error_log("BackgroundJob::handle(Job ID: {$job_id}): Conversion success. Getting output path. Current blog: " . get_current_blog_id()); // DETAILED DEBUG
				$output_path = $exporter->getOutputPath();
				error_log("BackgroundJob::handle(Job ID: {$job_id}): Output path: " . print_r($output_path, true) . ". Current blog: " . get_current_blog_id()); // DETAILED DEBUG

				$update_to_70_data = [
					'progress_percentage' => 70, // Mark start of validation phase
					'progress_message' => __( 'Conversion successful. Validating file...', 'pressbooks' ),
					'output_file_path' => $output_path,
					'updated_at' => current_time( 'mysql', true ),
				];
				$update_to_70_result = app( 'db' )->table( $job_table_name )
					->where( 'id', $job_id )
					->update($update_to_70_data);

                error_log("BackgroundJob::handle(Job ID: {$job_id}): DB updated to progress 70% ('Validating file...'). Result: " . ($update_to_70_result ? 'Success (rows: ' . $update_to_70_result . ')' : 'Failure') . ". Current blog: " . get_current_blog_id()); // DETAILED DEBUG

				// Handle validation with generator support
				if ( method_exists( $exporter, 'validateGenerator' ) ) {
					$validation_generator = $exporter->validateGenerator();
					foreach ( $validation_generator as $progress_key => $progress_value ) {
						$current_progress_percent = is_int($progress_key) ? $progress_key : (isset($progress_value['progress']) ? (int)$progress_value['progress'] : 70);
						$current_progress_message = is_string($progress_value) ? $progress_value : ($progress_value['message'] ?? __('Validating...', 'pressbooks'));
						$update_val_gen_data = [
							'progress_percentage' => $current_progress_percent,
							'progress_message' => $current_progress_message,
							'updated_at' => current_time( 'mysql', true ),
						];
						app( 'db' )->table( $job_table_name )
							->where( 'id', $job_id )
							->update($update_val_gen_data);
					}
					$validate_success = true;
				}
			}
		} catch ( \Exception $e ) {
			$error_message = $error_message ?: $e->getMessage();
			$log_content .= ( ! empty( $log_content ) ? "\n" : '' ) . 'Exception encountered: ' . $e->getMessage() . "\nTrace:\n" . $e->getTraceAsString();
			error_log("BackgroundJob::handle(Job ID: {$job_id}): EXCEPTION CAUGHT. Message: " . $e->getMessage() . " Trace: " . $e->getTraceAsString() . " Current blog: " . get_current_blog_id()); // DETAILED DEBUG
		}

		sleep( 1 );  // Give some time for the database to update before checking the status

		$final_status = ''; // For logging

		if ( $convert_success && $validate_success ) {
			$final_status = 'completed';
			$final_status_data = [
				'status' => 'completed',
				'progress_percentage' => 100,
				'progress_message' => __( 'Export completed successfully.', 'pressbooks' ),
				'output_file_path' => $output_path,
				'log_details' => $log_content ?: 'Successfully completed.',
				'job_completed_at' => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			];
			error_log("BackgroundJob::handle(Job ID: {$job_id}): Job determined as COMPLETED. Output: {$output_path}. Current blog: " . get_current_blog_id()); // DETAILED DEBUG
		} else if($convert_success && !$validate_success) {
            $final_status_data = [
                'status' => 'completed',
                'progress_percentage' => 100,
                'progress_message' => __( 'Export completed with validation errors.', 'pressbooks' ),
                'log_details' => $log_content ?: 'Export completed with validation errors.',
                'output_file_path' => $output_path,
                'job_completed_at' => current_time( 'mysql', true ),
                'updated_at' => current_time( 'mysql', true ),
            ];
        }else {
			$final_status = 'failed';
			$final_status_data = [
				'status' => 'completed',
				'progress_percentage' => $job->progress_percentage ?: 100, // Keep last known progress
				'progress_message' => $error_message ?: __( 'Export failed due to an unspecified error.', 'pressbooks' ),
				'log_details' => $log_content ?: ($error_message ?: 'Failed without specific error message.'),
				'job_completed_at' => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			];
			error_log("BackgroundJob::handle(Job ID: {$job_id}): Job determined as FAILED. Error: " . ($error_message ?: 'Unspecified') . " Log: " . ($log_content ?: 'N/A') . " Current blog: " . get_current_blog_id()); // DETAILED DEBUG
		}

        error_log("validate_success: " . ($validate_success ? 'true' : 'false')); // DEBUG
        error_log("convert_success: " . ($convert_success ? 'true' : 'false')); // DEBUG

		$final_update_result = app( 'db' )->table( $job_table_name )
			->where( 'id', $job_id )
			->update( $final_status_data );

		if ( $final_update_result === false ) {
			error_log("BackgroundJob::handle(Job ID: {$job_id}): FINAL DB update to status '{$final_status}' failed. Current blog: " . get_current_blog_id()); // DETAILED DEBUG
		} else {
			error_log("BackgroundJob::handle(Job ID: {$job_id}): FINAL DB update to status '{$final_status}' succeeded. Rows affected: " . $final_update_result . ". Current blog: " . get_current_blog_id()); // DETAILED DEBUG
		}

		error_log("BackgroundJob::handle(Job ID: {$job_id}): FINAL DB update to status '{$final_status}'. Result: " . ($final_update_result ? 'Success (rows: ' . $final_update_result . ')' : 'Failure') . ". Current blog: " . get_current_blog_id()); // DETAILED DEBUG

		// Cleanup like deleting transients
		delete_transient( 'dirsize_cache' );

		if ( $original_locale_switched ) {
			restore_current_locale();
		}

		if ( $switched_blog ) {
			restore_current_blog();
			error_log("BackgroundJob::handle(Job ID: {$job_id}): Restored to original blog. Current blog now: " . get_current_blog_id()); // DETAILED DEBUG
		}
		error_log("BackgroundJob::handle(Job ID: {$job_id}): FINISHED. Final status in DB should be '{$final_status}'. Switched blog was: " . ($switched_blog ? 'Yes' : 'No') . ". Current blog at exit: " . get_current_blog_id()); // DETAILED DEBUG
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
		// error_log("SSE Stream: Attempting to start stream for user: " . get_current_user_id()); // DEBUG

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

		error_log("SSE Stream: STARTING for User ID: {$current_user_id}, Book ID: {$book_id}"); // DETAILED DEBUG

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

		error_log("SSE Stream (User ID: {$current_user_id}, Book ID: {$book_id}): Initialized. Entering main loop for table '{$table_name}'."); // DETAILED DEBUG

		while ( true ) {
			$db = app( 'db' ); // Get a fresh DB instance INSIDE the loop

			// error_log("SSE Stream: Loop iteration. User ID: {$current_user_id}, Book ID: {$book_id}, Table: {$table_name}"); // DEBUG
			if ( connection_status() !== CONNECTION_NORMAL || connection_aborted() ) {
				error_log("SSE Stream (User ID: {$current_user_id}, Book ID: {$book_id}): Client disconnected. Exiting loop."); // DETAILED DEBUG
				break; // Client disconnected
			}

			$active_jobs = $db->table( $table_name )
				->where( 'user_id', $current_user_id )
				->where( 'book_id', $book_id )
				->whereIn( 'status', [ 'pending', 'processing', 'running', 'completed' ] )
				->orderBy( 'created_at', 'DESC' )
				->where( 'updated_at', '>=', date( 'Y-m-d H:i:s', strtotime( '-1 minutes' ) ) ) // Only jobs updated in the last minute
				->get();
			error_log("SSE Stream (Loop for User ID: {$current_user_id}, Book ID: {$book_id}): Fetched " . $active_jobs->count() . " jobs. Details: " . print_r($active_jobs->toArray(), true)); // DETAILED DEBUG

			$found_updates = false;
			if ( ! $active_jobs->isEmpty() ) {
				foreach ( $active_jobs as $job ) {
					$current_job_state_for_comparison = clone $job; // Clone for comparison to avoid modifying by reference if structure changes before serializing
					unset($current_job_state_for_comparison->updated_at); // updated_at will always change, exclude from comparison
					$current_job_state_json = wp_json_encode( $current_job_state_for_comparison );

					error_log("SSE Stream (Job ID: {$job->id}, User ID: {$current_user_id}): Current DB state - Status: {$job->status}, Progress: {$job->progress_percentage}%. Last sent state: " . ($last_sent_statuses[$job->id] ?? 'Never')); // DETAILED DEBUG

					$should_send = ! isset( $last_sent_statuses[ $job->id ] ) || $last_sent_statuses[ $job->id ] !== $current_job_state_json;

					if ( $should_send ) {
						error_log("SSE Stream (Job ID: {$job->id}, User ID: {$current_user_id}): State CHANGED or NEW. Preparing to send: Status: {$job->status}, Progress: {$job->progress_percentage}% Current JSON: {$current_job_state_json} Last JSON: " . ($last_sent_statuses[$job->id] ?? 'NONE')); // DETAILED DEBUG
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
							// Construct download URL (ensure Export::getExportFolderUrl() is correct)
							$download_nonce = wp_create_nonce( 'download_export_job_' . $job->id );
							$job_data_to_send['download_url'] = admin_url( 'admin.php?page=pb_export&download_export_file=' . basename( $job->output_file_path ) . '&job_id=' . $job->id . '&_wpnonce=' . $download_nonce );
							$job_data_to_send['file_size'] = file_exists( $job->output_file_path ) ? size_format( filesize( $job->output_file_path ), 2 ) : '';
						} elseif ( $job->status === 'failed' ) {
							// Attempt to get a more specific error message if available
							$log_details = json_decode( $job->log_details, true );
							if ( is_array( $log_details ) && isset( $log_details['error'] ) ) {
								$job_data_to_send['error_message'] = $log_details['error'];
							} elseif ( is_string( $log_details ) ) { // Fallback if log_details is just a string
								$job_data_to_send['error_message'] = $log_details;
							} else {
								$job_data_to_send['error_message'] = $job->progress_message; // Use progress message as fallback
							}
						}
						self::sendSecureSSE( $job_data_to_send, 'export_job_update', $job->id );
						$last_sent_statuses[ $job->id ] = $current_job_state_json;
						$found_updates = true;
						error_log("SSE Stream (Job ID: {$job->id}, User ID: {$current_user_id}): Successfully called sendSecureSSE. New last_sent_status recorded."); // DETAILED DEBUG
					} else {
						error_log("SSE Stream (Job ID: {$job->id}, User ID: {$current_user_id}): State UNCHANGED ({$job->status}, {$job->progress_percentage}%). Not sending update. Current JSON: {$current_job_state_json} Last JSON: " . ($last_sent_statuses[$job->id] ?? 'NONE')); // DETAILED DEBUG
					}
				}
			}

			// Explicitly flush the output buffer
			if (ob_get_level() > 0) {
				ob_flush();
			}
			flush();
		}

		error_log("SSE Stream (User ID: {$current_user_id}, Book ID: {$book_id}): Exiting stream_all_user_jobs_status function (SHOULD NOT HAPPEN in normal operation if client stays connected)."); // DETAILED DEBUG
		exit;
	}
}
