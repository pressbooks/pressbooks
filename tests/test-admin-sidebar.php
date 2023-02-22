<?php

use Pressbooks\Admin\Menus\SideBar;

/**
 * @group sidebar
 */
class TestAdminSidebar extends \WP_UnitTestCase {

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
	public function it_tests_super_admin_menu_order(): void {
		$this->createSuperAdminUser();

		global $menu, $submenu;
		include_once( ABSPATH . '/wp-admin/menu.php' );

		$this->sidebar->manageNetworkAdminMenu();
		$this->sidebar->reorderSuperAdminMenu( $menu );

		set_current_screen( 'dashboard-network' );

		$expected_order = [
			'Dashboard',
			'Books',
			'Users',
			'Appearance',
			'Pages',
			'Plugins',
			'Settings',
		];

		// Ignore WP default menu items
		$items_ordered = $this->getMenuItemsNames( array_slice( $menu, 8 ) );

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
		set_current_screen( 'dashboard' );

		$items_ordered = $this->getMenuItemsNames( array_slice( $menu, 8 ) );

		$this->assertEquals( $expected_order, $items_ordered );
	}

	/**
	 * @test
	 */
	public function it_tests_network_admin_menu_order(): void {
		$user_id = $this->createSuperAdminUser();

		// Restrict user to network admin
		add_network_option( null,  'pressbooks_network_managers', [ $user_id ] );

		global $menu, $submenu;
		include_once( ABSPATH . '/wp-admin/menu.php' );

		// network admin
		set_current_screen( 'dashboard-network' );

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

		$items_ordered = $this->getMenuItemsNames( array_slice( $menu, 0, 7 ) );

		$this->assertEquals( $expected_order, array_unique( $items_ordered ) );

		// Root site menu
		set_current_screen( 'dashboard' );

		$items_ordered = $this->getMenuItemsNames( array_slice( $menu, 0, 7 ) );

		$this->assertEquals( $expected_order, array_unique( $items_ordered ) );

	}

	private function getMenuItemsNames( array $menu ): array {
		return array_values(
			array_map(
				static function ( $item ) {
					return $item[0];
				},
				$menu
			)
		);
	}

}
