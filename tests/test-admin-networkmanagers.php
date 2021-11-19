<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/networkmanagers/namespace.php' );

class Admin_NetworkManagers extends \WP_UnitTestCase {


	/**
	 * @group networkmanagers
	 */
	public function test_add_menu() {
		\Pressbooks\Admin\NetworkManagers\add_menu();
		$this->assertTrue( true ); // Did not crash
	}

	/**
	 * @group networkmanagers
	 */
	public function test_admin_enqueues() {
		global $wp_scripts, $wp_styles;
		\Pressbooks\Admin\NetworkManagers\admin_enqueues();
		$this->assertContains( 'pb-network-managers', $wp_scripts->queue );
		$this->assertContains( 'pb-network-managers', $wp_styles->queue );
	}

	/**
	 * @group networkmanagers
	 */
	public function test_update_admin_status_AND_is_restricted_AND_hide_network_menus() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-managers' );
		$_POST['admin_id'] = $user_id;
		$_POST['status'] = '1';
		\Pressbooks\Admin\NetworkManagers\update_admin_status();
		$this->assertTrue( \Pressbooks\Admin\NetworkManagers\is_restricted() );

		$my_menu[] = [
			'Plugins',
			'manage_network_plugins',
			'plugins.php',
			'',
			'menu-top menu-icon-plugins',
			'menu-plugins',
			'dashicons-admin-plugins',
		];
		$my_menu[] = [
			'Settings',
			'manage_network_options',
			'settings.php',
			'',
			'menu-top menu-icon-settings menu-top-last',
			'menu-settings',
			'dashicons-admin-settings',
		];
		$my_submenu['settings.php'][] = [
			'Network Setup',
			'setup_network',
			'setup.php',
		];
		$my_submenu['settings.php'][] = [
			'Google Analytics',
			'manage_network_options',
			'pb_analytics',
			'Google Analytics',
		];

		global $menu, $submenu;
		$menu = $my_menu;
		\Pressbooks\Admin\NetworkManagers\hide_network_menus();
		$this->assertEmpty( $menu );
		$menu = $my_menu;
		$submenu = $my_submenu;
		\Pressbooks\Admin\NetworkManagers\hide_network_menus();
		$this->assertCount( 1, $menu );
		$this->assertEquals( 'settings.php', $menu[1][2] );
		$this->assertCount( 1, $submenu['settings.php'] );
		$this->assertEquals( 'pb_analytics', $submenu['settings.php'][1][2] );

		$_POST['status'] = '0';
		\Pressbooks\Admin\NetworkManagers\update_admin_status();
		$this->assertFalse( \Pressbooks\Admin\NetworkManagers\is_restricted() );
	}

	/**
	 * @group networkmanagers
	 */
	public function test_permitted_setting_menus() {
		$allowed = \Pressbooks\Admin\NetworkManagers\permitted_setting_menus();
		$this->assertTrue( is_array( $allowed ) );
		$this->assertContains( 'pb_analytics', $allowed );
		$this->assertContains( 'pb_whitelabel_settings', $allowed );
		$this->assertContainsg( 'pressbooks_sharingandprivacy_options', $allowed );
		$this->assertContains( 'pb_network_analytics_options', $allowed );
	}
}
