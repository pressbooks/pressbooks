<?php

use JetBrains\PhpStorm\NoReturn;
use Pressbooks\Admin\Menus\SideBar;

/**
 * @group sidebar
 */
class testAdminSidebar extends \WP_UnitTestCase {

	use utilsTrait;

	private SideBar $sidebar;

	public function set_up()
	{
		$this->sidebar = new SideBar();
		parent::set_up();
	}

	/**
	 * Check for both super admin and network admin
	 *
	 * @test
	 */
	#[NoReturn] public function it_tests_super_admin_menu_order(): void {
		global $menu, $submenu, $current_screen;

		$this->createSuperAdminUser();

//		set_current_screen( 'dashboard-network' );

		include_once( ABSPATH . '/wp-admin/menu.php' );
		$this->sidebar->manageNetworkAdminMenu();
//		$this->sidebar->reorderSuperAdminMenu( $menu );
//		dd($menu);

		$expected_order = [
			'Dashboard',
			'Books',
			'Users',
			'Appearance',
			'Pages',
			'Plugins',
			'Settings',
			'Integrations',
		];


		$items_ordered = array_values(
				array_map(
				static function ( $item ) {
					return $item[0];
				},
				$menu
			)
		);

		$this->assertEquals( $expected_order, $items_ordered );

		$this->assertEquals( 'Network Settings', $submenu[ network_admin_url( 'settings.php' ) ][0][0] );
		$this->assertEquals( 'Network Setup', $submenu[ network_admin_url( 'settings.php' ) ][1][0] );
		$this->assertEquals( 'Network Managers', $submenu[ network_admin_url( 'settings.php' ) ][2][0] );

		$this->assertEquals( 'Network Plugins', $submenu[ network_admin_url( 'plugins.php' ) ][0][0] );
		$this->assertEquals( 'Root Site Plugins', $submenu[ network_admin_url( 'plugins.php' ) ][1][0] );

		$this->assertEquals( 'Activate Book Themes', $submenu[ 'customize.php' ][1][0] );
		$this->assertEquals( 'Change Root Site Theme', $submenu[ 'customize.php' ][2][0] );
		$this->assertEquals( 'Customize Home Page', $submenu[ 'customize.php' ][3][0] );

		// Root site menu
//		set_current_screen( 'dashboard' );
//
//		$items_ordered = array_values(
//			array_map(
//				static function ( $item ) {
//					return $item[0];
//				},
//				$menu
//			)
//		);
//
//		$this->assertEquals( $expected_order, $items_ordered );
	}

	/**
	 * @test
	 */
	#[NoReturn] public function it_tests_network_admin_menu_order(): void {
		$user_id = $this->createSuperAdminUser();

		// Restrict user to network admin
		add_network_option( null,  'pressbooks_network_managers', [ $user_id ] );

		global $menu, $submenu;
		include_once( ABSPATH . '/wp-admin/menu.php' );

		$this->sidebar->manageAdminMenu();

		$expected_order = [
			'Dashboard',
			'Books',
			'Users',
			'Appearance',
			'Pages',
			'Plugins',
			'Settings',
		];

		$items_ordered = array_slice( $menu, 7 );

		$items_ordered = array_values(
			array_map(
				static function ( $item ) {
					return $item[0];
				},
				$items_ordered
			)
		);

		$this->assertEquals( $expected_order, array_unique( $items_ordered ) );

		// Root site menu
//		global $current_screen;
//		$current_screen = WP_Screen::get( 'edit.php' );

//		$items_ordered = array_slice( $menu, 7 );
//
//		$items_ordered = array_values(
//			array_map(
//				static function ( $item ) {
//					return $item[0];
//				},
//				$items_ordered
//			)
//		);
//
//		$this->assertEquals( $expected_order, array_unique( $items_ordered ) );

	}



}
