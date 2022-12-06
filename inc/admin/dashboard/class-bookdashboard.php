<?php
/**
 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
namespace Pressbooks\Admin\Dashboard;

use Illuminate\Support\Str;
use PressbooksMix\Assets;
use Pressbooks\Container;
use function Pressbooks\Admin\Laf\book_info_slug;

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
		$screen = get_current_screen();

		if ( $screen->base !== 'dashboard' ) {
			return;
		}

		wp_redirect(
			admin_url( "index.php?page={$this->submenu}" )
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
		$blade = Container::get( 'Blade' );

		echo $blade->render( 'admin.dashboard.book', [
			'site_name' => get_bloginfo( 'name' ),
			'book_url' => get_home_url(),
			'book_info_url' => book_info_slug(),
			'organize_url' => admin_url( 'admin.php?page=pb_organize' ),
			'themes_url' => admin_url( 'themes.php' ),
			'users_url' => admin_url( 'users.php' ),
			'analytics_url' => admin_url( 'index.php?page=koko-analytics' ),
			'delete_book_url' => admin_url( 'ms-delete-site.php' ),
			'webinar_rss' => $this->getWebinarsRssFeed(),
		] );
	}

	protected function getWebinarsRssFeed(): string {
		ob_start();

		wp_widget_rss_output( 'https://pressbooks.com/webinars/feed/', [
			'items' => 3,
			'show_summary' => 1,
			'show_author' => 0,
			'show_date' => 0,
		] );

		$rss = ob_get_clean();

		return Str::contains( $rss, 'An error has occurred, which probably means the feed is down. Try again later' )
			? '<p>' . __( 'There are currently no upcoming webinars scheduled.', 'pressbooks' ) . '</p>'
			: $rss;
	}
}
