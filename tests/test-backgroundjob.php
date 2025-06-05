<?php

use Pressbooks\Modules\BackgroundProcessing\BackgroundJob;

/**
 * @group backgroundjob
 */
class BackgroundJobTest extends \WP_UnitTestCase {

	/**
	 * @test
	 */
	public function it_creates_export_jobs_table(): void {
		global $wpdb;

		$table_name = $wpdb->prefix . BackgroundJob::JOBS_TABLE_NAME;

        $wpdb->query( "DROP TABLE IF EXISTS {$table_name}" );

        BackgroundJob::createJobTable();

        $this->assertTrue( app('db')->schema()->hasTable( BackgroundJob::JOBS_TABLE_NAME ), "Table should exist after ensureExportsTable()" );
	}

	/**
	 * @test
	 */
	public function it_marks_job_failed_if_exporter_missing(): void {
		global $wpdb;

        // Drop table if it already exists to avoid duplicate primary key error
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . BackgroundJob::JOBS_TABLE_NAME );
		BackgroundJob::createJobTable();

		$job_data = [
			'book_id'                  => 1,
			'user_id'                  => 1,
			'export_format'            => 'pdf',
			'export_module_classname'  => 'NonExistentExporter',
			'export_options'           => null,
			'status'                   => 'pending',
			'progress_percentage'      => 0,
			'progress_message'         => '',
			'output_file_path'         => '',
			'log_details'              => '',
			'job_started_at'           => current_time( 'mysql', true ),
			'job_completed_at'         => null,
			'created_at'               => current_time( 'mysql', true ),
			'updated_at'               => current_time( 'mysql', true ),
		];
		
        $job_id = app( 'db' )->table( BackgroundJob::JOBS_TABLE_NAME )
			->insertGetId( $job_data );

		BackgroundJob::handle( $job_id );

		$job = app( 'db' )->table( BackgroundJob::JOBS_TABLE_NAME )
			->where( 'id', $job_id )
			->first();
		$updated_status = $job->status;
		$updated_message = $job->progress_message;

		$this->assertEquals( 'failed', $updated_status, "El status debe pasar a 'failed' si la clase no existe" );
		$this->assertStringContainsString(
			'Exporter class NonExistentExporter not found',
			$updated_message,
			"El mensaje de progreso debe indicar que no se encontró la clase exportadora"
		);
	}

	/**
	 * @test
	 */
	public function it_completes_job_when_export_successful(): void {
		global $wpdb;

        // Drop table if it already exists to avoid duplicate primary key error
        $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . BackgroundJob::JOBS_TABLE_NAME );
		eval( '
		namespace Pressbooks\Modules\Export;
		class DummyExporter extends Export {
			public function __construct( $args = [] ) {}
			public function convert(): \Generator {
				$this->outputPath = \'dummy_output_path\';
				yield [ "progress" => 50, "message" => "Halfway there" ];
				return true;
			}
			public function validate(): \Generator {
				yield [ "progress" => 75, "message" => "Validating content" ];
				return true;
			}
		}
		' );

		BackgroundJob::ensureExportsTable();

		$job_data = [
			'book_id'                  => 1,
			'user_id'                  => 1,
			'export_format'            => 'pdf',
			'export_module_classname'  => 'Pressbooks\Modules\Export\DummyExporter',
			'export_options'           => null,
			'status'                   => 'pending',
			'progress_percentage'      => 0,
			'progress_message'         => '',
			'output_file_path'         => '',
			'log_details'              => '',
			'job_started_at'           => current_time( 'mysql', true ),
			'job_completed_at'         => null,
			'created_at'               => current_time( 'mysql', true ),
			'updated_at'               => current_time( 'mysql', true ),
		];
		$job_id = app( 'db' )->table( BackgroundJob::JOBS_TABLE_NAME )
			->insertGetId( $job_data );

		BackgroundJob::handle( $job_id );

		$job = app( 'db' )->table( BackgroundJob::JOBS_TABLE_NAME )
			->where( 'id', $job_id )
			->first();

		$this->assertEquals( 'completed', $job->status, "Status must be 'completed' after a successful export" );
		$this->assertEquals( 100, intval( $job->progress_percentage ), "Percentage of progress should be 100% on completion" );
		$this->assertEquals( 'Export completed successfully.', $job->progress_message, "The message should indicate success" );
		$this->assertNotEmpty( $job->output_file_path, "You must set the output_file_path upon completion" );
	}
}