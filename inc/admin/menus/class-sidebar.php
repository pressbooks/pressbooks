<?php

namespace Pressbooks\Admin\Menus;

use Pressbooks\Book;
use function Pressbooks\Admin\Laf\network_admin_menu;

class SideBar {

	public static function init(): void {
		( new self() )->hooks();
	}

	public function hooks(): void {
		add_action( 'network_admin_menu', [ $this, 'removeNetworkManagerLegacyItems' ], 9999 );
		add_action( 'admin_menu', [ $this, 'removeAdminLegacyItems' ], 9999 );

		add_action( 'network_admin_menu', [ $this, 'addNetworkManagerItems' ], 9999 );
		add_action( 'admin_menu', [ $this, 'addAdminItems' ], 9999 );

		add_filter( 'admin_menu_order', [ $this, 'reorderAdminMenu' ], 9999 );

	}

	public function reorderAdminMenu(): array {
		return [
			'index.php',
			'themes.php',
			'edit.php?post_type=page',
			'users.php',
			'options-general.php',
			'admin.php',
		];
	}

	public function removeAdminLegacyItems(): void {
		remove_submenu_page('index.php', 'pb_catalog' );
		remove_submenu_page('index.php', 'koko-analytics' );

		remove_menu_page( 'pb_home_page' );
		remove_menu_page( 'upload.php' );
		remove_menu_page( 'plugins.php' );
		remove_menu_page( 'tools.php' );
		remove_menu_page( 'options-general.php' );
	}

	public function addAdminItems(): void {
		add_menu_page(
			__( 'Books', 'pressbooks' ),
			__( 'Books', 'pressbooks' ),
			'manager_network',
			network_admin_url('sites.php'),
			'',
			'dashicons-book-alt',
			6
		);

		add_menu_page(
			__( 'Stats', 'pressbooks' ),
			__( 'Stats', 'pressbooks' ),
			'manager_network',
			network_admin_url('admin.php?page=pb_network_analytics_admin'),
			'',
			'dashicons-book-alt',
			7
		);

		\Pressbooks\Admin\Dashboard\init_network_integrations_menu();
	}

	public function removeNetworkManagerLegacyItems(): void {
		remove_submenu_page( 'sites.php', 'pb_network_analytics_booklist' );
		remove_submenu_page( 'sites.php', 'site-new.php' );
		remove_submenu_page( 'users.php', 'pb_network_analytics_userlist' );
		remove_submenu_page( 'users.php', 'user-new.php' );
		remove_submenu_page( 'users.php', 'user_bulk_new' );

		remove_menu_page( 'wp-sentry-tools-menu' );
	}

	public function addNetworkManagerItems(): void {
		add_menu_page(
			__( 'Appearance', 'pressbooks' ),
			__( 'Appearance', 'pressbooks' ),
			'manage_network',
			admin_url( 'themes.php' ),
			'',
			'dashicons-book-alt',
			4
		);

		add_menu_page(
			__( 'Pages', 'pressbooks' ),
			__( 'Pages', 'pressbooks' ),
			'manage_network',
			admin_url( 'edit.php?post_type=page' ),
			'',
			'dashicons-book-alt',
			5
		);
	}

}
