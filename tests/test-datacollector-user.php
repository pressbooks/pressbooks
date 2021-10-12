<?php

use Pressbooks\DataCollector\User as UserDataCollector;

class DataCollector_UserTest extends \WP_UnitTestCase {
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
