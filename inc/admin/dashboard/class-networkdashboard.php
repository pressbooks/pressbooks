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
		add_action( 'load-index.php', [ $this, 'redirectToDashboard' ] );
		add_action( 'admin_head', [ $this, 'removeDefaultDashboard' ] );
		add_action( 'network_admin_menu', [ $this, 'addDashboard' ] );
	}

	public function shouldRedirect(): bool {
		$screen = get_current_screen();

		return $screen->base === 'dashboard-network';
	}

	public function getRedirectUrl(): string {
		return network_admin_url( "index.php?page={$this->page_name}" );
	}

	public function shouldRemoveDefaultDashboard(): bool {
		return is_network_admin();
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws Throwable
	 * @throws NotFoundExceptionInterface
	 */
	public function renderDashboard(): void {
		$blade = Container::get( 'Blade' );

		echo $blade->render( 'admin.dashboard.network', [
			'network_name' => get_bloginfo( 'name' ),
			'total_users' => get_user_count(),
			'total_books' => $this->getTotalNumberOfBooks(),
		] );
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
}
