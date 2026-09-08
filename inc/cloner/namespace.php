<?php
/**
 * Background cloning AJAX handlers (queue + status).
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Cloner;

use function Pressbooks\Admin\Laf\can_create_new_books;

/**
 * Can the current user use the cloner?
 *
 * Mirrors the gate used to display the Clone a Book page.
 *
 * @return bool
 */
function can_user_clone(): bool {
	return Cloner::isEnabled() && ( can_create_new_books() || is_super_admin() );
}

/**
 * WP_Ajax: queue a background clone job.
 *
 * Fails fast on everything a retry can't fix: permissions, target name,
 * source compatibility, source license. Heavy fetching happens in the job.
 */
function queue_clone_job(): void {
	check_ajax_referer( 'pb-cloner' );

	if ( ! can_user_clone() ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'pressbooks' ) ], 403 );
		return;
	}

	$source_url = isset( $_POST['source_book_url'] ) ? trim( esc_url_raw( wp_unslash( $_POST['source_book_url'] ) ) ) : '';
	$target_slug = isset( $_POST['target_book_url'] ) ? sanitize_text_field( wp_unslash( $_POST['target_book_url'] ) ) : '';
	$target_title = isset( $_POST['target_book_title'] ) ? sanitize_text_field( wp_unslash( $_POST['target_book_title'] ) ) : '';

	if ( empty( $source_url ) ) {
		wp_send_json_error( [ 'message' => __( 'A source book URL is required.', 'pressbooks' ) ], 400 );
		return;
	}

	$target_url = Cloner::validateNewBookName( $target_slug );
	if ( is_wp_error( $target_url ) ) {
		wp_send_json_error( [ 'message' => $target_url->get_error_message() ], 400 );
		return;
	}

	CloneJobs::ensureTable();

	// Duplicate guard: one active job per target.
	$existing = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )
		->where( 'target_url', $target_url )
		->whereIn( 'status', [ CloneJobs::STATUS_PENDING, CloneJobs::STATUS_PROCESSING ] )
		->first();
	if ( $existing ) {
		wp_send_json_error( [ 'message' => __( 'A clone job for this book is already in progress.', 'pressbooks' ) ], 409 );
		return;
	}

	// Cap remote-probe timeouts: the 300s budget belongs to the background job, not this AJAX request.
	$probe_timeout = function ( $args ) {
		$args['timeout'] = 15;
		return $args;
	};
	add_filter( 'http_request_args', $probe_timeout );

	// Fail fast: compatibility (remote sources) and license.
	$probe = new Cloner( $source_url );
	if ( ! $probe->getSourceBookId() && ! $probe->isCompatible( $source_url ) ) {
		remove_filter( 'http_request_args', $probe_timeout );
		wp_send_json_error( [
			'message' => __( 'You can only clone from a book hosted by Pressbooks 4.1 or later. Please ensure that your source book meets these requirements.', 'pressbooks' ),
		], 400 );
		return;
	}
	// Fetch metadata directly so we can inspect the HTTP status: a 401/403 means the
	// source book is not public (its REST API rejects anonymous reads), which is a
	// different problem from an unreachable host and needs a different message.
	$metadata = $probe->handleGetRequest( $probe->getSourceBookUrl(), 'pressbooks/v2', 'metadata' );
	if ( is_wp_error( $metadata ) || empty( $metadata ) ) {
		remove_filter( 'http_request_args', $probe_timeout );
		$http_status = is_wp_error( $metadata ) ? (int) $metadata->get_error_code() : 0;
		if ( in_array( $http_status, [ 401, 403 ], true ) ) {
			$message = sprintf(
				/* translators: %s: source book URL */
				__( 'The source book at %s is not publicly accessible. Ask the book&rsquo;s owner to make it public before cloning.', 'pressbooks' ),
				$source_url
			);
		} else {
			$message = sprintf(
				/* translators: %s: source book URL */
				__( 'Could not retrieve metadata from %s. Check that the URL is correct and the book is reachable.', 'pressbooks' ),
				$source_url
			);
		}
		wp_send_json_error( [ 'message' => $message ], 400 );
		return;
	}
	if ( ! $probe->isSourceCloneable( $metadata['license'] ?? '' ) ) {
		remove_filter( 'http_request_args', $probe_timeout );
		wp_send_json_error( [
			/* translators: %s: source book title */
			'message' => sprintf( __( '%s is not licensed for cloning.', 'pressbooks' ), $metadata['name'] ?? $source_url ),
		], 400 );
		return;
	}

	remove_filter( 'http_request_args', $probe_timeout );

	$job_id = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )->insertGetId( [
		'user_id' => get_current_user_id(),
		'source_url' => $probe->getSourceBookUrl(),
		'target_url' => $target_url,
		'target_title' => $target_title,
		'status' => CloneJobs::STATUS_PENDING,
		'created_at' => current_time( 'mysql', true ),
		'updated_at' => current_time( 'mysql', true ),
	] );

	if ( ! $job_id ) {
		wp_send_json_error( [ 'message' => __( 'Failed to queue the clone job.', 'pressbooks' ) ], 500 );
		return;
	}

	wp_schedule_single_event( time(), 'pressbooks_process_clone_job', [ 'job_id' => $job_id ] );

	wp_send_json_success( [ 'job_id' => $job_id ] );
}

/**
 * WP_Ajax: report the status of a clone job.
 *
 * With ?job_id → that job (must belong to the current user).
 * Without → the current user's most recent pending/processing job (page-load resume).
 */
function clone_job_status(): void {
	check_ajax_referer( 'pb-cloner' );

	// Unlike queue_clone_job(), this endpoint deliberately skips can_user_clone():
	// owner scoping below is the real authorization, and a job already running
	// must stay visible even if cloning gets disabled network-wide mid-clone.
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( [ 'message' => __( 'Permission denied.', 'pressbooks' ) ], 403 );
		return;
	}

	CloneJobs::ensureTable();

	$job_id = isset( $_GET['job_id'] ) ? (int) $_GET['job_id'] : 0;

	$query = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )
		->where( 'user_id', get_current_user_id() );

	if ( $job_id > 0 ) {
		$query->where( 'id', $job_id );
	} else {
		$query->whereIn( 'status', [ CloneJobs::STATUS_PENDING, CloneJobs::STATUS_PROCESSING ] )
			->orderBy( 'created_at', 'desc' )
			->orderBy( 'id', 'desc' );
	}

	$job = $query->first();

	if ( ! $job ) {
		wp_send_json_error( [ 'message' => __( 'Job not found.', 'pressbooks' ) ], 404 );
		return;
	}

	$status = $job->status;
	$message = $job->progress_message;

	// Staleness guard: a processing job whose worker died can never finish on its own.
	if ( CloneJobs::STATUS_PROCESSING === $status ) {
		/**
		 * Filter the number of seconds after which a silent processing clone job
		 * is considered dead.
		 *
		 * @param int $timeout
		 */
		$timeout = apply_filters( 'pb_clone_job_stale_timeout', HOUR_IN_SECONDS );
		$last_update = strtotime( $job->updated_at . ' +0000' );
		if ( $last_update && $last_update < time() - $timeout ) {
			$status = CloneJobs::STATUS_FAILED;
			$message = __( 'The clone job timed out. The target book may exist in a partial state; contact your network manager to remove it.', 'pressbooks' );
			app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )
				->where( 'id', $job->id )
				->where( 'status', CloneJobs::STATUS_PROCESSING )
				->update( [
					'status' => CloneJobs::STATUS_FAILED,
					'progress_message' => $message,
					'job_completed_at' => current_time( 'mysql', true ),
					'updated_at' => current_time( 'mysql', true ),
				] );
		}
	} elseif ( CloneJobs::STATUS_PENDING === $status ) {
		// A pending job that never reached processing means WP-Cron never fired
		// (or the scheduled event was lost). It created no target book, so it can
		// simply be failed and retried instead of polling forever.
		/**
		 * Filter the number of seconds after which a clone job that never started
		 * processing is considered dead.
		 *
		 * @param int $timeout
		 */
		$timeout = apply_filters( 'pb_clone_job_pending_timeout', 15 * MINUTE_IN_SECONDS );
		$queued_at = strtotime( $job->created_at . ' +0000' );
		if ( $queued_at && $queued_at < time() - $timeout ) {
			$status = CloneJobs::STATUS_FAILED;
			$message = __( 'The clone job could not be started. Please try again.', 'pressbooks' );
			app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )
				->where( 'id', $job->id )
				->where( 'status', CloneJobs::STATUS_PENDING )
				->update( [
					'status' => CloneJobs::STATUS_FAILED,
					'progress_message' => $message,
					'job_completed_at' => current_time( 'mysql', true ),
					'updated_at' => current_time( 'mysql', true ),
				] );
		}
	}

	wp_send_json_success( [
		'job_id' => (int) $job->id,
		'status' => $status,
		'progress_percentage' => (int) $job->progress_percentage,
		'progress_message' => $message,
		'cloned_items' => $job->cloned_items ? json_decode( $job->cloned_items, true ) : null,
	] );
}
