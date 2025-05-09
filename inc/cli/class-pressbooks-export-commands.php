<?php
/**
 * Pressbooks Export CLI Commands.
 */

if ( ! class_exists( 'WP_CLI_Command' ) ) {
	return;
}

/**
 * Manages Pressbooks background export jobs.
 */
class Pressbooks_Export_CLI_Commands extends WP_CLI_Command {

	/**
	 * Manually processes a specific export job from the queue for a given site.
	 *
	 * ## OPTIONS
	 *
	 * <job_id>
	 * : The ID of the export job to process.
	 *
	 * --blog_id=<blog_id>
	 * : The ID of the blog/site where the job originated.
	 *   Alternatively, use --url=<url> to specify the site.
	 *
	 * [--force_rerun]
	 * : If set, will attempt to run the job even if its status is not 'pending'. Use with caution.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pressbooks export process_job 123 --blog_id=2
	 *     wp pressbooks export process_job 123 --url=http://example.com/site2 --force_rerun
	 *
	 * @when after_wp_load
	 */
	public function process_job( $args, $assoc_args ) {
		list( $job_id ) = $args;
		$job_id = absint( $job_id );

		if ( ! $job_id ) {
			WP_CLI::error( 'Invalid Job ID provided.' );
			return;
		}

		// Determine the blog_id. WP-CLI's --url parameter handles switching context globally.
        // If --blog_id is provided, it takes precedence for this specific command's logic.
        $blog_id_arg = WP_CLI\Utils\get_flag_value( $assoc_args, 'blog_id' );
        
        $original_blog_id = get_current_blog_id();
        $switched = false;

        if ( $blog_id_arg ) {
            $blog_id_to_switch = absint($blog_id_arg);
            if ( $blog_id_to_switch !== $original_blog_id ) {
                if ( ! get_site( $blog_id_to_switch ) ) {
                    WP_CLI::error( "Blog ID {$blog_id_to_switch} not found." );
                    return;
                }
                switch_to_blog( $blog_id_to_switch );
                $switched = true;
                WP_CLI::log( "Switched to blog ID: {$blog_id_to_switch}" );
            }
        } else if (isset($assoc_args['url'])) {
             // If only --url is used, WP-CLI handles the switch, get_current_blog_id() should be correct.
             WP_CLI::log( "Operating on blog ID: " . get_current_blog_id() . " (determined by --url or default)" );
        } else if (is_multisite()) {
            WP_CLI::error( "In a multisite environment, please specify --blog_id=<blog_id> or --url=<site_url>." );
            return;
        }


		global $wpdb;
		// $wpdb->prefix will now be correct for the (potentially) switched blog
		$table_name = $wpdb->prefix . 'pressbooks_export_jobs';
		$job = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table_name} WHERE id = %d", $job_id ) );

		if ( ! $job ) {
			WP_CLI::error( "Job ID {$job_id} not found in the database for blog ID " . get_current_blog_id() . "." );
			if ($switched) { restore_current_blog(); }
			return;
		}

		// Security check: ensure the job's book_id matches the current context
        if ( absint($job->book_id) !== get_current_blog_id() ) {
            WP_CLI::error( "Job {$job_id} belongs to book_id {$job->book_id}, but current context is blog_id " . get_current_blog_id() . ". Mismatch." );
            if ($switched) { restore_current_blog(); }
            return;
        }


		WP_CLI::log( "Found job {$job_id}. Status: {$job->status}, Module: {$job->export_module_classname}" );

		$force_rerun = isset( $assoc_args['force_rerun'] ) ? true : false;

		if ( 'pending' !== $job->status && ! $force_rerun ) {
			WP_CLI::warning( "Job {$job_id} is not in 'pending' status. Current status: {$job->status}. Use --force_rerun to process anyway." );
			if ($switched) { restore_current_blog(); }
			return;
		}
		
		if ( $force_rerun && 'processing' === $job->status ) {
			WP_CLI::warning( "Job {$job_id} is currently 'processing'. Forcing a rerun might lead to unexpected behavior if another process is active." );
		}


		// Ensure the main job processing function is available
		if ( ! function_exists( 'pressbooks_process_export_job' ) ) {
			// Attempt to load it if your structure requires it, e.g.
			// $handler_file = plugin_dir_path( __FILE__ ) . '../background-export-handlers.php'; // Adjust path
			// if (file_exists($handler_file)) { require_once $handler_file; }

			if ( ! function_exists( 'pressbooks_process_export_job' ) ) {
				WP_CLI::error( 'The function pressbooks_process_export_job() is not defined. Ensure background-export-handlers.php is loaded.' );
                if ($switched) { restore_current_blog(); }
				return;
			}
		}
		
		WP_CLI::log( "Attempting to process job {$job_id} for blog " . get_current_blog_id() . "..." );

		// If the job status is not 'pending', and force_rerun is true, you might want to reset it.
        // This is optional and depends on how you want retries to behave.
        if ( $force_rerun && $job->status !== 'pending' ) {
            $wpdb->update(
                $table_name,
                [ 'status' => 'pending', 'updated_at' => current_time( 'mysql', true ) ],
                [ 'id' => $job_id ]
            );
            WP_CLI::log( "Job {$job_id} status reset to 'pending' due to --force_rerun." );
        }


		// Call your existing job processing function
		pressbooks_process_export_job( $job_id );

		// Fetch the job again to report its final status
		$job_after = $wpdb->get_row( $wpdb->prepare( "SELECT status, progress_message, output_file_path FROM {$table_name} WHERE id = %d", $job_id ) );

		if ( $job_after ) {
			WP_CLI::success( "Job {$job_id} processing finished. Final status: {$job_after->status}." );
			if ( $job_after->progress_message ) {
				WP_CLI::log( "Message: {$job_after->progress_message}" );
			}
			if ( $job_after->output_file_path ) {
				WP_CLI::log( "Output file: {$job_after->output_file_path}" );
			}
		} else {
			WP_CLI::error( "Could not retrieve job {$job_id} status after processing." );
		}

        if ($switched) {
            restore_current_blog();
            WP_CLI::log( "Switched back to original blog ID: {$original_blog_id}" );
        }
	}
}

// Register the command
WP_CLI::add_command( 'pressbooks export', 'Pressbooks_Export_CLI_Commands' );
