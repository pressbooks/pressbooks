<?php

namespace Pressbooks\Admin\Menus;

use function Pressbooks\Admin\NetworkManagers\is_restricted;

class SideBar {

	private bool $isNetworkAnalyticsActive;

	private bool $isKokoAnalyticsActive;

	private string $booksSlug;

	private array|string $booksCallback;

	private string $usersSlug;

	private array|string $usersCallback;

	private string $settingsSlug;

	private array|string $settingsCallback;

	public function __construct() {
		$this->isKokoAnalyticsActive = is_plugin_active( 'koko-analytics/koko-analytics.php' );
		$this->isNetworkAnalyticsActive = is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' );

		if ( $this->isNetworkAnalyticsActive ) {
			$this->booksCallback = [ \PressbooksNetworkAnalytics\Admin\Books::init(), 'printMenuBookList' ];
			$this->booksSlug = 'pb_network_analytics_booklist';

			$this->usersCallback = [ \PressbooksNetworkAnalytics\Admin\Users::init(), 'printMenuUserList' ];
			$this->usersSlug = 'pb_network_analytics_userlist';

			$this->settingsCallback = [ \PressbooksNetworkAnalytics\Admin\Options::init(), 'printMenuSettings' ];
			$this->settingsSlug = $this->getContextSlug( 'admin.php?page=pb_network_analytics_options', false );
		} else {
			$this->booksCallback = '';
			$this->booksSlug = $this->getContextSlug( 'sites.php', false );

			$this->usersCallback = '';
			$this->usersSlug = $this->getContextSlug( 'users.php', false );

			$this->settingsCallback = '';
			$this->settingsSlug = $this->getContextSlug( 'settings.php', false );
		}
	}

	public static function init(): void {
		( new self() )->hooks();
	}

	public function hooks(): void {
		if ( ! is_super_admin() ) {
			return;
		}
		add_action( 'network_admin_menu', [ $this, 'manageNetworkAdminMenu' ], 999 );
		add_action( 'admin_menu', [ $this, 'manageAdminMenu' ], 999 );

		if ( ! is_restricted() ) {
			add_filter( 'custom_menu_order', '__return_true' );
			add_filter( 'menu_order', [ $this, 'reorderSuperAdminMenu' ], 999 );
		}

		remove_action( 'admin_init', '\Pressbooks\Admin\NetworkManagers\restrict_access' );
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
		array_map( 'remove_submenu_page',
			[
				'sites.php',
				'sites.php',
				'users.php',
				'users.php',
			],
			[
				'pb_network_analytics_booklist',
				'site-new.php',
				'pb_network_analytics_userlist',
				'user-new.php',
			]
		);

		array_map( 'remove_menu_page', [
			'users.php',
			'sites.php',
			'wp-sentry-tools-menu',
			'separator1',
			'separator-last',
			'separator2',
			'pb_stats',
			'themes.php',
		] );

		if ( ! is_restricted() ) {
			array_map( 'remove_submenu_page',
				[
					'themes.php',
					'themes.php',
				],
				[
					'themes.php',
					'theme-install.php',
				]
			);
		} else {
			array_map( 'remove_submenu_page',
				[
					'settings.php',
					'settings.php',
					'settings.php',
				],
				[
					'pressbooks_network_analytics_options',
					'pressbooks_sharingandprivacy_options',
					'pb_analytics',
				]
			);

			remove_menu_page( 'settings.php' );
		}
	}

	private function removeAdminLegacyItems(): void {
		array_map( 'remove_submenu_page',
			[
				'index.php',
				'edit.php?post_type=page',
			],
			[
				'pb_catalog',
				'post-new.php?post_type=page',
			]
		);

		array_map( 'remove_menu_page',
			[
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
				'edit-comments.php',
				'edit.php',
			]
		);

		if ( ! is_restricted() ) {
			array_map('remove_submenu_page',
				[
					'users.php',
					'themes.php',
					'plugins.php',
				],
				[
					'users.php',
					'themes.php',
					'plugins.php',
				]
			);
		}
	}

	private function addMenuItems(): void {
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
			$this->booksSlug,
			$this->booksCallback,
			'dashicons-book-alt',
			2
		);

		add_menu_page(
			__( 'Users', 'pressbooks' ),
			__( 'Users', 'pressbooks' ),
			'manager_network',
			$this->usersSlug,
			$this->usersCallback,
			'dashicons-admin-users',
			3
		);

		add_menu_page(
			__( 'Appearance', 'pressbooks' ),
			__( 'Appearance', 'pressbooks' ),
			'manage_network',
			$this->getContextSlug( 'customize.php', true ),
			'',
			'dashicons-admin-appearance',
			4
		);

		add_menu_page(
			__( 'Pages', 'pressbooks' ),
			__( 'Pages', 'pressbooks' ),
			'manage_network',
			$this->getContextSlug( 'edit.php?post_type=page', true ),
			'',
			'dashicons-admin-page',
			5
		);

		if ( is_restricted() && $this->isNetworkAnalyticsActive ) {
			add_menu_page(
				__( 'Settings', 'pressbooks' ),
				__( 'Settings', 'pressbooks' ),
				'manager_network',
				'settings.php',
				$this->settingsCallback,
				'dashicons-admin-settings',
				7
			);
			if ( ! is_network_admin() ) {
				add_submenu_page(
					'settings.php',
					__( 'Network Options', 'pressbooks' ),
					__( 'Network Options', 'pressbooks' ),
					'manager_network',
					$this->settingsSlug,
					''
				);
				remove_submenu_page( 'settings.php', 'settings.php' );
			}
		}

		if ( $this->isNetworkAnalyticsActive ) {
			if ( ! is_network_admin() ) {
				add_menu_page(
					__( 'Stats', 'pressbooks' ),
					__( 'Stats', 'pressbooks' ),
					'manage_network',
					network_admin_url( 'admin.php?page=pb_network_analytics_admin' ),
					'',
					'dashicons-chart-area',
					7
				);
			}

			add_submenu_page(
				$this->getNetworkAnalyticsStatsSlug(),
				__( 'Network Stats', 'pressbooks' ),
				__( 'Network Stats', 'pressbooks' ),
				'manage_network',
				$this->getNetworkAnalyticsStatsSlug(),
				''
			);
			if ( $this->isKokoAnalyticsActive ) {
				add_submenu_page(
					$this->getNetworkAnalyticsStatsSlug(),
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

			remove_submenu_page( 'pressbooks_network_stats', 'pressbooks_network_stats' );
		}

		if ( ! is_restricted() ) {
			$this->addSuperAdminMenuItems();
		}
	}

	private function addSuperAdminMenuItems(): void {
		// Dashboard
		if ( ! is_network_admin() ) {
			add_submenu_page(
				network_admin_url( 'index.php' ),
				__( 'Upgrade Network', 'pressbooks' ),
				__( 'Upgrade Network', 'pressbooks' ),
				'maanger_network',
				network_admin_url( 'upgrade.php' )
			);
		}

		// Users
		remove_submenu_page( $this->usersSlug, $this->usersSlug );

		if ( $this->isNetworkAnalyticsActive ) {
			add_submenu_page(
				$this->usersSlug,
				__( 'Network Users', 'pressbooks' ),
				__( 'Network Users', 'pressbooks' ),
				'manager_network',
				$this->usersSlug
			);
		} else {
			remove_submenu_page( $this->usersSlug, $this->usersSlug );
			add_submenu_page(
				$this->usersSlug,
				__( 'Network Users', 'pressbooks' ),
				__( 'Network Users', 'pressbooks' ),
				'manager_network',
				$this->getContextSlug( 'users.php', false )
			);
			global $submenu;
			unset( $submenu[ $this->usersSlug ][11] );
		}

		add_submenu_page(
			$this->usersSlug,
			__( 'Root Site Users', 'pressbooks' ),
			__( 'Root Site Users', 'pressbooks' ),
			'manager_network',
			$this->getContextSlug( 'users.php', true )
		);

		// Appearance
		add_submenu_page(
			$this->getContextSlug( 'customize.php', true ),
			__( 'Customize Home Page' ),
			__( 'Customize Home Page' ),
			'manage_network',
			$this->getContextSlug( 'customize.php', true )
		);

		add_submenu_page(
			$this->getContextSlug( 'customize.php', true ),
			__( 'Activate Book Themes' ),
			__( 'Activate Book Themes' ),
			'manage_network',
			$this->getContextSlug( 'themes.php', false )
		);

		add_submenu_page(
			$this->getContextSlug( 'customize.php', true ),
			__( 'Change Root Site Theme' ),
			__( 'Change Root Site Theme' ),
			'manage_network',
			$this->getContextSlug( 'themes.php', true )
		);

		// Plugins
		if ( is_network_admin() ) {
			remove_submenu_page( 'plugins.php', 'plugin-install.php' );
		} else {
			add_menu_page(
				__( 'Plugins', 'pressbooks' ),
				__( 'Plugins', 'pressbooks' ),
				'manage_network',
				network_admin_url( 'plugins.php' ),
				'',
				'dashicons-admin-plugins',
				65
			);
		}

		add_submenu_page(
			$this->getContextSlug( 'plugins.php', false ),
			__( 'Network Plugins', 'pressbooks' ),
			__( 'Network Plugins', 'pressbooks' ),
			'manage_network',
			$this->getContextSlug( 'plugins.php', false )
		);

		if ( is_network_admin() ) {
			remove_submenu_page( $this->getContextSlug( 'plugins.php', false ), $this->getContextSlug( 'plugins.php', false ) );
		}

		add_submenu_page(
			$this->getContextSlug( 'plugins.php', false ),
			__( 'Root Site Plugins', 'pressbooks' ),
			__( 'Root Site Plugins', 'pressbooks' ),
			'manage_network',
			$this->getContextSlug( 'plugins.php', true )
		);

		// Settings
		if ( ! is_network_admin() ) {
			add_menu_page(
				__( 'Settings', 'pressbooks' ),
				__( 'Settings', 'pressbooks' ),
				'manage_network',
				$this->getContextSlug( 'settings.php', false ),
				'',
				'dashicons-admin-settings',
				66
			);

			remove_submenu_page( $this->getContextSlug( 'settings.php', false ), $this->getContextSlug( 'settings.php', false ) );

			add_submenu_page(
				$this->getContextSlug( 'settings.php', false ),
				__( 'Network Settings', 'pressbooks' ),
				__( 'Network Settings', 'pressbooks' ),
				'manage_network',
				$this->getContextSlug( 'settings.php', false )
			);

			add_submenu_page(
				$this->getContextSlug( 'settings.php', false ),
				__( 'Network Setup', 'pressbooks' ),
				__( 'Network Setup', 'pressbooks' ),
				'manage_network',
				$this->getContextSlug( 'setup.php', false )
			);

			add_submenu_page(
				$this->getContextSlug( 'settings.php', false ),
				__( 'Network Managers', 'pressbooks' ),
				__( 'Network Managers', 'pressbooks' ),
				'manage_network',
				$this->getContextSlug( 'settings.php?page=pb_network_managers', false )
			);

			if ( is_plugin_active( 'pressbooks-whitelabel/pressbooks-whitelabel.php' ) ) {
				add_submenu_page(
					$this->getContextSlug( 'settings.php', false ),
					__( 'Whitelabel Settings', 'pressbooks' ),
					__( 'Whitelabel Settings', 'pressbooks' ),
					'manage_network',
					$this->getContextSlug( 'settings.php?page=pb_whitelabel_settings', false )
				);
			}

			if ( is_plugin_active( 'object-cache-pro/object-cache-pro.php' ) ) {
				add_submenu_page(
					$this->getContextSlug( 'settings.php', false ),
					__( 'Object Cache', 'pressbooks' ),
					__( 'Object Cache', 'pressbooks' ),
					'manage_network',
					$this->getContextSlug( 'settings.php?page=objectcache', false )
				);
			}

			add_submenu_page(
				$this->getContextSlug( 'settings.php', false ),
				__( 'Google Analytics', 'pressbooks' ),
				__( 'Google Analytics', 'pressbooks' ),
				'manage_network',
				$this->getContextSlug( 'settings.php?page=pb_analytics', false )
			);
		}

		if ( $this->isNetworkAnalyticsActive ) {
			if ( ! is_network_admin() ) {
				add_submenu_page(
					$this->getContextSlug( 'settings.php', false ),
					__( 'Network Options', 'pressbooks' ),
					__( 'Network Options', 'pressbooks' ),
					'manage_network',
					$this->getContextSlug( 'admin.php?page=pb_network_analytics_options', false ),
				);

				add_submenu_page(
					$this->getContextSlug( 'settings.php', false ),
					__( 'Sharing & Privacy', 'pressbooks' ),
					__( 'Sharing & Privacy', 'pressbooks' ),
					'manage_network',
					$this->getContextSlug( 'settings.php?page=pressbooks_sharingandprivacy_options', false )
				);
			}
		}

		remove_submenu_page( $this->getContextSlug( 'options-general.php', true ), $this->getContextSlug( 'options-general.php', true ) );

		add_submenu_page(
			$this->getContextSlug( 'settings.php', false ),
			__( 'Root Site General Settings', 'pressbooks' ),
			__( 'Root Site General Settings', 'pressbooks' ),
			'manage_network',
			$this->getContextSlug( 'options-general.php', true )
		);

		remove_submenu_page(
			$this->getContextSlug( 'options-general.php', true ),
			$this->getContextSlug( 'options-media.php', true )
		);

		add_submenu_page(
			$this->getContextSlug( 'settings.php', false ),
			__( 'Root Site Media Settings', 'pressbooks' ),
			__( 'Root Site Media Settings', 'pressbooks' ),
			'manage_network',
			$this->getContextSlug( 'options-media.php', true )
		);

		remove_submenu_page(
			$this->getContextSlug( 'options-general.php', true ),
			$this->getContextSlug( 'options-privacy.php', true )
		);

		add_submenu_page(
			$this->getContextSlug( 'settings.php', false ),
			__( 'Root Site Privacy Settings', 'pressbooks' ),
			__( 'Root Site Privacy Settings', 'pressbooks' ),
			'manage_network',
			$this->getContextSlug( 'options-privacy.php', true )
		);

		// Stats
		if ( is_plugin_active( 'pressbooks-stats/pressbooks-stats.php' ) ) {
			if ( ! $this->isNetworkAnalyticsActive && $this->isKokoAnalyticsActive ) {
				$stats_slug = 'pressbooks_network_stats';
				add_submenu_page(
					$stats_slug,
					__( 'PB Stats', 'pressbooks' ),
					__( 'PB Stats', 'pressbooks' ),
					'manage_network',
					is_network_admin() ? 'pb_stats' : network_admin_url( 'admin.php?page=pb_stats' )
				);
			}

			if ( $this->isNetworkAnalyticsActive ) {
				$stats_slug = is_network_admin() ? 'pb_network_analytics_admin' : network_admin_url( 'admin.php?page=pb_network_analytics_admin' );
				add_submenu_page(
					$stats_slug,
					__( 'PB Stats', 'pressbooks' ),
					__( 'PB Stats', 'pressbooks' ),
					'manage_network',
					is_network_admin() ? 'pb_stats' : network_admin_url( 'admin.php?page=pb_stats' )
				);
			}

			if ( ! $this->isKokoAnalyticsActive && ! $this->isNetworkAnalyticsActive ) {
				$stats_slug = is_network_admin() ? 'pb_stats' : network_admin_url( 'admin.php?page=pb_stats' );
				add_menu_page(
					__( 'Stats', 'pressbooks' ),
					__( 'Stats', 'pressbooks' ),
					'manage_network',
					$stats_slug,
					'',
					'dashicons-chart-area',
					66
				);
			}
		}
	}

	public function reorderSuperAdminMenu( array $menu_order ): array {
		if ( ! is_network_admin() && $this->isNetworkAnalyticsActive ) {
			array_splice( $menu_order, 8, 0, network_admin_url( 'admin.php?page=pb_network_analytics_admin' ) );
			unset( $menu_order[5] );
		}

		if ( isset( $menu_order[5] ) && $menu_order[5] === 'pressbooks_network_stats' ) {
			array_splice( $menu_order, 8, 0, 'pressbooks_network_stats' );
			unset( $menu_order[5] );
		}

		$this->reorderSettingsSubMenu();

		return $menu_order;
	}

	private function reorderSettingsSubMenu(): void {
		global $submenu;

		$setting_slug = $this->getContextSlug( 'settings.php', false );

		if ( ! array_key_exists( $setting_slug, $submenu ) ) {
			return;
		}

		$settings_items = $submenu[ $setting_slug ];

		$settings_items_ordered = [];

		if ( $this->isNetworkAnalyticsActive ) {
			$settings_items_ordered[] = $this->getSubmenuBySlug( $settings_items, 'pb_network_analytics_options' );
		}

		array_push(
			$settings_items_ordered,
			$this->getSubmenuBySlug( $settings_items, 'settings.php' ),
			$this->getSubmenuBySlug( $settings_items, 'setup.php' ),
			$this->getSubmenuBySlug( $settings_items, 'pb_network_managers' )
		);

		if ( $this->isNetworkAnalyticsActive ) {
			$settings_items_ordered[] = $this->getSubmenuBySlug( $settings_items, 'pressbooks_sharingandprivacy_options' );
		}

		array_push(
			$settings_items_ordered,
			$this->getSubmenuBySlug( $settings_items, 'pb_analytics' ),
			$this->getSubmenuBySlug( $settings_items, 'options-general.php' ),
			$this->getSubmenuBySlug( $settings_items, 'options-media.php' ),
			$this->getSubmenuBySlug( $settings_items, 'options-privacy.php' )
		);

		if ( is_plugin_active( 'pressbooks-whitelabel/pressbooks-whitelabel.php' ) ) {
			$settings_items_ordered[] = $this->getSubmenuBySlug( $settings_items, 'pb_whitelabel_settings' );
		}

		if ( is_plugin_active( 'object-cache-pro/object-cache-pro.php' ) ) {
			$settings_items_ordered[] = $this->getSubmenuBySlug( $settings_items, 'objectcache' );
		}

		$submenu[ $this->getContextSlug( 'settings.php', false ) ] = array_merge(
			$settings_items_ordered,
			$settings_items
		);
	}

	private function getSubmenuBySlug( array &$submenu, string $slug ): array {
		foreach ( $submenu as $key => $item ) {
			if ( str_contains( $item[2], $slug ) ) {
				unset( $submenu[ $key ] );
				return $item;
			}
		}
		return $submenu[0];
	}

	private function manageIntegrationsAdminMenuItem(): void {
		global $submenu;

		\Pressbooks\Admin\Dashboard\init_network_integrations_menu();
		$plugins_active = false;

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

			// Move LTI settings menu item to network admin menu page
			if ( ! is_network_admin() && isset( $submenu['pb_network_integrations'] ) ) {
				$submenu[ network_admin_url( 'admin.php?page=pb_lti_settings' ) ] = $submenu['pb_network_integrations'];
				unset( $submenu['pb_network_integrations'] );
			}
		}

		if (
			isset( $submenu[ network_admin_url( 'admin.php?page=pb_network_integrations' ) ] ) &&
			$submenu[ network_admin_url( 'admin.php?page=pb_network_integrations' ) ][0][2] === network_admin_url( 'admin.php?page=pb_network_integrations' )
		) {
			unset( $submenu[ network_admin_url( 'admin.php?page=pb_network_integrations' ) ][0] );
		}
	}

	private function getKokoAnalyticsSlug(): string {
		return is_network_admin() ? admin_url( 'admin.php?page=koko-analytics' ) : 'koko-analytics';
	}

	private function getNetworkAnalyticsStatsSlug(): string {
		return is_network_admin() ?
			'pb_network_analytics_admin' : network_admin_url( 'admin.php?page=pb_network_analytics_admin' );
	}

	private function getContextSlug( string $page, bool $is_main_site_page ): string {
		return is_network_admin() ?
			( $is_main_site_page ? admin_url( $page ) : $page ) :
			( $is_main_site_page ? $page : network_admin_url( $page ) );
	}

}
