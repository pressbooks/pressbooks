<?php

use Pressbooks\Cloner\CloneJobs;

/**
 * @group clonejobs
 */
class ClonerNamespaceTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function set_up() {
		parent::set_up();
		update_site_option( 'pressbooks_sharingandprivacy_options', [ 'enable_cloning' => 1 ] );
	}

	private function callAjax( callable $fn ): string {
		$reporting = $this->_fakeAjax();
		ob_start();
		try {
			$fn();
			$output = ob_get_clean();
		} catch ( \WPAjaxDieContinueException $e ) {
			$output = ob_get_clean();
		}
		$this->_fakeAjaxDone( $reporting );
		return $output;
	}

	/**
	 * @test
	 */
	public function queue_rejects_users_without_permission(): void {
		update_site_option( 'registration', 'none' ); // Otherwise can_create_new_books() lets anyone through.
		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );

		$output = $this->callAjax( 'Pressbooks\Cloner\queue_clone_job' );

		$this->assertStringContainsString( 'Permission denied', $output );
		unset( $_REQUEST['_wpnonce'] );
		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function queue_rejects_invalid_target_book_name(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );
		$_POST['source_book_url'] = 'https://example.com/source';
		$_POST['target_book_url'] = 'UPPER CASE INVALID';

		$output = $this->callAjax( 'Pressbooks\Cloner\queue_clone_job' );

		$this->assertStringContainsString( 'lowercase letters', $output );
		$this->assertStringContainsString( '"success":false', $output );

		unset( $_REQUEST['_wpnonce'], $_POST['source_book_url'], $_POST['target_book_url'] );
		revoke_super_admin( $user_id );
		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function queue_rejects_missing_source_url(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );
		$_POST['target_book_url'] = 'validtarget';

		$output = $this->callAjax( 'Pressbooks\Cloner\queue_clone_job' );

		$this->assertStringContainsString( 'source book URL is required', $output );

		unset( $_REQUEST['_wpnonce'], $_POST['target_book_url'] );
		revoke_super_admin( $user_id );
		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function queue_rejects_duplicate_active_target(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . CloneJobs::JOBS_TABLE_NAME );
		CloneJobs::createJobTable();

		// Active job for the same target owned by a DIFFERENT user.
		$other_user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		$target_url = \Pressbooks\Cloner\Cloner::validateNewBookName( 'duptarget' );
		$this->assertIsString( $target_url );
		app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )->insert( [
			'user_id' => $other_user_id,
			'source_url' => 'https://example.com/source',
			'target_url' => $target_url,
			'target_title' => 'Dup',
			'status' => CloneJobs::STATUS_PROCESSING,
			'created_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		] );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );
		$_POST['source_book_url'] = 'https://example.com/source';
		$_POST['target_book_url'] = 'duptarget';

		$output = $this->callAjax( 'Pressbooks\Cloner\queue_clone_job' );

		$this->assertStringContainsString( 'already in progress', $output );
		$this->assertStringContainsString( '"success":false', $output );

		unset( $_REQUEST['_wpnonce'], $_POST['source_book_url'], $_POST['target_book_url'] );
		revoke_super_admin( $user_id );
		wp_set_current_user( 0 );
	}

	private function seedStatusJob( int $user_id, array $overrides = [] ): int {
		global $wpdb;
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}" . CloneJobs::JOBS_TABLE_NAME );
		CloneJobs::createJobTable();
		$defaults = [
			'user_id' => $user_id,
			'source_url' => 'https://example.com/source',
			'target_url' => 'example.com/target/',
			'target_title' => 'Target Book',
			'status' => CloneJobs::STATUS_PROCESSING,
			'progress_percentage' => 40,
			'progress_message' => 'Cloning parts and chapters',
			'created_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		];
		return app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )
			->insertGetId( array_merge( $defaults, $overrides ) );
	}

	/**
	 * @test
	 */
	public function status_returns_own_job(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$job_id = $this->seedStatusJob( $user_id );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );
		$_GET['job_id'] = (string) $job_id;

		$output = $this->callAjax( 'Pressbooks\Cloner\clone_job_status' );

		$this->assertStringContainsString( '"success":true', $output );
		$this->assertStringContainsString( '"status":"processing"', $output );
		$this->assertStringContainsString( '"progress_percentage":40', $output );

		unset( $_REQUEST['_wpnonce'], $_GET['job_id'] );
		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function status_hides_other_users_jobs(): void {
		$owner_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		$intruder_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		$job_id = $this->seedStatusJob( $owner_id );
		wp_set_current_user( $intruder_id );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );
		$_GET['job_id'] = (string) $job_id;

		$output = $this->callAjax( 'Pressbooks\Cloner\clone_job_status' );

		$this->assertStringContainsString( '"success":false', $output );
		$this->assertStringContainsString( 'Job not found', $output );

		unset( $_REQUEST['_wpnonce'], $_GET['job_id'] );
		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function status_reports_stale_processing_job_as_failed(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$job_id = $this->seedStatusJob( $user_id, [
			'updated_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS ),
		] );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );
		$_GET['job_id'] = (string) $job_id;

		$output = $this->callAjax( 'Pressbooks\Cloner\clone_job_status' );

		$this->assertStringContainsString( '"status":"failed"', $output );
		$this->assertStringContainsString( 'timed out', $output );

		$job = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )->where( 'id', $job_id )->first();
		$this->assertEquals( CloneJobs::STATUS_FAILED, $job->status, 'Staleness must be persisted' );

		unset( $_REQUEST['_wpnonce'], $_GET['job_id'] );
		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function status_reports_stale_pending_job_as_failed(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$job_id = $this->seedStatusJob( $user_id, [
			'status' => CloneJobs::STATUS_PENDING,
			'progress_percentage' => 0,
			'progress_message' => '',
			'created_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS ),
			'updated_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS ),
		] );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );
		$_GET['job_id'] = (string) $job_id;

		$output = $this->callAjax( 'Pressbooks\Cloner\clone_job_status' );

		$this->assertStringContainsString( '"status":"failed"', $output );
		$this->assertStringContainsString( 'could not be started', $output );

		$job = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )->where( 'id', $job_id )->first();
		$this->assertEquals( CloneJobs::STATUS_FAILED, $job->status, 'Pending timeout must be persisted' );

		unset( $_REQUEST['_wpnonce'], $_GET['job_id'] );
		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function status_does_not_fail_a_fresh_pending_job(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$job_id = $this->seedStatusJob( $user_id, [
			'status' => CloneJobs::STATUS_PENDING,
			'progress_percentage' => 0,
			'progress_message' => '',
		] );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );
		$_GET['job_id'] = (string) $job_id;

		$output = $this->callAjax( 'Pressbooks\Cloner\clone_job_status' );

		$this->assertStringContainsString( '"status":"pending"', $output );

		$job = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )->where( 'id', $job_id )->first();
		$this->assertEquals( CloneJobs::STATUS_PENDING, $job->status, 'A fresh pending job must not be timed out' );

		unset( $_REQUEST['_wpnonce'], $_GET['job_id'] );
		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function status_without_job_id_returns_latest_active_job(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$job_id = $this->seedStatusJob( $user_id );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );

		$output = $this->callAjax( 'Pressbooks\Cloner\clone_job_status' );

		$this->assertStringContainsString( '"success":true', $output );
		$this->assertStringContainsString( '"job_id":' . $job_id, $output );

		unset( $_REQUEST['_wpnonce'] );
		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function status_resume_skips_finished_jobs_and_prefers_newest(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		// seedStatusJob drops + recreates the table, so call it once, then insert extras.
		$completed_id = $this->seedStatusJob( $user_id, [ 'status' => CloneJobs::STATUS_COMPLETED ] );
		$older_active_id = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )->insertGetId( [
			'user_id' => $user_id,
			'source_url' => 'https://example.com/source',
			'target_url' => 'example.com/older/',
			'target_title' => 'Older',
			'status' => CloneJobs::STATUS_PROCESSING,
			'created_at' => gmdate( 'Y-m-d H:i:s', time() - 100 ),
			'updated_at' => current_time( 'mysql', true ),
		] );
		$newest_active_id = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )->insertGetId( [
			'user_id' => $user_id,
			'source_url' => 'https://example.com/source',
			'target_url' => 'example.com/newest/',
			'target_title' => 'Newest',
			'status' => CloneJobs::STATUS_PENDING,
			'created_at' => current_time( 'mysql', true ),
			'updated_at' => current_time( 'mysql', true ),
		] );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );

		$output = $this->callAjax( 'Pressbooks\Cloner\clone_job_status' );

		$this->assertStringContainsString( '"success":true', $output );
		$this->assertStringContainsString( '"job_id":' . $newest_active_id, $output );

		unset( $_REQUEST['_wpnonce'] );
		wp_set_current_user( 0 );
	}

	/**
	 * @test
	 */
	public function staleness_update_does_not_clobber_completed_job(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$job_id = $this->seedStatusJob( $user_id, [
			'updated_at' => gmdate( 'Y-m-d H:i:s', time() - 2 * HOUR_IN_SECONDS ),
		] );

		// Simulate the worker finishing between the endpoint's SELECT and UPDATE:
		// flip the row to completed BEFORE the endpoint runs; the guarded UPDATE
		// must then match zero rows. (We can't inject mid-request, so we verify
		// the guard by ensuring a completed row is never flipped to failed.)
		app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )
			->where( 'id', $job_id )
			->update( [ 'status' => CloneJobs::STATUS_COMPLETED ] );

		$_REQUEST['_wpnonce'] = wp_create_nonce( 'pb-cloner' );
		$_GET['job_id'] = (string) $job_id;

		$output = $this->callAjax( 'Pressbooks\Cloner\clone_job_status' );

		$this->assertStringContainsString( '"status":"completed"', $output );
		$job = app( 'db' )->table( CloneJobs::JOBS_TABLE_NAME )->where( 'id', $job_id )->first();
		$this->assertEquals( CloneJobs::STATUS_COMPLETED, $job->status, 'Completed job must never be flipped to failed' );

		unset( $_REQUEST['_wpnonce'], $_GET['job_id'] );
		wp_set_current_user( 0 );
	}
}
