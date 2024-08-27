<?php

use function Pressbooks\Admin\NetworkManagers\update_admin_status;

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

	public function test_it_returns_a_list_of_restricted_users(): void {
		$first_user = new WP_User(
			$this->factory()->user->create()
		);

		$second_user = new WP_user(
			$this->factory()->user->create()
		);

		grant_super_admin( $first_user->ID );
		grant_super_admin( $second_user->ID );

		update_site_option( 'pressbooks_network_managers', [ $first_user->ID, $second_user->ID ] );

		// Check restricted users
		$this->assertEquals( [
			$first_user->ID,
			$second_user->ID,
		], \Pressbooks\Admin\NetworkManagers\_restricted_users() );

		// Force delete the user since WP does not allow deleting super admins
		global $wpdb;

		$meta = $wpdb->get_col( $wpdb->prepare( "SELECT umeta_id FROM $wpdb->usermeta WHERE user_id = %d", $first_user->ID ) );

		foreach ( $meta as $mid ) {
			delete_metadata_by_mid( 'user', $mid );
		}

		$wpdb->delete( $wpdb->users, [ 'ID' => $first_user->ID ] );

		clean_user_cache( $first_user );

		// Check restricted users once again
		$this->assertEquals( [
			$second_user->ID,
		], \Pressbooks\Admin\NetworkManagers\_restricted_users() );
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
		update_admin_status();
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
		update_admin_status();
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
		$this->assertContains( 'pressbooks_sharingandprivacy_options', $allowed );
		$this->assertContains( 'pb_network_analytics_options', $allowed );
	}

	/**
	 * @group networkmanagers
	 */
	public function test_if_pressbooks_network_managers_gets_updated_when_super_admin_privilegies_revoked() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-managers' );
		$_POST['admin_id'] = $user_id;
		$_POST['status'] = '1';
		update_admin_status();
		$this->assertTrue( \Pressbooks\Admin\NetworkManagers\is_restricted() );

		$restricted_users = get_site_option( 'pressbooks_network_managers' );
		$this->assertContains( $user_id, $restricted_users );

		add_action( 'revoked_super_admin', '\Pressbooks\Admin\NetworkManagers\remove_from_pressbooks_network_managers' );

		// Revoke super admin privileges
		revoke_super_admin( $user_id );

		// User should no longer be restricted and should be removed from the list of restricted users
		$restricted_users = get_site_option( 'pressbooks_network_managers' );
		$this->assertNotContains( $user_id, $restricted_users );
	}

	/**
	 * @group networkmanagers
	 */

	public function test_if_pressbooks_network_managers_gets_updated_when_user_gets_deleted() {
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-network-managers' );
		$_POST['admin_id'] = $user_id;
		$_POST['status'] = '1';
		update_admin_status();
		$this->assertTrue( \Pressbooks\Admin\NetworkManagers\is_restricted() );

		$restricted_users = get_site_option( 'pressbooks_network_managers' );
		$this->assertContains( $user_id, $restricted_users );

		revoke_super_admin( $user_id );

		add_action( 'deleted_user', '\Pressbooks\Admin\NetworkManagers\remove_from_pressbooks_network_managers' );

		wp_delete_user( $user_id );

		$restricted_users = get_site_option( 'pressbooks_network_managers' );
		$this->assertNotContains( $user_id, $restricted_users );
	}
}
