<?php
/**
 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
namespace Pressbooks\Admin\Dashboard;

use Pressbooks\Container;

class NetworkDashboard extends Dashboard {
	protected static ?Dashboard $instance = null;

	protected string $page_name = 'pb_network_page';

	public function hooks(): void {
		add_action( 'load-index.php', [ $this, 'redirect' ] );
		add_action( 'network_admin_menu', [ $this, 'removeDefaultPage' ] );
		add_action( 'network_admin_menu', [ $this, 'addNewPage' ] );
	}

	public function getUrl(): string {
		return network_admin_url( "index.php?page={$this->page_name}" );
	}

	public function render(): void {
		$blade = Container::get( 'Blade' );

		echo $blade->render( 'admin.dashboard.network', [
			'network_name' => get_bloginfo( 'name' ),
			'network_url' => network_home_url(),
			'total_users' => get_user_count(),
			'total_books' => $this->getTotalNumberOfBooks(),
			'network_analytics_active' => is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' ),
			'koko_analytics_active' => is_plugin_active( 'koko-analytics/koko-analytics.php' ),
			'network_checklist' => $this->getNetworkChecklist(),
		] );
	}

	protected function shouldRedirect(): bool {
		$screen = get_current_screen();

		return $screen->base === 'dashboard-network';
	}

	protected function shouldRemoveDefaultPage(): bool {
		return is_network_admin();
	}

	protected function getTotalNumberOfBooks(): int {
		global $wpdb;

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT blog_id) FROM {$wpdb->blogmeta} WHERE blog_id <> %d AND meta_key = %s",
				get_network()->site_id,
				'pb_book_sync_timestamp',
			)
		);
	}

	protected function getNetworkChecklist() : array {
		$items = [
			[
				'title' => __( 'Brand your network', 'pressbooks' ),
				'link' => admin_url( 'customize.php' ),
				'description' => __( 'Display your institutional logos and colors across your network', 'pressbooks' ),
				'checked' => get_network_option( null, 'network_checklist_pressbooks_branding', false ),
			],
			[
				'title' => __( 'Customize your homepage', 'pressbooks' ),
				'link' => admin_url( 'edit.php?post_type=page' ),
				'description' => __( 'Edit the textboxes and menu links on your homepage' ),
				'checked' => get_network_option( null, 'network_checklist_customize_homepage', false ),
			],
			[
				'title' => __( 'Review network settings', 'pressbooks' ),
				'link' => network_admin_url( 'admin.php?page=pb_network_analytics_options' ),
				'description' => __( 'Adjust defaults for new books and user permissions ' ),
				'checked' => get_network_option( null, 'network_checklist_review_network', false ),
			],
			[
				'title' => __( 'Configure Google Analytics', 'pressbooks' ),
				'link' => network_admin_url( 'settings.php?page=pb_network_analytics_options#tabs-3' ),
				'description' => __( 'Get more insight into your network’s web traffic' ),
				'checked' => get_network_option( null, 'network_checklist_google_analytics', false ),
			],
		];

		// Check if either SSO plugin is activated
		if ( is_plugin_active( 'pressbooks-saml-sso/pressbooks-saml-sso.php' ) ) {
			$sso_item = [
				'title' => __( 'Configure Single Sign On (SSO)', 'pressbooks' ),
				'link' => network_admin_url( 'admin.php?page=pb_saml_admin' ),
				'description' => __( 'Allow users to login with their existing institutional credentials' ),
				'checked' => get_network_option( null, 'network_checklist_configure_sso', false ),
			];
			array_splice( $items, 3, 0, [ $sso_item ] );
		}
		if ( is_plugin_active( 'pressbooks-cas-sso/pressbooks-cas-sso.php' ) ) {
			$sso_item = [
				'title'       => __( 'Configure Single Sign On (SSO)', 'pressbooks' ),
				'link'        => network_admin_url( 'admin.php?page=pb_cas_admin' ),
				'description' => __( 'Allow users to login with their existing institutional credentials' ),
				'checked'     => get_network_option( null, 'network_checklist_configure_sso', false ),
			];
			array_splice( $items, 3, 0, [ $sso_item ] );
		}

		// Check if Network Analytics plugin is activated
		if ( is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' ) ) {
			$forum_item = [
				'title' => __( 'Join the Pressbooks Community Forum', 'pressbooks' ),
				'link' => 'https://pressbooks.community/invites/Rqa9J1wYUN',
				'description' => __( 'Chat with other network managers in a dedicated group' ),
				'checked' => get_network_option( null, 'network_checklist_join_forum', false ),
			];
			$checkin_item = [
				'title' => __( 'Complete your onboarding', 'pressbooks' ),
				'link' => 'https://calendly.com/pb-amy',
				'description' => __( 'Book a meeting to discuss any remaining questions with us' ),
				'checked' => get_network_option( null, 'network_checklist_book_meeting', false ),
			];

			// Insert forum and checkin items at end of the list/array
			$items[] = $forum_item;
			$items[] = $checkin_item;
		}

		return $items;
	}
}
