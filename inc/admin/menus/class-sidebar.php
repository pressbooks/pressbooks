<?php

namespace Pressbooks\Admin\Menus;

class SideBar {

	public static function init(): void {
		( new self() )->hooks();
	}

	public function hooks(): void {
		add_action( 'network_admin_menu', [ $this, 'manageNetworkAdminMenu' ], 999 );
		add_action( 'admin_menu', [ $this, 'manageAdminMenu' ], 999 );

		add_filter( 'custom_menu_order', '__return_true' );
		add_filter( 'menu_order', [ $this, 'reorderMenu' ], 999 );
	}

	public function manageNetworkAdminMenu(): void {
		$this->removeNetworkManagerLegacyItems();
		$this->addMenuItems();
		$this->addStatsMenuItem();
	}

	public function manageAdminMenu(): void {
		$this->removeAdminLegacyItems();
		$this->addMenuItems();
		$this->manageIntegrationsAdminMenuItem();
		$this->addStatsMenuItem();
	}

	public function reorderMenu(): array
	{
		$is_network_analytics_active = is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' );
		$is_koko_analytics_active = is_plugin_active( 'koko-analytics/koko-analytics.php' );

		$items_order = [
			'index.php',
			$this->getSiteSlug(),
			$this->getUsersSlug(),
			admin_url( 'customize.php' ),
			admin_url( 'edit.php?post_type=page' ),
		];

		if ( $is_network_analytics_active ) {
			$items_order[] = $this->getSlug( 'admin.php?page=pb_network_analytics', false );
		} else {
			$items_order[] = network_admin_url( 'settings.php' );
		}

		if ( $is_network_analytics_active || $is_koko_analytics_active ) {
			$items_order[] = $is_network_analytics_active ?
				network_admin_url( 'admin.php?page=pb_network_analytics_admin' ) :
				admin_url( 'index.php?page=koko-analytics' );
		}

		return $items_order;
	}

	private function getSiteSlug(): string {
		return is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' ) ?
			network_admin_url( 'admin.php?page=pb_network_analytics_booklist' ) :
			network_admin_url( 'sites.php' );
	}

	private function getUsersSlug(): string {
		return is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' ) ?
			network_admin_url( 'admin.php?page=pb_network_analytics_userlit' ) :
			network_admin_url( 'users.php' );
	}

	private function getSettingsSlug(): string {
		return is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' ) ?
			network_admin_url( 'admin.php?page=pb_network_analytics_options' ) :
			network_admin_url( 'settings.php' );
	}

	private function getSlug( string $page, bool $is_from_main_site ): string {
		return is_network_admin() ?
			( $is_from_main_site ? admin_url( $page ) : $page ) :
			( $is_from_main_site ? $page : network_admin_url( $page ) );
	}

	private function removeAdminLegacyItems(): void {
		remove_submenu_page('index.php', 'pb_catalog' );
		remove_submenu_page('index.php', 'koko-analytics' );

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
		add_menu_page(
			__( 'Books', 'pressbooks' ),
			__( 'Books', 'pressbooks' ),
			'manager_network',
			$this->getSiteSlug(),
			'',
			'dashicons-book-alt',
			2
		);

		add_menu_page(
			__( 'Users', 'pressbooks' ),
			__( 'Users', 'pressbooks' ),
			'manager_network',
			$this->getUsersSlug(),
			'',
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
			admin_url( 'edit.php?post_type=page' ),
			'',
			'dashicons-admin-page',
			5
		);

		add_menu_page(
			__( 'Settings', 'pressbooks' ),
			__( 'Settings', 'pressbooks' ),
			'manager_network',
			$this->getSettingsSlug(),
			'',
			'dashicons-admin-settings',
			7
		);
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
		remove_submenu_page( 'users.php', 'user-new.php' );
		remove_submenu_page( 'users.php', 'user_bulk_new' );

		remove_menu_page( 'settings.php' );
		remove_menu_page( 'users.php' );
		remove_menu_page( 'sites.php' );
		remove_menu_page( 'wp-sentry-tools-menu' );
		remove_menu_page( 'separator1' );
		remove_menu_page( 'separator-last' );
		remove_menu_page( 'separator2' );
		remove_menu_page( 'pb_network_analytics_admin' );
	}

	private function addStatsMenuItem(): void {
		$is_network_analytics_active = is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' );
		$is_koko_analytics_active = is_plugin_active( 'koko-analytics/koko-analytics.php' );

		if ( $is_network_analytics_active || $is_koko_analytics_active ) {
			add_menu_page(
				__( 'Stats', 'pressbooks' ),
				__( 'Stats', 'pressbooks' ),
				'manage_network',
				'pb_network_stats',
				'',
				'dashicons-chart-area',
				7
			);
			if ( $is_network_analytics_active ) {
				add_submenu_page(
					'pb_network_stats',
					__( 'Network Stats', 'pressbooks' ),
					__( 'Network Stats', 'pressbooks' ),
					'manage_network',
					network_admin_url('admin.php?page=pb_network_analytics_admin' )
				);
			}

			if ( $is_koko_analytics_active ) {
				add_submenu_page(
					'pb_network_stats',
					__( 'Analytics', 'pressbooks' ),
					__( 'Analytics', 'pressbooks' ),
					'manage_network',
					admin_url('index.php?page=koko-analytics' )
				);
			}
			remove_submenu_page( 'pb_network_stats', 'pb_network_stats' );
		}
	}

}
