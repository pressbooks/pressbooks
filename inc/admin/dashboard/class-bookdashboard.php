<?php
/**
 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
namespace Pressbooks\Admin\Dashboard;

use PressbooksMix\Assets;
use Pressbooks\Container;

class BookDashboard {
	protected static ?BookDashboard $instance = null;

	protected string $menu = 'index.php';

	protected string $submenu = 'book_dashboard';

	public static function init(): BookDashboard {
		if ( ! static::$instance ) {
			static::$instance = new static();

			static::$instance->hooks();
		}

		return static::$instance;
	}

	public function hooks(): void {
		add_action( 'load-index.php', [ $this, 'redirectToBookDash' ] );
		add_action( 'admin_head', [ $this, 'removeDefaultBookDash' ] );
		add_action( 'admin_menu', [ $this, 'addPressbooksBookDash' ] );
	}

	public function redirectToBookDash(): void {
		wp_redirect(
			//TODO: usesiteurl ( "index.php?page={$this->submenu}" )
		);
	}

	public function removeDefaultBookDash(): void {
		remove_submenu_page( $this->menu, $this->menu );
	}

	public function addPressbooksBookDash(): void {
		$page = add_dashboard_page(
			__( 'Dashboard', 'pressbooks' ),
			__( 'Home', 'pressbooks' ),
			'read',
			$this->submenu,
			[ $this, 'renderBookDashboard' ],
			0,
		);

		add_action( "admin_print_styles-{$page}", function() {
			$assets = new Assets( 'pressbooks', 'plugin' );

			wp_enqueue_style( 'pb-book-dashboard', $assets->getPath( 'styles/pressbooks-dashboard.css' ) );
		} );
	}

	/**
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Throwable
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public function renderBookDashboard(): void {
		global $wpdb;

		$blade = Container::get( 'Blade' );

		echo $blade->render( 'admin.dashboard.book', [
			'site_name' => get_bloginfo( 'name' )
		] );
	}
}
