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
}
