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
	public function setUp() {
		parent::setUp();
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
		$this->assertTrue( DateTime::createFromFormat( 'Y-m-d H:i:s', $last_login ) !== false );
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
}
