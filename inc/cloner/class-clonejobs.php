<?php
/**
 * Background clone jobs: table management and WP-Cron job handler.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Cloner;

class CloneJobs {

	const JOBS_TABLE_NAME = 'pressbooks_clone_jobs';

	const STATUS_PENDING = 'pending';
	const STATUS_PROCESSING = 'processing';
	const STATUS_COMPLETED = 'completed';
	const STATUS_FAILED = 'failed';

	/**
	 * Creates the clone jobs table if it doesn't exist.
	 */
	public static function ensureTable(): void {
		if ( ! app( 'db' )->schema()->hasTable( self::JOBS_TABLE_NAME ) ) {
			self::createJobTable();
		}
	}

	/**
	 * Creates the clone jobs table.
	 */
	public static function createJobTable(): void {
		if ( ! app( 'db' )->schema()->hasTable( self::JOBS_TABLE_NAME ) ) {
			app( 'db' )->schema()->create( self::JOBS_TABLE_NAME, function ( $table ) {
				$table->bigIncrements( 'id' );
				$table->bigInteger( 'user_id' )->unsigned();
				$table->string( 'source_url', 255 );
				$table->string( 'target_url', 255 );
				$table->string( 'target_title', 255 )->nullable();
				$table->bigInteger( 'target_book_id' )->unsigned()->nullable();
				$table->string( 'status', 20 )->default( self::STATUS_PENDING );
				$table->integer( 'progress_percentage' )->default( 0 );
				$table->text( 'progress_message' )->nullable();
				$table->longText( 'cloned_items' )->nullable();
				$table->longText( 'log_details' )->nullable();
				$table->dateTime( 'job_started_at' )->nullable();
				$table->dateTime( 'job_completed_at' )->nullable();
				$table->timestamp( 'created_at' )->useCurrent();
				$table->timestamp( 'updated_at' )->useCurrent()->useCurrentOnUpdate();
				$table->index( 'user_id' );
				$table->index( 'status' );
				$table->index( 'created_at' );
			});
		}
	}

	/**
	 * Handles the background processing of a clone job. Triggered by WP-Cron.
	 *
	 * @param int $job_id
	 */
	public static function handle( $job_id ) {
		set_time_limit( 0 );

		$job = app( 'db' )->table( self::JOBS_TABLE_NAME )
			->where( 'id', $job_id )
			->first();

		if ( ! $job ) {
			error_log( 'CloneJobs::handle(Job ID: ' . $job_id . '): Job not found.' );
			return;
		}

		// Atomically claim the job: only one worker can flip it from pending to
		// processing, so a WP-Cron double fire cannot clone the same job twice.
		$claimed = app( 'db' )->table( self::JOBS_TABLE_NAME )
			->where( 'id', $job_id )
			->where( 'status', self::STATUS_PENDING )
			->update( [
				'status' => self::STATUS_PROCESSING,
				'progress_percentage' => 0,
				'progress_message' => __( 'Starting clone…', 'pressbooks' ),
				'job_started_at' => current_time( 'mysql', true ),
				'updated_at' => current_time( 'mysql', true ),
			] );

		if ( ! $claimed ) {
			// Already claimed by another worker, or no longer pending.
			return;
		}

		// Restore the queueing user: Cloner snapshots super-admin status in its
		// constructor and wpmu_create_blog() assigns ownership from the current user.
		wp_set_current_user( (int) $job->user_id );

		/**
		 * Filter the Cloner instance used by the background job. Primarily a test seam.
		 *
		 * @param \Pressbooks\Cloner\Cloner $cloner
		 * @param object $job The job row.
		 */
		$cloner = apply_filters(
			'pb_clone_job_cloner',
			new Cloner( $job->source_url, $job->target_url, $job->target_title ),
			$job
		);

		$target_book_recorded = false;

		try {
			foreach ( $cloner->cloneBookGenerator() as $percentage => $info ) {
				$data = [
					'progress_percentage' => (int) $percentage,
					'progress_message' => $info,
				];
				if ( ! $target_book_recorded && $cloner->getTargetBookId() ) {
					$data['target_book_id'] = (int) $cloner->getTargetBookId();
					$target_book_recorded = true;
				}
				self::update( $job_id, $data );
			}

			self::update( $job_id, [
				'status' => self::STATUS_COMPLETED,
				'progress_percentage' => 100,
				'progress_message' => __( 'Cloning succeeded!', 'pressbooks' ),
				'target_book_id' => (int) $cloner->getTargetBookId(),
				'cloned_items' => wp_json_encode( self::summarize( $cloner ) ),
				'job_completed_at' => current_time( 'mysql', true ),
			] );
		} catch ( \Throwable $e ) {
			// The generator may throw while switched into the target blog.
			while ( is_multisite() && ms_is_switched() ) {
				restore_current_blog();
			}

			$errors = array_merge( [ $e->getMessage() ], $cloner->getErrors() );

			$target_book_id = (int) $cloner->getTargetBookId();
			$cleanup_note = '';
			if ( $target_book_id ) {
				try {
					if ( ! function_exists( 'wpmu_delete_blog' ) ) {
						require_once ABSPATH . 'wp-admin/includes/ms.php';
					}
					wpmu_delete_blog( $target_book_id, true );
					$remaining_site = get_site( $target_book_id );
					if ( $remaining_site && empty( $remaining_site->deleted ) ) {
						$cleanup_note = __( 'The partially created book could not be deleted automatically. Please remove it manually.', 'pressbooks' );
					} else {
						$cleanup_note = __( 'The partially created book was deleted.', 'pressbooks' );
					}
				} catch ( \Throwable $cleanup_error ) {
					error_log( 'CloneJobs::handle(Job ID: ' . $job_id . '): Cleanup failed: ' . $cleanup_error->getMessage() );
					$cleanup_note = sprintf(
						/* translators: %s: error message explaining why the partially created book could not be removed */
						__( 'The partially created book could not be deleted automatically: %s', 'pressbooks' ),
						$cleanup_error->getMessage()
					);
				}
			}

			error_log( 'CloneJobs::handle(Job ID: ' . $job_id . '): Exception during clone: ' . $e->getMessage() );

			self::update( $job_id, [
				'status' => self::STATUS_FAILED,
				'progress_message' => trim( $e->getMessage() . ' ' . $cleanup_note ),
				'target_book_id' => $target_book_id ? $target_book_id : null,
				'log_details' => wp_json_encode( [
					'errors' => $errors,
					'cleanup' => $cleanup_note,
				] ),
				'job_completed_at' => current_time( 'mysql', true ),
			] );
		}
	}

	/**
	 * Build the completion summary stored on the job row and rendered by the UI.
	 *
	 * @param Cloner $cloner
	 * @return array
	 */
	protected static function summarize( Cloner $cloner ): array {
		$cloned_items = $cloner->getClonedItems();
		$count = function ( $key ) use ( $cloned_items ) {
			return is_countable( $cloned_items[ $key ] ?? null ) ? count( $cloned_items[ $key ] ) : 0;
		};
		return [
			'counts' => [
				'terms' => $count( 'terms' ),
				'front-matter' => $count( 'front-matter' ),
				'parts' => $count( 'parts' ),
				'chapters' => $count( 'chapters' ),
				'back-matter' => $count( 'back-matter' ),
				'media' => $count( 'media' ),
				'h5p' => $count( 'h5p' ),
				'glossary' => $count( 'glossary' ),
			],
			'theme_applied' => ! empty( $cloned_items['theme'] ),
			'source_theme' => $cloner->getSourceTheme(),
			'target_book_url' => $cloner->getTargetBookUrl(),
			'target_book_title' => $cloner->getTargetBookTitle(),
		];
	}

	/**
	 * Update a job row, always bumping updated_at (UTC).
	 *
	 * @param int $job_id
	 * @param array $data
	 */
	protected static function update( int $job_id, array $data ): void {
		$data['updated_at'] = current_time( 'mysql', true );
		app( 'db' )->table( self::JOBS_TABLE_NAME )
			->where( 'id', $job_id )
			->update( $data );
	}
}
