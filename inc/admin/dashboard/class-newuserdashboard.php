<?php
/**
 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
namespace Pressbooks\Admin\Dashboard;

use function Pressbooks\Admin\Laf\can_create_new_books;
use PressbooksMix\Assets;
use Pressbooks\Cloner\Cloner;
use Pressbooks\Container;

class NewUserDashboard {
	protected static ?NewUserDashboard $instance = null;

	protected string $menu = 'index.php';

	protected string $submenu = 'pb_home_page';

	public static function init(): NewUserDashboard {
		if ( ! static::$instance ) {
			static::$instance = new static();

			static::$instance->hooks();
		}

		return static::$instance;
	}

	public function hooks(): void {
		add_action( 'load-index.php', [ $this, 'redirectToHomePage' ] );
		add_action( 'admin_head', [ $this, 'removeDefaultHomePage' ] );
		add_action( 'admin_menu', [ $this, 'addPressbooksHomePage' ] );
	}

	public function redirectToHomePage(): void {
		$screen = get_current_screen();

		$dashboards = collect( [ 'dashboard', 'dashboard-user' ] );

		if ( $dashboards->doesntContain( $screen->base ) ) {
			return;
		}

		wp_redirect(
			admin_url( "index.php?page={$this->submenu}" )
		);
	}

	public function removeDefaultHomePage(): void {
		if ( is_network_admin() ) {
			return;
		}

		remove_submenu_page( $this->menu, $this->menu );
	}

	public function addPressbooksHomePage(): void {
		$page = add_dashboard_page(
			__( 'Dashboard', 'pressbooks' ),
			__( 'Home', 'pressbooks' ),
			'read',
			$this->submenu,
			[ $this, 'renderHomePage' ],
			0,
		);

		add_action( "admin_print_styles-{$page}", function() {
			$assets = new Assets( 'pressbooks', 'plugin' );

			wp_enqueue_style( 'pb-user-dashboard', $assets->getPath( 'styles/pressbooks-dashboard.css' ) );
		} );
	}

	/**
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Throwable
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public function renderHomePage(): void {
		global $wpdb;

		$blade = Container::get( 'Blade' );

		echo $blade->render( 'admin.dashboard.new-user', [
			'site_name' => get_bloginfo( 'name' ),
			'can_create_new_books' => can_create_new_books(),
			'can_clone_books' => Cloner::isEnabled() && ( can_create_new_books() || is_super_admin() ),
			'invitations' => Invitations::getPendingInvitations(),
		] );
	}
}
