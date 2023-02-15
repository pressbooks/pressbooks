<?php

namespace Pressbooks\Admin\Menus\Sidebar;

class SuperAdminSideBar {

	public static function init(): void {
		( new self() )->hooks();
	}

	public function hooks(): void {
		add_action( 'network_admin_menu', [ $this, 'manageNetworkAdminMenu' ], 999 );
		add_action( 'admin_menu', [ $this, 'manageAdminMenu' ], 999 );

		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', [ $this, 'reorderMenu' ], 999 );

		remove_action( 'admin_init', '\Pressbooks\Admin\NetworkManagers\restrict_access' );
	}

	public function manageNetworkAdminMenu(): void {
		$this->removeNetworkManagerLegacyItems();
		$this->addMenuItems();
	}
}
