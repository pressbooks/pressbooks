<?php
/**
 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
namespace Pressbooks\Admin\Dashboard;

use PressbooksMix\Assets;
use Pressbooks\Container;

class NetworkDashboard extends Dashboard {
	protected static ?Dashboard $instance = null;

	protected string $page_name = 'pb_network_page';

	public function hooks(): void {
		add_action( 'load-index.php', [ $this, 'redirect' ] );
		add_action( 'network_admin_menu', [ $this, 'removeDefaultPage' ] );
		add_action( 'network_admin_menu', [ $this, 'addNewPage' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
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
			'network_checklist' => [
				'items' => $this->getNetworkChecklist(),
				'should_display' => $this->shouldDisplayChecklist(),
				'survey_link' => env( 'PB_CHECKLIST_ONBOARDING_SURVEY' ),
			],
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

	public function enqueueAssets(): void {
		$assets = new Assets( 'pressbooks', 'plugin' );
		wp_enqueue_script( 'pb-dashboard-checklist', $assets->getPath( 'scripts/dashboards.js' ), false, null );
		wp_localize_script('pb-dashboard-checklist', 'pb_ajax_dashboard', [
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce' => wp_create_nonce( 'pb-dashboard-checklist' ),
		]);
	}

	public function getNetworkChecklist() : array {
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
				'description' => __( 'Edit the textboxes and menu links on your homepage' ),
				'checked' => get_network_option( null, 'network_checklist_customize_homepage', false ),
			],
			[
				'id' => 'network_checklist_review_network',
				'title' => __( 'Review network settings', 'pressbooks' ),
				'link' => 'https://networkmanagerguide.pressbooks.com/chapter/view-page-visit-and-referrer-statistics/',
				'description' => __( 'Adjust defaults for new books and user permissions ' ),
				'checked' => get_network_option( null, 'network_checklist_review_network', false ),
			],
			[
				'id' => 'network_checklist_google_analytics',
				'title' => __( 'Learn about analytics options', 'pressbooks' ),
				'link' => network_admin_url( 'settings.php?page=pb_network_analytics_options#tabs-3' ),
				'description' => __( 'Learn how to understand your network’s web traffic' ),
				'checked' => get_network_option( null, 'network_checklist_google_analytics', false ),
			],
		];

		// Check if either SSO plugin is activated
		$sso_configurations = [
			[
				'plugin'      => 'pressbooks-saml-sso/pressbooks-saml-sso.php',
				'link'        => network_admin_url( 'admin.php?page=pb_saml_admin' ),
			],
			[
				'plugin'      => 'pressbooks-cas-sso/pressbooks-cas-sso.php',
				'link'        => network_admin_url( 'admin.php?page=pb_cas_admin' ),
			],
			[
				'plugin'      => 'pressbooks-oidc-sso/pressbooks-oidc-sso.php',
				'link'        => network_admin_url( 'admin.php?page=pb_oidc_admin')
			]
		];

		// Check for active SSO plugin and add configuration
		foreach ( $sso_configurations as $sso_configuration ) {
			if ( is_plugin_active( $sso_configuration['plugin'] ) ) {
				$sso_item = [
					'id'          => 'network_checklist_configure_sso',
					'title'       => __( 'Configure Single Sign On (SSO)', 'pressbooks' ),
					'link'        => $sso_configuration['link'],
					'description' => __( 'Allow users to login with their existing institutional credentials' ),
					'checked'     => get_network_option( null, 'network_checklist_configure_sso', false ),
				];

				array_splice( $items, 3, 0, [ $sso_item ] );
				break;
			}
		}

		// Check if Network Analytics plugin is activated
		if ( is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' ) ) {
			$items[] = [
				'id' => 'network_checklist_join_forum',
				'title' => __( 'Join the Pressbooks Community Forum', 'pressbooks' ),
				'link' => 'https://pressbooks.community/invites/Rqa9J1wYUN',
				'description' => __( 'Chat with other network managers in a dedicated group' ),
				'checked' => get_network_option( null, 'network_checklist_join_forum', false ),
			];
			$items[] = [
				'id' => 'network_checklist_book_meeting',
				'title' => __( 'Complete your onboarding', 'pressbooks' ),
				'link' => env( 'PB_CHECKLIST_BOOKING_URL' ),
				'description' => __( 'Book a meeting to discuss any remaining questions with us' ),
				'checked' => get_network_option( null, 'network_checklist_book_meeting', false ),
			];
			$items[] = [
				'id' => 'network_checklist_take_survey',
				'title' => __( 'Take the \'Readiness to Launch\' survey', 'pressbooks' ),
				'link' => env( 'PB_CHECKLIST_ONBOARDING_SURVEY'),
			    'description' => __( 'Complete a brief survey about your onboarding experience', 'pressbooks' ),
			    'checked' => get_network_option( null, 'network_checklist_take_survey', 'pressbooks' ),
			];
		}

		return $items;
	}

	public static function storeCheck(): void {
		check_ajax_referer( 'pb-dashboard-checklist' );
		$item = sanitize_text_field( wp_unslash( $_POST['item'] ?? '' ) );
		$current = get_network_option( null, $item );
		$updated = update_network_option( null, $item, ! $current );
		if ( $updated ) {
			wp_send_json_success(
				[
					'checked' => $updated,
					'completed' => ( new self() )->checkIfAllChecked(),
				]
			);
		}
	}

	public function checkIfAllChecked(): bool {
		$checklist = $this->getNetworkChecklist();
		foreach ( $checklist as $item ) {
			if ( ! $item['checked'] ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Check if the checklist should be displayed if the network is older than X months
	 * PB_CHECKLIST_NETWORK_CREATION_MONTHS_AGO is defined in the .env file
	 *
	 * @return bool
	 */
	public function shouldDisplayChecklist(): bool {

		if ( ! env( 'PB_CHECKLIST_NETWORK_CREATION_MONTHS_AGO' ) ) {
			return false;
		}

		global $wpdb;

		// Get the root site creation date
		$network_creation_date = $wpdb->get_var( $wpdb->prepare( "SELECT registered FROM $wpdb->blogs WHERE site_id = %s", get_main_network_id() ) );

		if ( $network_creation_date ) {
			$current_date = current_time( 'Y-m-d' ); // Use a non-timestamp format
			$months_ago = strtotime( env( 'PB_CHECKLIST_NETWORK_CREATION_MONTHS_AGO' ) );
			$months_ago_date = date( 'Y-m-d', $months_ago );
			return ( strtotime( $network_creation_date ) >= strtotime( $months_ago_date ) && strtotime( $network_creation_date ) <= strtotime( $current_date ) );
		}

		return false;
	}
}
