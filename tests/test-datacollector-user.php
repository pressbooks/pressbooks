<?php

use Pressbooks\DataCollector\User as UserDataCollector;

class DataCollector_UserTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @var UserDataCollector
	 */
	protected $userDataCollector;

	/**
	 * @group datacollector
	 */
	public function set_up() {
		parent::set_up();

		$this->userDataCollector = new UserDataCollector();
	}

	/**
	 * @group datacollector
	 */
	public function test_setLastLogin() {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'contributor' ] );
		$this->userDataCollector->setLastLogin( null, $user );
		$last_login = get_user_meta( $user->ID, UserDataCollector::LAST_LOGIN, true );
		$this->assertNotEmpty( $last_login );
		$this->assertNotFalse( DateTime::createFromFormat( 'Y-m-d H:i:s', $last_login ) );
	}

	/**
	 * @group datacollector
	 */
	public function test_setSubscriberRole() {
		global $wpdb;

		$user = $this->factory()->user->create_and_get();
		$this->_book();

		$current_blog_id = get_current_blog_id();
		$user->for_site( $current_blog_id ); // simulate user login in a book.

		$this->userDataCollector->setSubscriberRole( '', $user );

		$metadata = get_user_meta( $user->ID );

		$this->assertArrayHasKey( "{$wpdb->base_prefix}capabilities", $metadata );
		$this->assertArrayNotHasKey( "{$wpdb->prefix}capabilities", $metadata );
	}

	/**
	 * @group datacollector
	 */
	public function test_updateNetworkManagers() {
		delete_site_option( 'pressbooks_network_managers_ids' );
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );

		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-managers' );
		$_POST['admin_id'] = $user_id;
		$_POST['status'] = 1;

		\Pressbooks\Admin\NetworkManagers\update_admin_status();

		$this->assertEmpty( get_site_option( 'pressbooks_network_managers_ids', [] ) );

		$this->userDataCollector->updateNetworkManagers();

		$this->assertNotEmpty( get_site_option( 'pressbooks_network_managers_ids' ) );

		$_POST['status'] = 0;

		\Pressbooks\Admin\NetworkManagers\update_admin_status();

		$this->userDataCollector->updateNetworkManagers();

		$this->assertEmpty( get_site_option( 'pressbooks_network_managers_ids', [] ) );
	}

	/**
	 * @group datacollector
	 */
	public function test_updateAllUsersMetadata() {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'contributor' ] );
		$i = 0;
		$this->assertEmpty( get_user_meta( $user->ID, UserDataCollector::HIGHEST_ROLE ) );
		foreach ( $this->userDataCollector->updateAllUsersMetadata() as $_ ) {
			$i++;
		}
		$this->assertTrue( $i > 0 );
		$this->assertNotEmpty( get_user_meta( $user->ID, UserDataCollector::HIGHEST_ROLE ) );
	}

	/**
	 * @group datacollector
	 */
	public function test_updateMetaData() {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'contributor' ] );

		$this->assertEmpty( get_user_meta( $user->ID, UserDataCollector::HIGHEST_ROLE ) );

		$this->userDataCollector->updateMetaData( $user->ID );

		$this->assertNotEmpty( get_user_meta( $user->ID, UserDataCollector::HIGHEST_ROLE ) );
	}

	/**
	 * @group datacollector
	 */
	public function test_storeLastActiveDate() {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user->ID );
		$this->assertEmpty( get_user_meta( $user->ID, UserDataCollector::USER_DATE_LAST_ACTIVE ) );
		$this->userDataCollector::storeLastActiveDate();
		$date_last_active = get_user_meta( $user->ID, UserDataCollector::USER_DATE_LAST_ACTIVE );
		$this->assertNotEmpty( $date_last_active );
		$this->assertGreaterThanOrEqual( strtotime( $date_last_active[0] ), strtotime( gmdate( 'Y-m-d H:i:s' ) ) );
	}
}
