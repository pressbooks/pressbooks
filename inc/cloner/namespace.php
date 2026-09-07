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

	// Fail fast: compatibility (remote sources) and license.
	$probe = new Cloner( $source_url );
	if ( ! $probe->getSourceBookId() && ! $probe->isCompatible( $source_url ) ) {
		wp_send_json_error( [
			'message' => __( 'You can only clone from a book hosted by Pressbooks 4.1 or later. Please ensure that your source book meets these requirements.', 'pressbooks' ),
		], 400 );
		return;
	}
	$metadata = $probe->getBookMetadata( $probe->getSourceBookUrl() );
	if ( empty( $metadata ) ) {
		wp_send_json_error( [
			/* translators: %s: source book URL */
			'message' => sprintf( __( 'Could not retrieve metadata from %s.', 'pressbooks' ), $source_url ),
		], 400 );
		return;
	}
	if ( ! $probe->isSourceCloneable( $metadata['license'] ?? '' ) ) {
		wp_send_json_error( [
			/* translators: %s: source book title */
			'message' => sprintf( __( '%s is not licensed for cloning.', 'pressbooks' ), $metadata['name'] ?? $source_url ),
		], 400 );
		return;
	}

	CloneJobs::ensureTable();

	// Duplicate guard: one active job per user + target.
	$existing = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )
		->where( 'user_id', get_current_user_id() )
		->where( 'target_url', $target_url )
		->whereIn( 'status', [ CloneJobs::STATUS_PENDING, CloneJobs::STATUS_PROCESSING ] )
		->first();
	if ( $existing ) {
		wp_send_json_error( [ 'message' => __( 'A clone job for this book is already in progress.', 'pressbooks' ) ], 409 );
		return;
	}

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
