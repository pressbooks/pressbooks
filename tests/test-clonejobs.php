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

	private function seedJob( array $overrides = [] ): int {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . CloneJobs::JOBS_TABLE_NAME );
		CloneJobs::createJobTable();
		$defaults = [
			'user_id' => 1,
			'source_url' => 'https://example.com/source',
			'target_url' => 'example.com/target/',
			'target_title' => 'Target Book',
			'status' => CloneJobs::STATUS_PENDING,
			'created_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		];
		return app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )
			->insertGetId( array_merge( $defaults, $overrides ) );
	}

	private function makeClonerStub( \Generator $generator = null, int $target_book_id = 0 ) {
		return new class( $generator, $target_book_id ) extends \Pressbooks\Cloner\Cloner {
			private $gen;
			private $fakeTargetId;
			public function __construct( $gen, $fake_target_id ) {
				$this->gen = $gen;
				$this->fakeTargetId = $fake_target_id;
				// Deliberately skip parent constructor: no HTTP, no H5P bootstrapping.
			}
			public function dependencies( $h5p = null, $downloads = null, $contributors = null ) {}
			public function cloneBookGenerator(): \Generator {
				yield from $this->gen;
			}
			public function getTargetBookId() {
				return $this->fakeTargetId;
			}
			public function getTargetBookUrl() {
				return 'https://example.com/target';
			}
			public function getTargetBookTitle() {
				return 'Target Book';
			}
			public function getClonedItems() {
				return [
					'terms' => [ 1, 2 ],
					'front-matter' => [ 1 ],
					'parts' => [ 1 ],
					'chapters' => [ 1, 2, 3 ],
					'back-matter' => [],
					'media' => [ 1 ],
					'h5p' => [],
					'glossary' => [],
					'theme' => true,
				];
			}
			public function getSourceTheme(): array {
				return [ 'name' => 'McLuhan', 'version' => '1.0.0', 'stylesheet' => 'pressbooks-book' ];
			}
		};
	}

	/**
	 * @test
	 */
	public function it_ignores_missing_or_non_pending_jobs(): void {
		$job_id = $this->seedJob( [ 'status' => CloneJobs::STATUS_COMPLETED ] );

		CloneJobs::handle( 999999 ); // Missing: must not throw.
		CloneJobs::handle( $job_id );

		$job = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )->where( 'id', $job_id )->first();
		$this->assertEquals( CloneJobs::STATUS_COMPLETED, $job->status, 'Non-pending job must not be reprocessed' );
	}

	/**
	 * @test
	 */
	public function it_completes_job_and_records_progress_and_summary(): void {
		$job_id = $this->seedJob();

		$generator = ( function (): \Generator {
			yield 1 => 'Looking up the source book';
			yield 10 => 'Creating the target book';
			yield 50 => 'Cloning parts and chapters';
			yield 100 => 'Finishing up';
		} )();

		$stub = $this->makeClonerStub( $generator, 123 );
		add_filter( 'pb_clone_job_cloner', function () use ( $stub ) {
			return $stub;
		} );

		CloneJobs::handle( $job_id );
		remove_all_filters( 'pb_clone_job_cloner' );

		$job = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )->where( 'id', $job_id )->first();

		$this->assertEquals( CloneJobs::STATUS_COMPLETED, $job->status );
		$this->assertEquals( 100, (int) $job->progress_percentage );
		$this->assertEquals( 123, (int) $job->target_book_id );
		$this->assertNotEmpty( $job->job_started_at );
		$this->assertNotEmpty( $job->job_completed_at );

		$summary = json_decode( $job->cloned_items, true );
		$this->assertEquals( 3, $summary['counts']['chapters'] );
		$this->assertEquals( 2, $summary['counts']['terms'] );
		$this->assertTrue( $summary['theme_applied'] );
		$this->assertEquals( 'https://example.com/target', $summary['target_book_url'] );
	}
}
