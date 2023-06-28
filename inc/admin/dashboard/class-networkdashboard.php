<?php
/**
 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
namespace Pressbooks\Admin\Dashboard;

use Pressbooks\Container;
use PressbooksMix\Assets;

class NetworkDashboard extends Dashboard {
	protected static ?Dashboard $instance = null;

	protected string $page_name = 'pb_network_page';

	public function hooks(): void {
		add_action( 'load-index.php', [ $this, 'redirect' ] );
		add_action( 'network_admin_menu', [ $this, 'removeDefaultPage' ] );
		add_action( 'network_admin_menu', [ $this, 'addNewPage' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
		add_action( 'wp_ajax_pb-dashboard-checklist', [ $this, 'storeCheck' ]);
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

	public function enqueueAssets(): void
	{
		$assets = new Assets( 'pressbooks', 'plugin' );
		wp_enqueue_script( 'pb-dashboard-checklist', $assets->getPath( 'scripts/dashboards.js' ), false, null );
		wp_localize_script('pb-dashboard-checklist', 'pb_ajax_dashboard', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'nonce' => wp_create_nonce('pb-dashboard-checklist'),
		));
	}

	protected function getNetworkChecklist() : array {
		$items = [
			[
				'id' => 'network_checklist_pressbooks_branding',
				'title' => __( 'Brand your network', 'pressbooks' ),
				'link' => admin_url( 'customize.php' ),
				'description' => __( 'Display your institutional logos and colors across your network', 'pressbooks' ),
				'checked' => get_network_option( null, 'network_checklist_pressbooks_branding', false ),
			],
			[
				'id' => 'network_checklist_customize_homepage',
				'title' => __( 'Customize your homepage', 'pressbooks' ),
				'link' => admin_url( 'edit.php?post_type=page' ),
				'description' => __( 'Edit the textboxes and menu links on your homepage to better orient visitors' ),
				'checked' => get_network_option( null, 'network_checklist_customize_homepage', false ),
			],
			[
				'id' => 'network_checklist_review_network',
				'title' => __( 'Review network settings', 'pressbooks' ),
				'link' => network_admin_url( 'admin.php?page=pb_network_analytics_options' ),
				'description' => __( 'Adjust defaults for new books and user permissions to suit your preferences' ),
				'checked' => get_network_option( null, 'network_checklist_review_network', false ),
			],
			[
				'id' => 'network_checklist_google_analytics',
				'title' => __( 'Configure Google Analytics', 'pressbooks' ),
				'link' => network_admin_url( 'settings.php?page=pb_network_analytics_options#tabs-3' ),
				'description' => __( 'Set up Google Analytics for additional insight into your network’s visitors' ),
				'checked' => get_network_option( null, 'network_checklist_google_analytics', false ),
			],
			[
				'id' => 'network_checklist_join_forum',
				'title' => __( 'Join the Pressbooks Community Forum', 'pressbooks' ),
				'link' => 'https://pressbooks.community/invites/Rqa9J1wYUN',
				'description' => __( 'Communicate with other network managers in a dedicated group on the Pressbooks Forum' ),
				'checked' => get_network_option( null, 'network_checklist_join_forum', false ),
			],
			[
				'id' => 'network_checklist_book_meeting',
				'title' => __( 'Complete your onboarding', 'pressbooks' ),
				'link' => 'https://calendly.com/pb-amy',
				'description' => __( 'Book a short meeting with Pressbooks staff to answer all of your pre-launch questions' ),
				'checked' => get_network_option( null, 'network_checklist_book_meeting', false ),
			],
		];

		// Check if SSO plugin is activated
		if ( is_plugin_active( 'pressbooks-saml-sso/pressbooks-saml-sso.php' ) ) {
			$sso_item = [
				'id'          => 'network_checklist_configure_sso',
				'title'       => __( 'Configure Single Sign On (SSO)', 'pressbooks' ),
				'link'        => network_admin_url( 'admin.php?page=pb_saml_admin' ),
				'description' => __( 'Allow users to login using their existing institutional credentials' ),
				'checked'     => get_network_option( null, 'network_checklist_configure_sso', false ),
			];

			// Insert SSO item at the fourth position
			array_splice( $items, 3, 0, [ $sso_item ] );
		}

		return $items;
	}

	public static function storeCheck(): void
	{
		check_ajax_referer('pb-dashboard-checklist');
		$item = $_POST['item'];
		$current = get_network_option( null, $item );
		wp_send_json_success(update_network_option( null, $item, ! $current));
	}
}
