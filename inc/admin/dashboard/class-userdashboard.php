<?php
/**
 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
namespace Pressbooks\Admin\Dashboard;

use function Pressbooks\Admin\Laf\can_create_new_books;
use Pressbooks\Cloner\Cloner;
use Pressbooks\Container;

class UserDashboard extends Dashboard {
	protected static ?Dashboard $instance = null;

	protected string $page_name = 'pb_home_page';

	public function shouldRedirect(): bool {
		$screen = get_current_screen();

		$dashboards = collect( [ 'dashboard', 'dashboard-user' ] );

		return $dashboards->contains( $screen->base );
	}

	public function shouldRemoveDefaultDashboard(): bool {
		return ! is_network_admin();
	}

	public function addDashboard(): void {
		$page = add_dashboard_page(
			__( 'Dashboard', 'pressbooks' ),
			__( 'Home', 'pressbooks' ),
			'read',
			$this->page_name,
			[ $this, 'renderDashboard' ],
			0,
		);

		$this->enqueueStyles( $page );
	}

	/**
	 * @throws ContainerExceptionInterface
	 * @throws Throwable
	 * @throws NotFoundExceptionInterface
	 */
	public function renderDashboard(): void {
		$blade = Container::get( 'Blade' );

		echo $blade->render( 'admin.dashboard.user', [
			'site_name' => get_bloginfo( 'name' ),
			'can_create_new_books' => can_create_new_books(),
			'can_clone_books' => Cloner::isEnabled() && ( can_create_new_books() || is_super_admin() ),
			'invitations' => Invitations::getPendingInvitations(),
		] );
	}
}
