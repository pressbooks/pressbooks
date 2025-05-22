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
		if ( is_multisite() && get_current_blog_id() !== (int) $job->book_id ) {
			switch_to_blog( $job->book_id );
			$switched_blog = true;
		} elseif ( ! is_multisite() && (int) $job->book_id !== 1 && get_current_blog_id() === 1 ) {
			// Single site, but a job somehow has a different book_id. This shouldn't happen.
			return;
		}

		if ( $job->status !== 'pending' ) {
			if ( $switched_blog ) {
				restore_current_blog();
			}
			return;
		}

		// Mark as processing
		app( 'db' )->table( $job_table_name )
			->where( 'id', $job_id )
			->update([
				'status' => 'processing',
				'progress_percentage' => 0,
				'progress_message' => __( 'Initializing export...', 'pressbooks' ),
				'job_started_at' => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			]);

		if ( ! class_exists( $job->export_module_classname ) ) {
			app( 'db' )->table( $job_table_name )
				->where( 'id', $job_id )
				->update([
					'status' => 'failed',
					'progress_message' => sprintf( __( 'Exporter class %s not found.', 'pressbooks' ), $job->export_module_classname ),
					'log_details' => sprintf( __( 'Exporter class %s not found.', 'pressbooks' ), $job->export_module_classname ),
					'job_completed_at' => current_time( 'mysql', true ),
					'updated_at' => current_time( 'mysql', true ),
				]);
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

		app( 'db' )->table( $job_table_name )
			->where( 'id', $job_id )
			->update([
				'progress_percentage' => 10,
				'progress_message' => __( 'Preparing export...', 'pressbooks' ),
				'updated_at' => current_time( 'mysql', true ),
			]);

		$convert_success = false;
		$validate_success = false;
		$error_message = '';
		$output_path = '';
		$log_content = '';

		try {
			// Update progress before potentially long operations
			app( 'db' )->table( $job_table_name )
				->where( 'id', $job_id )
				->update([
					'progress_percentage' => 30,
					'progress_message' => __( 'Converting content (this may take a while, you can leave this page and return back later)...', 'pressbooks' ),
					'updated_at' => current_time( 'mysql', true ),
				]);

			if ( ! $exporter->convert() ) {
				$error_message = __( 'Conversion process failed.', 'pressbooks' );
			} else {
				$convert_success = true;
				$output_path = $exporter->getOutputPath();
				app( 'db' )->table( $job_table_name )
					->where( 'id', $job_id )
					->update([
						'progress_percentage' => 70,
						'progress_message' => __( 'Conversion successful. Validating file...', 'pressbooks' ),
						'output_file_path' => $output_path,
						'updated_at' => current_time( 'mysql', true ),
					]);

				if ( ! $exporter->validate() ) {
					$error_message = __( 'Validation of exported file failed.', 'pressbooks' );
				} else {
					$validate_success = true;
				}
			}
		} catch ( \Exception $e ) {
			$error_message = $e->getMessage();
			$log_content = $e->getTraceAsString();
		}

		$final_status_data = [
			'job_completed_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		];

		if ( $convert_success && $validate_success ) {
			$final_status_data['status'] = 'completed';
			$final_status_data['progress_percentage'] = 100;
			$final_status_data['progress_message'] = __( 'Export successful!', 'pressbooks' );
			$final_status_data['output_file_path'] = $output_path;
			$final_status_data['log_details'] = $log_content ?: __( 'Export completed without errors.', 'pressbooks' );
		} else {
			$final_status_data['status'] = 'failed';
			$final_status_data['progress_percentage'] = $validate_success ? 90 : ( $convert_success ? 70 : 50 );
			$final_status_data['progress_message'] = $error_message ?: __( 'Export failed due to an unknown error.', 'pressbooks' );
			$final_status_data['log_details'] = $log_content ?: ( $error_message ?: 'Unknown error during export.' );

			$pdf_module_classes = [
				'\\Pressbooks\\Modules\\Export\\Prince\\Pdf',
				'\\Pressbooks\\Modules\\Export\\Prince\\PrintPdf',
			];
			if ( in_array( $job->export_module_classname, $pdf_module_classes ) && $convert_success && ! $validate_success && $output_path && file_exists( $output_path ) ) {
				unlink( $output_path );
				$final_status_data['output_file_path'] = null;
				$final_status_data['progress_message'] .= ' ' . __( 'Invalid output file has been deleted.', 'pressbooks' );
			}
		}

		app( 'db' )->table( $job_table_name )
			->where( 'id', $job_id )
			->update( $final_status_data );

		// Cleanup like deleting transients
		delete_transient( 'dirsize_cache' );

		if ( $original_locale_switched ) {
			restore_current_locale();
		}

		if ( $switched_blog ) {
			restore_current_blog();
		}

	}

	/**
	 * Creates the export jobs table for each site in a multisite installation.
	 */
	public function createExportJobsTables(): void {
		if ( is_multisite() ) {
			$sites = get_sites();
			foreach ( $sites as $site ) {
				switch_to_blog( $site->blog_id );
				self::createJobTable();
				restore_current_blog();
			}
			return;
		}
		self::createJobTable();
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
	 * SSE endpoint to provide status updates for an export job.
	 * Handles AJAX requests for job status updates.
	 */
	public static function updateSSEStatus() {
		// Start output buffering if not already started
		if ( ob_get_level() === 0 ) {
			ob_start();
		}

		$job_id_key = isset( $_GET['job_id'] ) ? sanitize_key( $_GET['job_id'] ) : 'unknown_job';
		$nonce_action = 'pressbooks_export_status_' . $job_id_key;
		$nonce_value = isset( $_GET['_wpnonce'] ) ? sanitize_key( $_GET['_wpnonce'] ) : null;

		if ( ! isset( $_GET['job_id'] ) || ! isset( $_GET['book_id'] ) || ! $nonce_value || ! wp_verify_nonce( $nonce_value, $nonce_action ) ) {
			status_header( 403 );
			self::sendSecureSSE(
				data: [
					'message' => __( 'Invalid request or nonce.', 'pressbooks' ),
				],
				event_type: 'error'
			);
			wp_die();
		}

		// Set headers for SSE
		header( 'Content-Type: text/event-stream' );
		header( 'Cache-Control: no-cache' );
		header( 'Connection: keep-alive' );
		header( 'X-Accel-Buffering: no' ); // Useful for Nginx

		$job_id = absint( $_GET['job_id'] );
		$book_id = absint( $_GET['book_id'] );
		$user_id = get_current_user_id();

		if ( ! $book_id || ! $job_id ) {
			status_header( 400 );
			self::sendSecureSSE(
				data: [
					'message' => __( 'Book ID or Job ID not provided.', 'pressbooks' ),
				],
				event_type: 'error'
			);
			wp_die();
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
		$last_progress = -1;
		$last_status = '';
		$iterations = 0;
		$max_iterations = 720; // Poll for max 1 hour (720 * 5s = 3600s)

		while ( $iterations < $max_iterations ) {
			if ( connection_aborted() ) {
				break;
			}

			$job = app( 'db' )->table( $table_name )
				->select([
					'status',
					'progress_percentage',
					'progress_message',
					'output_file_path',
					'user_id',
					'log_details',
				])
				->where( 'id', $job_id )
				->where( 'book_id', $book_id )
				->first();

			if ( ! $job ) {
				$data = [
					'status' => 'error',
					'progress_percentage' => 0,
					'message' => __( 'Job not found or access denied.', 'pressbooks' ),
					'job_id' => $job_id,
				];
				self::sendSecureSSE(
					data: $data,
				);
				break;
			}

			if ( (int) $job->user_id !== $user_id && ! current_user_can( 'manage_options' ) ) {
				$data = [
					'status' => 'error',
					'progress_percentage' => 0,
					'message' => __( 'Access denied to view this job\'s status.', 'pressbooks' ),
					'job_id' => $job_id,
				];
				self::sendSecureSSE(
					data: $data,
				);
				break;
			}

			// Check if we have new progress or status to report
			if ( $job->progress_percentage !== $last_progress || $job->status !== $last_status ) {
				$data_to_send = [
					'status' => $job->status,
					'progress' => (int) $job->progress_percentage,
					'message' => $job->progress_message,
					'job_id' => $job_id,
					'book_id' => $book_id,
				];

				// Add validation warnings if present
				if ( ! empty( $job->log_details ) && str_contains( $job->log_details, 'Validation' ) ) {
					$data_to_send['has_warnings'] = true;
					$data_to_send['warning_message'] = __( 'Export completed with some non-critical warnings. Your file is ready for use.', 'pressbooks' );
				}

				self::sendSecureSSE(
					data: $data_to_send,
				);

				$last_progress = (int) $job->progress_percentage;
				$last_status = $job->status;
			}

			// Only break the loop if the job is truly complete or failed
			if ( in_array( $job->status, [ 'completed', 'failed' ], true ) ) {
				// Send one final message before breaking
				$final_data = [
					'status' => $job->status,
					'progress' => 100,
					'message' => $job->progress_message,
					'job_id' => $job_id,
					'book_id' => $book_id,
					'is_final' => true,
				];
				echo "event: export_progress\nid: " . time() . "\ndata: " . wp_json_encode( $final_data ) . "\n\n";
				self::sendSecureSSE(
					data: $final_data,
				);
				break;
			}

			sleep( 5 );
			$iterations++;
		}

		if ( $switched ) {
			restore_current_blog();
		}

		// Clean up output buffer
		while ( ob_get_level() > 0 ) {
			ob_end_flush();
		}

		wp_die();
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
}
