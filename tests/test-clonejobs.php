<?php

use Pressbooks\Cloner\CloneJobs;

/**
 * @group clonejobs
 */
class CloneJobsTest extends \WP_UnitTestCase {

	/**
	 * @test
	 */
	public function it_creates_clone_jobs_table(): void {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . CloneJobs::JOBS_TABLE_NAME );

		CloneJobs::ensureTable();

		$this->assertTrue(
			app( 'db' )->schema()->hasTable( CloneJobs::JOBS_TABLE_NAME ),
			'Table should exist after ensureTable()'
		);

		// Idempotent
		CloneJobs::ensureTable();
		$this->assertTrue( app( 'db' )->schema()->hasTable( CloneJobs::JOBS_TABLE_NAME ) );
	}
}
