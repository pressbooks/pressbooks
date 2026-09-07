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
