<?php

namespace Pressbooks\Admin\Menus;

use function Pressbooks\Admin\NetworkManagers\is_restricted;

class SideBar {

	private bool $isNetworkAnalyticsActive;

	private bool $isKokoAnalyticsActive;

	public function __construct() {
		$this->isKokoAnalyticsActive = is_plugin_active( 'koko-analytics/koko-analytics.php' );
		$this->isNetworkAnalyticsActive = is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' );
	}

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

	private function removeNetworkManagerLegacyItems(): void {
		array_map( 'remove_submenu_page', [
			'sites.php',
			'sites.php',
			'users.php',
			'settings.php',
			'users.php',
			'users.php',
		], [
			'pb_network_analytics_booklist',
			'site-new.php',
			'pb_network_analytics_userlist',
			'pb_network_analytics_options',
			'user-new.php',
			'user_bulk_new',
		] );

		array_map( 'remove_menu_page', [
			'settings.php',
			'users.php',
			'sites.php',
			'wp-sentry-tools-menu',
			'separator1',
			'separator-last',
			'separator2',
		] );
	}

	private function getSlug( string $page, bool $is_main_site_page ): string {
		return is_network_admin() ?
			( $is_main_site_page ? admin_url( $page ) : $page ) :
			( $is_main_site_page ? $page : network_admin_url( $page ) );
	}

	private function removeAdminLegacyItems(): void {
		array_map( 'remove_submenu_page', [
			'index.php',
			'edit.php?post_type=page',
		], [
			'pb_catalog',
			'post-new.php?post_type=page',
		] );

		array_map( 'remove_menu_page', [
			'index.php',
			'themes.php',
			'edit.php?post_type=page',
			'pb_home_page',
			'upload.php',
			'plugins.php',
			'tools.php',
			'options-general.php',
			'users.php',
			'separator1',
			'separator-last',
			'separator2',
		] );
	}

	private function addMenuItems(): void {
		if ( $this->isNetworkAnalyticsActive ) {
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
			$this->getSlug( 'customize.php', true),
			'',
			'dashicons-admin-appearance',
			4
		);

		add_menu_page(
			__( 'Pages', 'pressbooks' ),
			__( 'Pages', 'pressbooks' ),
			'manage_network',
			$this->getSlug( 'edit.php?post_type=page', true ),
			'',
			'dashicons-admin-page',
			5
		);

		add_menu_page(
			__( 'Settings', 'pressbooks' ),
			__( 'Settings', 'pressbooks' ),
			'manager_network',
			$settings_slug,
			$settings_callback,
			'dashicons-admin-settings',
			7
		);

		if ( $this->isNetworkAnalyticsActive ) {
			if ( ! is_network_admin() ) {
				add_menu_page(
					__( 'Stats', 'pressbooks' ),
					__( 'Stats', 'pressbooks' ),
					'manage_network',
					$this->getSlug( 'admin.php?page=pb_network_analytics_admin', false ),
					'',
					'dashicons-chart-area',
					7
				);
			}
			add_submenu_page(
				is_network_admin() ?
					'pb_network_analytics_admin' : network_admin_url( 'admin.php?page=pb_network_analytics_admin'),
				__( 'Network Stats', 'pressbooks' ),
				__( 'Network Stats', 'pressbooks' ),
				'manage_network',
				is_network_admin() ?
					'pb_network_analytics_admin' : network_admin_url( 'admin.php?page=pb_network_analytics_admin'),
				''
			);
			if ( $this->isKokoAnalyticsActive ) {
				add_submenu_page(
					is_network_admin() ?
						'pb_network_analytics_admin' : network_admin_url( 'admin.php?page=pb_network_analytics_admin'),
					__( 'Analytics', 'pressbooks' ),
					__( 'Analytics', 'pressbooks' ),
					'view_koko_analytics',
					$this->getKokoAnalyticsSlug(),
					''
				);
			}
		} elseif ( $this->isKokoAnalyticsActive ) {
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
				$this->getKokoAnalyticsSlug(),
				''
			);

			// Koko analytics dashboard must be a submenu item to make it work
			remove_submenu_page( 'pressbooks_network_stats', 'pressbooks_network_stats' );
		}
	}

	private function manageIntegrationsAdminMenuItem(): void {
		\Pressbooks\Admin\Dashboard\init_network_integrations_menu();

		if ( is_plugin_active( 'pressbooks-cas-sso/pressbooks-cas-sso.php' ) ) {
			\PressbooksCasSso\Admin::init()->addMenu();
		}

		if ( is_plugin_active( 'pressbooks-saml-sso/pressbooks-saml-sso.php' ) ) {
			\PressbooksSamlSso\Admin::init()->addMenu();
		}

		if ( is_plugin_active( 'pressbooks-oidc-sso/pressbooks-oidc-sso.php' ) ) {
			\PressbooksOidcSso\Admin::init()->addMenu();
		}

		if ( is_plugin_active( 'pressbooks-lti-provider-1p3/pressbooks-lti-provider.php' ) ) {
			$lti_admin = \PressbooksLtiProvider1p3\Admin::init();
			$lti_admin->addConsumersMenu();
			$lti_admin->addSettingsMenu();
		}
	}

	public function reorderMenu(): array {
		$items_order = [
			$this->getSlug( 'index.php', false ),
			$this->isNetworkAnalyticsActive ?
				'pb_network_analytics_booklist' :
				$this->getSlug( 'sites.php', false ),
			$this->isNetworkAnalyticsActive ?
				'pb_network_analytics_userlist' :
				$this->getSlug( 'users.php', false ),
			$this->getSlug( 'customize.php', true),
			$this->getSlug( 'edit.php?post_type=page', true ),
		];

		$items_order[] = $this->isNetworkAnalyticsActive ?
			'pb_network_analytics_options' :
			$this->getSlug( 'settings.php', false );

		if ( $this->isNetworkAnalyticsActive ) {
			$items_order[] = is_network_admin() ?
				'pb_network_analytics_admin' : network_admin_url( 'admin.php?page=pb_network_analytics_admin');
		} elseif ( $this->isKokoAnalyticsActive ) {
			$items_order[] = $this->getKokoAnalyticsSlug();
		}

		return $items_order;
	}

	private function getKokoAnalyticsSlug(): string {
		return is_network_admin() ? admin_url( 'admin.php?page=koko-analytics' ) : 'koko-analytics';
	}

}
