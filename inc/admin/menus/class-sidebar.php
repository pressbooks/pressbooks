<?php

namespace Pressbooks\Admin\Menus;

use PressbooksNetworkAnalytics\Admin\Options;
use function Pressbooks\Admin\NetworkManagers\is_restricted;

class SideBar {

	public static function init(): void {
		( new self() )->hooks();
	}

	public function hooks(): void {
		if ( ! is_restricted() ) {
			return;
		}
		add_action( 'network_admin_menu', [ $this, 'manageNetworkAdminMenu' ], 999 );
		add_action( 'admin_menu', [ $this, 'manageAdminMenu' ], 999 );

		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', [ $this, 'reorderMenu' ], 999 );
	}

	public function manageNetworkAdminMenu(): void {
		$this->removeNetworkManagerLegacyItems();
		$this->addMenuItems();
	}

	public function manageAdminMenu(): void {
		$this->removeAdminLegacyItems();
		$this->addMenuItems();
		$this->manageIntegrationsAdminMenuItem();
	}

	public function reorderMenu(): array
	{
		$is_network_analytics_active = is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' );

		$items_order = [
			is_network_admin() ? 'index.php' : network_admin_url( 'index.php' ),
			$is_network_analytics_active ?
				'pb_network_analytics_booklist' :
				( is_network_admin() ? 'sites.php' : network_admin_url( 'sites.php' ) ),
			$is_network_analytics_active ?
				'pb_network_analytics_userlist' :
				( is_network_admin() ? 'users.php' : network_admin_url( 'users.php' ) ),
			admin_url( 'customize.php' ),
			is_network_admin() ? admin_url( 'edit.php?post_type=page' ) : 'edit.php?post_type=page',
		];

		$items_order[] = $is_network_analytics_active ?
			'pb_network_analytics_options' :
			( is_network_admin() ? 'settings.php' : network_admin_url( 'settings.php' ) );

		if ( $is_network_analytics_active ) {
			$items_order[] = ! is_network_admin() ?
				network_admin_url('admin.php?page=pb_network_analytics_admin' ) :
				'admin.php?page=pb_network_analytics_admin';
		} else if ( is_plugin_active( 'koko-analytics/koko-analytics.php' ) ) {
			$items_order[] = ! is_network_admin() ? 'koko-analytics' : admin_url( 'admin.php?page=koko-analytics' );
		}

		return $items_order;
	}

	private function getSettingsSlug(): string {
		return is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' ) ?
			$this->getSlug( 'admin.php?page=pb_network_analytics_options', false ) :
			$this->getSlug('settings.php', false );
	}

	private function getSlug( string $page, bool $admin_url ): string {
		return is_network_admin() ?
			( $admin_url ? admin_url( $page ) : $page ) :
			( $admin_url ? $page : network_admin_url( $page ) );
	}

	private function removeAdminLegacyItems(): void {
		remove_submenu_page('index.php', 'pb_catalog' );
		remove_submenu_page('edit.php?post_type=page', 'post-new.php?post_type=page' );

		remove_menu_page( 'index.php' );
		remove_menu_page( 'themes.php' );
		remove_menu_page( 'edit.php?post_type=page' );
		remove_menu_page( 'pb_home_page' );
		remove_menu_page( 'upload.php' );
		remove_menu_page( 'plugins.php' );
		remove_menu_page( 'tools.php' );
		remove_menu_page( 'options-general.php' );
		remove_menu_page( 'users.php' );
		remove_menu_page( 'separator1' );
		remove_menu_page( 'separator-last' );
		remove_menu_page( 'separator2' );
	}

	private function addMenuItems(): void {
		$is_network_analytics_active = is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' );
		if( $is_network_analytics_active ) {
			$books_callback = [ \PressbooksNetworkAnalytics\Admin\Books::init(), 'printMenuBookList' ];
			$books_slug = 'pb_network_analytics_booklist';

			$users_callback = [ \PressbooksNetworkAnalytics\Admin\Users::init(), 'printMenuUserList' ];
			$users_slug = 'pb_network_analytics_userlist';

			$settings_callback = [ \PressbooksNetworkAnalytics\Admin\Options::init(), 'printMenuSettings' ];
			$settings_slug = 'pb_network_analytics_options';
		} else {
			$books_callback = '';
			$books_slug = is_network_admin() ? 'sites.php' : network_admin_url( 'sites.php' );

			$users_callback = '';
			$users_slug = is_network_admin() ? 'users.php' : network_admin_url( 'users.php' );

			$settings_callback = '';
			$settings_slug = is_network_admin() ? 'settings.php' : network_admin_url( 'settings.php' );
		}

		if ( ! is_network_admin() ) {
			add_menu_page(
				__( 'Dashboard', 'pressbooks' ),
				__( 'Dashboard', 'pressbooks' ),
				'manager_network',
				network_admin_url( 'index.php' ),
				'',
				'dashicons-dashboard',
				1
			);
		}

		add_menu_page(
			__( 'Books', 'pressbooks' ),
			__( 'Books', 'pressbooks' ),
			'manager_network',
			$books_slug,
			$books_callback,
			'dashicons-book-alt',
			2
		);

		add_menu_page(
			__( 'Users', 'pressbooks' ),
			__( 'Users', 'pressbooks' ),
			'manager_network',
			$users_slug,
			$users_callback,
			'dashicons-admin-users',
			3
		);

		add_menu_page(
			__( 'Appearance', 'pressbooks' ),
			__( 'Appearance', 'pressbooks' ),
			'manage_network',
			admin_url( 'customize.php' ),
			'',
			'dashicons-admin-appearance',
			4
		);

		add_menu_page(
			__( 'Pages', 'pressbooks' ),
			__( 'Pages', 'pressbooks' ),
			'manage_network',
			is_network_admin() ? admin_url( 'edit.php?post_type=page' ) : 'edit.php?post_type=page',
			'',
			'dashicons-admin-page',
			5
		);
//		remove_submenu_page('settings.php', 'pb_network_analytics_options');
		add_menu_page(
			__( 'Settings', 'pressbooks' ),
			__( 'Settings', 'pressbooks' ),
			'manager_network',
			$settings_slug,
			$settings_callback,
			'dashicons-admin-settings',
			7
		);



		if ( $is_network_analytics_active ) {
			if ( ! is_network_admin() ) {
				add_menu_page(
					__( 'Stats', 'pressbooks' ),
					__( 'Stats', 'pressbooks' ),
					'manage_network',
					network_admin_url('admin.php?page=pb_network_analytics_admin' ),
					'',
					'dashicons-chart-area',
					7
				);
			}
			if ( is_plugin_active( 'koko-analytics/koko-analytics.php' ) ) {
				add_submenu_page(
					! is_network_admin() ?
						network_admin_url('admin.php?page=pb_network_analytics_admin' ) :
						'pb_network_analytics_admin',
					__( 'Analytics', 'pressbooks' ),
					__( 'Analytics', 'pressbooks' ),
					'view_koko_analytics',
					! is_network_admin() ? 'koko-analytics' : admin_url( 'admin.php?page=koko-analytics' ),
					''
				);
			}
		} else if ( is_plugin_active( 'koko-analytics/koko-analytics.php' ) ) {
			add_menu_page(
				__( 'Stats', 'pressbooks' ),
				__( 'Stats', 'pressbooks' ),
				'view_koko_analytics',
				'pressbooks_network_stats',
				'',
				'dashicons-chart-area',
				7
			);
			add_submenu_page(
				'pressbooks_network_stats',
				__( 'Analytics', 'pressbooks' ),
				__( 'Analytics', 'pressbooks' ),
				'view_koko_analytics',
				! is_network_admin() ? 'koko-analytics' : admin_url( 'admin.php?page=koko-analytics' ),
				''
			);

			// Koko analytics dashboard must be a submenu item to make it work
			remove_submenu_page( 'pressbooks_network_stats', 'pressbooks_network_stats' );
		}
	}

	private function manageIntegrationsAdminMenuItem(): void
	{
		\Pressbooks\Admin\Dashboard\init_network_integrations_menu();

		if (is_plugin_active('pressbooks-cas-sso/pressbooks-cas-sso.php')) {
			\PressbooksCasSso\Admin::init()->addMenu();
		}

		if (is_plugin_active('pressbooks-saml-sso/pressbooks-saml-sso.php')) {
			\PressbooksSamlSso\Admin::init()->addMenu();
		}

		if (is_plugin_active('pressbooks-oidc-sso/pressbooks-oidc-sso.php')) {
			\PressbooksOidcSso\Admin::init()->addMenu();
		}

		if (is_plugin_active('pressbooks-lti-provider-1p3/pressbooks-lti-provider.php')) {
			$lti_admin = \PressbooksLtiProvider1p3\Admin::init();
			$lti_admin->addConsumersMenu();
			$lti_admin->addSettingsMenu();
		}

	}

	private function removeNetworkManagerLegacyItems(): void {
		remove_submenu_page( 'sites.php', 'pb_network_analytics_booklist' );
		remove_submenu_page( 'sites.php', 'site-new.php' );
		remove_submenu_page( 'users.php', 'pb_network_analytics_userlist' );
		remove_submenu_page( 'settings.php', 'pb_network_analytics_options' );
		remove_submenu_page( 'users.php', 'user-new.php' );
		remove_submenu_page( 'users.php', 'user_bulk_new' );

		remove_menu_page( 'settings.php' );
		remove_menu_page( 'users.php' );
		remove_menu_page( 'sites.php' );
		remove_menu_page( 'wp-sentry-tools-menu' );
		remove_menu_page( 'separator1' );
		remove_menu_page( 'separator-last' );
		remove_menu_page( 'separator2' );
	}

}
