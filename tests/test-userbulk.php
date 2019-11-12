<?php

use Pressbooks\Admin\Users\UserBulk;

class UserBulkTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Admin\Users\UserBulk
	 */
	protected $user_bulk;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->user_bulk = new UserBulk();
	}

	/**
	 * @group userbulk
	 */
	public function test_hooks() {
		$this->user_bulk->hooks( $this->user_bulk );
		$this->assertEquals( true, has_action( 'admin_menu', [ $this->user_bulk, 'addMenu' ] ) );
	}

	/**
	 * @group userbulk
	 */
	public function test_init() {
		$instance = UserBulk::init();
		$this->assertTrue( $instance instanceof UserBulk );
	}

	/**
	 * @group userbulk
	 */
	public function test_addMenu() {
		$this->user_bulk->addMenu( );
		$this->assertTrue( true ); // Did not crash
	}
}
