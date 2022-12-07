<?php
/**
 * @phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
 */
namespace Pressbooks\Admin\Dashboard;

use function Pressbooks\Admin\Laf\book_info_slug;
use function Pressbooks\Image\thumbnail_from_url;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use PressbooksMix\Assets;
use Pressbooks\Container;
use Pressbooks\Metadata;
use SimpleXMLElement;

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
			'book_cover' => $this->getBookCover(),
			'book_url' => get_home_url(),
			'book_info_url' => book_info_slug(),
			'organize_url' => admin_url( 'admin.php?page=pb_organize' ),
			'themes_url' => admin_url( 'themes.php' ),
			'users_url' => admin_url( 'users.php' ),
			'analytics_url' => admin_url( 'index.php?page=koko-analytics' ),
			'delete_book_url' => admin_url( 'ms-delete-site.php' ),
			'webinars' => $this->getWebinarsRssFeed(),
		] );
	}

	protected function getBookCover(): string {
		$cover_image = get_post_meta( ( new Metadata )->getMetaPostId(), 'pb_cover_image', true );
		$cover_image = Str::of( $cover_image );
		if ( $cover_image->endsWith( 'default-book-cover.jpg' ) ) {
			return $cover_image->replace( 'default-book-cover.jpg', 'default-book-cover-225x0@2x.jpg' );
		}
		return thumbnail_from_url( $cover_image, 'pb_cover_medium' );
	}

	protected function getWebinarsRssFeed(): array {
		$webinars = [];

		try {
			$response = ( new Client() )->get(
				'https://pressbooks.com/webinars/feed/', [
					'headers' => [ 'Accept' => 'application/xml' ],
					'timeout' => 120,
				]
			);

			$content = new SimpleXMLElement(
				$response->getBody()->getContents()
			);

			if ( ! $content->channel ) {
				return $webinars;
			}

			$items = 1;

			foreach ( $content->channel->item ?? [] as $item ) {
				if ( $items > 2 ) {
					break;
				}

				$date = Carbon::parse( $item->pubDate )->setTimezone( 'US/Eastern' );

				$webinars[] = [
					'title' => $item->title,
					'link' => $item->link,
					'date' => $date->format( 'M d, Y @ h:i A T' ),
				];

				$items++;
			}

			return $webinars;
		} catch ( GuzzleException ) {
			// TODO: Steel, should we log this?
			return $webinars;
		}
	}
}
