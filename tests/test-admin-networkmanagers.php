<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/networkmanagers/namespace.php' );

class Admin_NetworkManagers extends \WP_UnitTestCase {


	public function test_add_menu() {
		\Pressbooks\Admin\NetworkManagers\add_menu();
		$this->assertTrue( true ); // Did not crash
	}

	public function test_admin_enqueues() {
		global $wp_scripts, $wp_styles;
		\Pressbooks\Admin\NetworkManagers\admin_enqueues();
		$this->assertContains( 'pb-network-managers', $wp_scripts->queue );
		$this->assertContains( 'pb-network-managers', $wp_styles->queue );
	}


	public function test_update_admin_status_AND_is_restricted() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-managers' );
		$_POST['admin_id'] = $user_id;
		$_POST['status'] = '1';
		\Pressbooks\Admin\NetworkManagers\update_admin_status();
		$this->assertTrue( \Pressbooks\Admin\NetworkManagers\is_restricted() );

		$_POST['status'] = '0';
		\Pressbooks\Admin\NetworkManagers\update_admin_status();
		$this->assertFalse( \Pressbooks\Admin\NetworkManagers\is_restricted() );
	}


	public function test_permitted_setting_menus() {
		$allowed = \Pressbooks\Admin\NetworkManagers\permitted_setting_menus();
		$this->assertTrue( is_array( $allowed ) );
		$this->assertContains( 'pb_analytics', $allowed );
		$this->assertContains( 'pb_whitelabel_settings', $allowed );
	}

}