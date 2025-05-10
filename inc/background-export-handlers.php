<?php
/**
 * Pressbooks Background Export Job Handlers
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Ensures the export jobs table exists.
 * This function should be called before any table operations.
 */
function pressbooks_ensure_export_jobs_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'pressbooks_export_jobs';
	
	// Check if table exists
	$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;
	
	if (!$table_exists) {
		pressbooks_create_export_jobs_table();
	}
}

/**
 * Handles the background processing of an export job.
 * This function is triggered by WP-Cron.
 *
 * @param int $job_id The ID of the export job.
 */
function pressbooks_process_export_job( $job_id ) {
	set_time_limit( 0 ); // Allow unlimited execution time for this job
	global $wpdb;
	// Note: $wpdb->prefix will be determined by the blog context AFTER switch_to_blog.

	// Fetch job details first using the global $wpdb which might be on the wrong site initially if cron runs from main site.
	// A more robust way for cron might be to store the blog_id with the cron schedule args,
	// switch to it, then query the job. However, $job->book_id is what we rely on from class-export.php.
	// For now, we assume job_id is unique enough or the initial query is against a global/main site job table IF it were designed that way.
	// BUT, our design is per-site tables. So, the cron *must* run on, or switch to, the correct blog to find the job.
	// The `wp_schedule_single_event` in `Export::exportGenerator` runs in the context of the current blog,
	// so WP-Cron should trigger this hook in the context of that blog, meaning $wpdb->prefix should be correct.
	// If WP-Cron itself runs all hooks under the main site context, then $job->book_id is essential BEFORE the first query.
	// Let's assume cron triggers in the correct blog context due to how it was scheduled.

	$initial_blog_id = get_current_blog_id(); // For safety, to see where cron is running
	$job_table_name_for_log = $wpdb->prefix . 'pressbooks_export_jobs'; // For initial log before potential switch

	// It's safer to get the job by ID and then use its book_id to switch,
	// especially if job IDs could clash or cron context is unpredictable.
	// However, to find the job, we need to know which blog's table to query.
	// The current scheduling in Export::exportGenerator is blog-specific, so this function *should* run in that blog's context.

	// Let's assume $job_id is for the current blog context WP-Cron is running under.
	$table_name = $wpdb->prefix . 'pressbooks_export_jobs';
	$job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $job_id ) );

	if ( ! $job ) {
		error_log( "Pressbooks Export CRON: Job {$job_id} not found. Current blog context: {$initial_blog_id}. Table queried: {$job_table_name_for_log}." );
		return;
	}

	// Now we have $job->book_id, ensure we are on the correct blog.
	$switched_blog = false;
	if ( is_multisite() && get_current_blog_id() != $job->book_id ) {
		switch_to_blog( $job->book_id );
		$switched_blog = true;
		// Re-initialize $wpdb->prefix dependent table name after switching
		$table_name = $wpdb->prefix . 'pressbooks_export_jobs';
	} elseif ( ! is_multisite() && $job->book_id != 1 && get_current_blog_id() == 1 ) {
		// Single site, but job somehow has a different book_id. This shouldn't happen.
		error_log( "Pressbooks Export CRON: Job {$job_id} has book_id {$job->book_id} on a single site. Aborting." );
		return;
	}

	if ( $job->status !== 'pending' ) {
		error_log( "Pressbooks Export CRON: Job {$job_id} for book {$job->book_id} was not in 'pending' state. Status: {$job->status}. Skipping." );
		if ( $switched_blog ) {
			restore_current_blog();
		}
		return;
	}

	// Mark as processing
	$wpdb->update(
		$table_name,
		[
			'status' => 'processing',
			'progress_percentage' => 0,
			'progress_message' => __( 'Initializing export...', 'pressbooks' ),
			'job_started_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		],
		[ 'id' => $job_id ]
	);

	if ( ! class_exists( $job->export_module_classname ) ) {
		$wpdb->update(
			$table_name,
			[
				'status' => 'failed',
				'progress_message' => sprintf( __( 'Exporter class %s not found.', 'pressbooks' ), $job->export_module_classname ),
				'log_details' => sprintf( __( 'Exporter class %s not found.', 'pressbooks' ), $job->export_module_classname ),
				'job_completed_at' => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			],
			[ 'id' => $job_id ]
		);
		error_log( "Pressbooks Export CRON: Job {$job_id} failed for book {$job->book_id}. Exporter class {$job->export_module_classname} not found." );
		if ( $switched_blog ) {
			restore_current_blog();
		}
		return;
	}

	// User context - WP-Cron often runs as anonymous. If exporter needs user permissions:
	// if ( $job->user_id ) { wp_set_current_user( $job->user_id ); }

	$constructor_args = [];
	if ( ! empty( $job->export_options ) ) {
		$job_options = json_decode( $job->export_options, true );
		if ( is_array( $job_options ) ) { // Ensure it's an array after decode
			$constructor_args = $job_options;
			// Example: if a specific theme option snapshot was stored and needs to be filtered in
			// if (isset($job_options['theme_options_snapshot'])) {
			// add_filter('option_pressbooks_theme_options_prince', function() use ($job_options) {
			// return $job_options['theme_options_snapshot'];
			// }, 10, 0);
			// }
		}
	}

	/** @var \Pressbooks\Modules\Export\Export $exporter */
	$exporter = new $job->export_module_classname( $constructor_args );

	$original_locale_switched = false;
	$locale_to_use = \Pressbooks\Modules\Export\Export::locale(); // Get target locale for export
	if ( $locale_to_use ) {
		$original_locale_switched = switch_to_locale( $locale_to_use );
	}

	$wpdb->update( $table_name, [
		'progress_percentage' => 10,
		'progress_message' => __( 'Preparing export...', 'pressbooks' ),
		'updated_at' => current_time( 'mysql', true ),
	], [ 'id' => $job_id ] );

	$convert_success = false;
	$validate_success = false;
	$error_message = '';
	$output_path = '';
	$log_content = '';

	try {
		// Update progress before potentially long operations
		$wpdb->update( $table_name, [
			'progress_percentage' => 30,
			'progress_message' => __( 'Converting content (this may take a while)...', 'pressbooks' ),
			'updated_at' => current_time( 'mysql', true ),
		], [ 'id' => $job_id ] );

		if ( ! $exporter->convert() ) {
			$error_message = __( 'Conversion process failed.', 'pressbooks' );
			if ( isset( $exporter->logfile ) && is_readable( $exporter->logfile ) ) {
				$log_content = file_get_contents( $exporter->logfile );
			} elseif ( property_exists( $exporter, 'errorLog' ) && ! empty( $exporter->errorLog ) ) { // Some exporters might use a property
				$log_content = is_array( $exporter->errorLog ) ? implode( "\n", $exporter->errorLog ) : $exporter->errorLog;
			}
		} else {
			$convert_success = true;
			$output_path = $exporter->getOutputPath();
			$wpdb->update( $table_name, [
				'progress_percentage' => 70,
				'progress_message' => __( 'Conversion successful. Validating file...', 'pressbooks' ),
				'output_file_path' => $output_path,
				'updated_at' => current_time( 'mysql', true ),
			], [ 'id' => $job_id ] );

			if ( ! $exporter->validate() ) {
				$error_message = __( 'Validation of exported file failed.', 'pressbooks' );
				$current_log = ( isset( $exporter->logfile ) && is_readable( $exporter->logfile ) ) ? file_get_contents( $exporter->logfile ) : '';
				$log_content = $log_content ? $log_content . "\n---Validation---\n" . $current_log : $current_log;
			} else {
				$validate_success = true;
			}
		}
	} catch ( \Exception $e ) {
		$error_message = $e->getMessage();
		$log_content = $log_content ? $log_content . "\n---Exception---\n" . $e->getTraceAsString() : $e->getTraceAsString();
		error_log( "Pressbooks Export CRON: Exception during job {$job_id} for book {$job->book_id}: " . $e->getMessage() );
	}

	$final_status_data = [
		'job_completed_at' => current_time( 'mysql', true ),
		'updated_at' => current_time( 'mysql', true ),
	];

	if ( $convert_success && $validate_success ) {
		$final_status_data['status'] = 'completed';
		$final_status_data['progress_percentage'] = 100;
		$final_status_data['progress_message'] = __( 'Export successful!', 'pressbooks' );
		$final_status_data['output_file_path'] = $output_path; // Already set if convert was successful
		$final_status_data['log_details'] = $log_content ?: __( 'Export completed without errors.', 'pressbooks' );
	} else {
		$final_status_data['status'] = 'failed';
		$final_status_data['progress_percentage'] = $validate_success ? 90 : ( $convert_success ? 70 : 50 ); // Rough estimate
		$final_status_data['progress_message'] = $error_message ?: __( 'Export failed due to an unknown error.', 'pressbooks' );
		$final_status_data['log_details'] = $log_content ?: ( $error_message ?: 'Unknown error during export.' );

		// Similar to Export::postExport, handle cleanup for failed PDF validation specifically
		$pdf_module_classes = [
			'\\Pressbooks\\Modules\\Export\\Prince\\Pdf',
			'\\Pressbooks\\Modules\\Export\\Prince\\PrintPdf',
			// Add DocRaptor equivalents if they have similar behavior
		];
		if ( in_array( $job->export_module_classname, $pdf_module_classes ) && $convert_success && ! $validate_success && $output_path && file_exists( $output_path ) ) {
			unlink( $output_path ); // Delete invalid PDF
			$final_status_data['output_file_path'] = null; // Clear path
			$final_status_data['progress_message'] .= ' ' . __( 'Invalid output file has been deleted.', 'pressbooks' );
		}
	}

	$wpdb->update( $table_name, $final_status_data, [ 'id' => $job_id ] );

	// Cleanup like deleting transients (dirsize_cache is common in Pressbooks)
	delete_transient( 'dirsize_cache' );

	if ( $original_locale_switched ) {
		restore_current_locale();
	}

	if ( $switched_blog ) {
		restore_current_blog();
	}
}
add_action( 'pressbooks_process_export_job', 'pressbooks_process_export_job', 10, 1 );

/**
 * SSE endpoint to provide status updates for an export job.
 * Handles AJAX requests for job status updates.
 */
function pressbooks_export_status_sse() {
	error_log( '[DEBUG SSE HANDLER] Entered pressbooks_export_status_sse.' );
	
	// Start output buffering if not already started
	if (ob_get_level() === 0) {
		ob_start();
	}

	error_log( '[DEBUG SSE HANDLER] GET params: ' . print_r( $_GET, true ) );

	$job_id_key = isset( $_GET['job_id'] ) ? sanitize_key( $_GET['job_id'] ) : 'unknown_job';
	$nonce_action = 'pressbooks_export_status_' . $job_id_key;
	$nonce_value = isset( $_GET['_wpnonce'] ) ? sanitize_key( $_GET['_wpnonce'] ) : null;

	error_log( '[DEBUG SSE HANDLER] Nonce action expected: ' . $nonce_action );
	error_log( '[DEBUG SSE HANDLER] Nonce value received: ' . $nonce_value );

	if ( ! isset( $_GET['job_id'] ) || ! isset( $_GET['book_id'] ) || ! $nonce_value || ! wp_verify_nonce( $nonce_value, $nonce_action ) ) {
		error_log( '[DEBUG SSE HANDLER] Nonce verification FAILED or missing params.' );
		status_header( 403 );
		echo "event: error\ndata: " . wp_json_encode( [ 'message' => 'Invalid request or nonce.' ] ) . "\n\n";
		ob_end_flush();
		wp_die();
	}

	error_log( '[DEBUG SSE HANDLER] Nonce verification PASSED.' );

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
		echo "event: error\ndata: " . wp_json_encode( [ 'message' => 'Book ID or Job ID not provided.' ] ) . "\n\n";
		ob_end_flush();
		wp_die();
	}

	$switched = false;
	if ( is_multisite() && get_current_blog_id() != $book_id ) {
		switch_to_blog( $book_id );
		$switched = true;
	}

	// Ensure table exists before querying
	pressbooks_ensure_export_jobs_table();

	if ( ! current_user_can_for_blog( $book_id, 'edit_posts' ) ) {
		status_header( 403 );
		echo "event: error\ndata: " . wp_json_encode( [ 'message' => 'Permission denied to view job status for this book.' ] ) . "\n\n";
		if ( $switched ) {
			restore_current_blog();
		}
		ob_end_flush();
		wp_die();
	}

	error_log( '[DEBUG SSE HANDLER] Setting SSE headers.' );

	set_time_limit( 0 );

	global $wpdb;
	$table_name = $wpdb->prefix . 'pressbooks_export_jobs';
	$last_progress = -1;
	$last_status = '';
	$iterations = 0;
	$max_iterations = 720; // Poll for max 1 hour (720 * 5s = 3600s)

	while ( $iterations < $max_iterations ) {
		if ( connection_aborted() ) {
			break;
		}

		$job = $wpdb->get_row( $wpdb->prepare( 
			"SELECT status, progress_percentage, progress_message, output_file_path, user_id, log_details 
			FROM {$table_name} 
			WHERE id = %d AND book_id = %d", 
			$job_id, 
			$book_id 
		) );

		if ( ! $job ) {
			$data = [
				'status' => 'error',
				'progress_percentage' => 0,
				'message' => __( 'Job not found or access denied.', 'pressbooks' ),
				'job_id' => $job_id,
			];
			echo "event: export_progress\nid: " . time() . "\ndata: " . wp_json_encode( $data ) . "\n\n";
			ob_flush();
			flush();
			break;
		}

		if ( $job->user_id != $user_id && ! current_user_can_for_blog( $book_id, 'manage_options' ) ) {
			$data = [
				'status' => 'error',
				'progress_percentage' => 0,
				'message' => __( 'Access denied to view this job\'s status.', 'pressbooks' ),
				'job_id' => $job_id,
			];
			echo "event: export_progress\nid: " . time() . "\ndata: " . wp_json_encode( $data ) . "\n\n";
			ob_flush();
			flush();
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
			if ( ! empty( $job->log_details ) && strpos( $job->log_details, 'Validation' ) !== false ) {
				$data_to_send['has_warnings'] = true;
				$data_to_send['warning_message'] = __( 'Export completed with some non-critical warnings. Your file is ready for use.', 'pressbooks' );
			}

			echo "event: export_progress\nid: " . time() . "\ndata: " . wp_json_encode( $data_to_send ) . "\n\n";
			ob_flush();
			flush();

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
				'is_final' => true
			];
			echo "event: export_progress\nid: " . time() . "\ndata: " . wp_json_encode( $final_data ) . "\n\n";
			ob_flush();
			flush();
			break;
		}

		sleep( 5 );
		$iterations++;
	}

	if ( $switched ) {
		restore_current_blog();
	}

	// Clean up output buffer
	while (ob_get_level() > 0) {
		ob_end_flush();
	}
	
	wp_die();
}
add_action( 'wp_ajax_pressbooks_export_status_sse', 'pressbooks_export_status_sse' );

/**
 * AJAX handler for checking existing export jobs.
 */
function pressbooks_check_existing_jobs() {
	check_ajax_referer( 'pb-export-book', '_wpnonce' );

	if ( ! current_user_can( 'edit_posts' ) ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'pressbooks' ) ], 403 );
		return;
	}

	// Ensure table exists before querying
	pressbooks_ensure_export_jobs_table();

	global $wpdb;
	$table_name = $wpdb->prefix . 'pressbooks_export_jobs';
	$book_id = get_current_blog_id();
	$user_id = get_current_user_id();

	// Get all jobs for this book and user that aren't completed/failed/cancelled
	$jobs = $wpdb->get_results($wpdb->prepare(
		"SELECT id as job_id, book_id, export_format as module_slug, status, progress_percentage, progress_message 
		FROM {$table_name} 
		WHERE book_id = %d AND user_id = %d 
		AND status NOT IN ('completed', 'failed', 'cancelled')
		ORDER BY created_at DESC",
		$book_id,
		$user_id
	));

	if ( $jobs ) {
		// Add SSE nonce for each job
		foreach ( $jobs as &$job ) {
			$job->sse_nonce = wp_create_nonce( 'pressbooks_export_status_' . $job->job_id );
		}
		wp_send_json_success( [ 'jobs' => $jobs ] );
	} else {
		wp_send_json_success( [ 'jobs' => [] ] );
	}
}
add_action( 'wp_ajax_pressbooks_check_existing_jobs', 'pressbooks_check_existing_jobs' );

/**
 * Creates the export jobs table if it doesn't exist.
 * This function is called on plugin activation.
 */
function pressbooks_create_export_jobs_table() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'pressbooks_export_jobs';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		book_id bigint(20) NOT NULL,
		user_id bigint(20) NOT NULL,
		export_format varchar(50) NOT NULL,
		export_module_classname varchar(255) NOT NULL,
		export_options longtext,
		status varchar(20) NOT NULL DEFAULT 'pending',
		progress_percentage int(3) NOT NULL DEFAULT 0,
		progress_message text,
		output_file_path varchar(255),
		log_details longtext,
		job_started_at datetime DEFAULT NULL,
		job_completed_at datetime DEFAULT NULL,
		created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY book_id (book_id),
		KEY user_id (user_id),
		KEY status (status),
		KEY created_at (created_at)
	) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
}

/**
 * Creates the export jobs table for each site in a multisite installation.
 */
function pressbooks_create_export_jobs_tables() {
	if (is_multisite()) {
		$sites = get_sites();
		foreach ($sites as $site) {
			switch_to_blog($site->blog_id);
			pressbooks_create_export_jobs_table();
			restore_current_blog();
		}
	} else {
		pressbooks_create_export_jobs_table();
	}
}

// Hook to create tables on plugin activation
add_action('pressbooks_activate', 'pressbooks_create_export_jobs_tables');

// Also create table when a new site is created in multisite
add_action('wp_initialize_site', function($new_site) {
	if (is_multisite()) {
		switch_to_blog($new_site->blog_id);
		pressbooks_create_export_jobs_table();
		restore_current_blog();
	}
});
