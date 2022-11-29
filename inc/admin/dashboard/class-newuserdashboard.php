<?php
/**
 * TODO: phpcs detects named arguments as "goto" language, remove this once we upgrade phpcs.
 * @phpcs:disable Generic.PHP.DiscourageGoto.Found
 */
namespace Pressbooks\Admin\Dashboard;

use Pressbooks\Container;
use PressbooksMix\Assets;

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
		if ( ! is_main_site() ) {
			return;
		}

		add_action( hook_name: 'load-index.php', callback: [ $this, 'redirectToHomePage' ] );
		add_action( hook_name: 'admin_head', callback: [ $this, 'removeDefaultHomePage' ] );
		add_action( hook_name: 'admin_menu', callback: [ $this, 'addPressbooksHomePage' ] );
	}

	public function redirectToHomePage(): void {
		$screen = get_current_screen();

		if ( $screen->base !== 'dashboard' ) {
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

		remove_submenu_page( menu_slug: $this->menu, submenu_slug: $this->menu );
	}

	public function addPressbooksHomePage(): void {
		$page = add_dashboard_page(
			page_title: __( 'Dashboard', 'pressbooks' ),
			menu_title: __( 'Home', 'pressbooks' ),
			capability: 'read',
			menu_slug: $this->submenu,
			callback: [ $this, 'renderHomePage' ],
			position: 0,
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
		/** @var \Illuminate\View\View $blade */
		$blade = Container::get( 'Blade' );

		echo $blade->render( 'admin.dashboard.new-user' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
