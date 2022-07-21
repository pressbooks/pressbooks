<?php

use Pressbooks\Admin\Network_Managers_List_Table;

class Admin_Network_Managers_List_Table extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var Network_Managers_List_Table;
	 */
	protected $table;

	/**
	 * @group networkmanagers
	 */
	public function set_up() {
		parent::set_up();
		$this->table = new Network_Managers_List_Table();
	}

	/**
	 * @group networkmanagers
	 */
	public function test_prepare_items() {
		$user_id = $this->factory->user->create( [ 'user_login' => 'me@here.com' ] );
		grant_super_admin( $user_id );
		update_site_option( 'pressbooks_network_managers', [ $user_id ] );
		$this->table->prepare_items();
		// Two super-admins
		$this->assertEquals( is_countable( $this->table->items ) ? count( $this->table->items ) : 0, 2 );
		// Normal username
		$this->assertEquals( $this->table->items[0]['user_login'], 'admin' );
		// Weird username
		$this->assertEquals( $this->table->items[1]['user_login'], 'me@here.com' );
		// Unrestricted user
		$this->assertFalse( $this->table->items[0]['restricted'] );
		// Restricted user
		$this->assertTrue( $this->table->items[1]['restricted'] );
	}
}
