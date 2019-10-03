<?php

use Pressbooks\DataCollector\User;

class DataCollector_UserTest extends \WP_UnitTestCase {

	/**
	 * @var User
	 */
	protected $user;

	/**
	 * @group datacollector
	 */
	public function setUp() {
		parent::setUp();
		$this->user = new User();
	}

	/**
	 * @group datacollector
	 */
	public function test_setLastLogin() {
		$user = $this->factory()->user->create_and_get( [ 'role' => 'contributor' ] );
		$this->user->setLastLogin( null, $user );
		$last_login = get_user_meta( $user->ID, User::LAST_LOGIN, true );
		$this->assertNotEmpty( $last_login );
		$this->assertTrue( DateTime::createFromFormat( 'Y-m-d H:i:s', $last_login ) !== false );
	}

}
