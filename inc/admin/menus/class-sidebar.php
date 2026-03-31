<?php

namespace Pressbooks\Admin\Menus;

use function Pressbooks\Admin\Dashboard\init_network_integrations_menu;
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

		// Use default menu slugs - plugins can customize via hooks
		$this->booksCallback = '';
		$this->booksSlug = $this->getContextSlug( 'sites.php', false );

		$this->usersCallback = '';
		$this->usersSlug = $this->getContextSlug( 'users.php', false );

		$this->settingsCallback = '';
		$this->settingsSlug = $this->getContextSlug( 'settings.php', false );
	}

	public static function init(): void {
		( new self() )->hooks();
	}

	public function hooks(): void {
		// Apply posts restriction to all sites
		add_action( 'admin_init', [ $this, 'restrictPostsPageAccess' ] );

		if ( ! is_main_site() ) {
			add_action( 'admin_menu', [ $this, 'removePatternsSubMenuItem' ] );
			add_action( 'admin_init', [ $this, 'restrictPatternsPageAccess' ] );
			return;
		}

		if ( ! is_super_admin() ) {
			return;
		}
		add_action( 'network_admin_menu', [ $this, 'manageNetworkAdminMenu' ], 999 );
		add_action( 'admin_menu', [ $this, 'manageAdminMenu' ], 999 );

		if ( ! is_restricted() ) {
			add_filter( 'custom_menu_order', '__return_true' );
			add_filter( 'menu_order', [ $this, 'reorderSuperAdminMenu' ], 998 );
		}

		remove_action( 'admin_init', '\Pressbooks\Admin\NetworkManagers\restrict_access' );
	}

	public function removePatternsSubMenuItem(): void {
		remove_submenu_page( 'themes.php', 'edit.php?post_type=wp_block' );
		remove_submenu_page( 'themes.php', 'site-editor.php?p=/pattern' );
	}

	public function restrictPatternsPageAccess(): void {
		global $pagenow;

		if ( $pagenow !== 'edit.php' || ! isset( $_GET['post_type'] ) || $_GET['post_type'] !== 'wp_block' ) {
			return;
		}

		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'pressbooks' ), 403 );
	}

	public function restrictPostsPageAccess(): void {
		global $pagenow;

		// Get post_type from request (check both GET and POST)
		// phpcs:ignore Pressbooks.Security.NonceVerification.Missing, Pressbooks.Security.ValidatedSanitizedInput.InputNotSanitized
		$post_type = wp_unslash( $_GET['post_type'] ?? $_POST['post_type'] ?? null );

		if ( ! is_null( $post_type ) ) {
			$post_type = sanitize_text_field( $post_type );
		}

		$pages_to_block = [ 'edit.php', 'post-new.php' ];

		// Block access to edit.php and post-new.php with no post_type (defaults to 'post') or post_type=post
		if ( in_array( $pagenow, $pages_to_block, true ) && ( $post_type === null || $post_type === 'post' ) ) {
			wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'pressbooks' ), 403 );
		}
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
				'users.php',
				'users.php',
			],
			[
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
		remove_submenu_page( 'edit.php?post_type=page', 'post-new.php?post_type=page' );

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

		if ( is_network_admin() ) {
			global $admin_page_hooks;
			$admin_page_hooks['sites.php'] = 'sites';
		}

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
				8
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
					8
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
				8
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
				'manager_network',
				network_admin_url( 'upgrade.php' )
			);
		}

		/**
		 * Allow plugins to register custom menu items or submenu pages in the Dashboard section.
		 *
		 * @since 6.38.0
		 */
		do_action( 'pressbooks_register_network_dashboard_submenu_pages' );

		/**
		 * Allow plugins to register custom menu items or submenu pages in the Books section.
		 * This hook allows extensions like pressbooks-plugins-config to customize the Books menu
		 * when Pressbooks ecosystem plugins are active.
		 *
		 * @since 6.38.0
		 * @param string $books_slug The slug for the Books menu parent page.
		 */
		do_action( 'pressbooks_register_network_books_submenu_pages', $this->booksSlug );

		/**
		 * Allow plugins to register custom menu items or submenu pages in the Users section.
		 * This hook allows extensions like pressbooks-plugins-config to customize the Users menu
		 * when Pressbooks ecosystem plugins are active.
		 *
		 * @since 6.38.0
		 * @param string $users_slug The slug for the Users menu parent page.
		 */
		do_action( 'pressbooks_register_network_users_submenu_pages', $this->usersSlug );

		// Appearance
		add_submenu_page(
			$this->getContextSlug( 'customize.php', true ),
			__( 'Customize Home Page', 'pressbooks' ),
			__( 'Customize Home Page', 'pressbooks' ),
			'manage_network',
			$this->getContextSlug( 'customize.php', true )
		);

		add_submenu_page(
			$this->getContextSlug( 'customize.php', true ),
			__( 'Activate Book Themes', 'pressbooks' ),
			__( 'Activate Book Themes', 'pressbooks' ),
			'manage_network',
			$this->getContextSlug( 'themes.php', false )
		);

		add_submenu_page(
			$this->getContextSlug( 'customize.php', true ),
			__( 'Change Root Site Theme', 'pressbooks' ),
			__( 'Change Root Site Theme', 'pressbooks' ),
			'manage_network',
			$this->getContextSlug( 'themes.php', true )
		);

		/**
		 * Allow plugins to register custom submenu pages under the Appearance menu.
		 *
		 * @since 5.X.X
		 * @param string $appearance_slug The slug for the Appearance menu parent page.
		 */
		do_action( 'pressbooks_register_network_appearance_submenu_pages', $this->getContextSlug( 'customize.php', true ) );

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

		/**
		 * Allow plugins to register custom submenu pages under the Plugins menu.
		 *
		 * @since 5.X.X
		 * @param string $plugins_slug The slug for the Plugins menu parent page.
		 */
		do_action( 'pressbooks_register_network_plugins_submenu_pages', $this->getContextSlug( 'plugins.php', false ) );

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

			add_submenu_page(
				$this->getContextSlug( 'settings.php', false ),
				__( 'Google Analytics', 'pressbooks' ),
				__( 'Google Analytics', 'pressbooks' ),
				'manage_network',
				$this->getContextSlug( 'settings.php?page=pb_analytics', false )
			);
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

		/**
		 * Allow plugins to register custom submenu pages under the Settings menu.
		 *
		 * @since 5.X.X
		 * @param string $settings_slug The slug for the Settings menu parent page.
		 */
		do_action( 'pressbooks_register_network_settings_submenu_pages', $this->getContextSlug( 'settings.php', false ) );

		/**
		 * Allow plugins to register custom menu pages for Stats section.
		 *
		 * @since 6.38.0
		 */
		do_action( 'pressbooks_register_network_stats_submenu_pages' );
	}
	public function reorderSuperAdminMenu( array $menu_order ): array {

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
		return array_values( $submenu )[0] ?? [];
	}

	private function manageIntegrationsAdminMenuItem(): void {
		global $submenu;

		init_network_integrations_menu();

		/**
		 * TODO: create a filter to add/remove integrations menu items and apply inversion of control principle
		 *
		 * Creating a filter to add/remove integrations menu items will allow us to apply inversion of control principle
		 * and avoid the need to add/remove integrations menu items in this class directly.
		 *
		 * For example, we can create a filter to add/remove integrations menu items in a plugin or theme.
		 *
		 * apply_filters( 'add_items_to_network_integrations_menu', $submenu )
		 * apply_filters( 'remove_items_from_network_integrations_menu', $submenu )
		 *
		 */

		if ( is_plugin_active( 'pressbooks-cas-sso/pressbooks-cas-sso.php' ) ) {
			\PressbooksCasSso\Admin::init()->addMenu();
		}

		if ( is_plugin_active( 'pressbooks-saml-sso/pressbooks-saml-sso.php' ) ) {
			\PressbooksSamlSso\Admin::init()->addMenu();
		}

		if ( is_plugin_active( 'pressbooks-oidc-sso/pressbooks-oidc-sso.php' ) ) {
			\PressbooksOidcSso\Admin::init()->addMenu();
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

	public static function removeH5pMenuForSubscribers(): void {
		$user = get_user_by( 'ID', get_current_user_id() );

		if (
			! $user ||
			! is_admin() ||
			is_super_admin( $user->ID ) ||
			$user->roles !== [ 'subscriber' ] ||
			! is_plugin_active( 'h5p/h5p.php' )
		) {
			return;
		}

		remove_menu_page( 'h5p' );
	}
}
