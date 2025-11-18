<?php

namespace Pressbooks\Admin\Dashboard;

abstract class Dashboard {
	protected static ?Dashboard $instance = null;

	protected string $root_page = 'index.php';

	protected array $recentUpdates = [];

	protected string $page_name;

	public static function init(): Dashboard {
		if ( ! static::$instance ) {
			static::$instance = new static();

			static::$instance->hooks();
		}

		return static::$instance;
	}

	public function hooks(): void {
		add_action( 'load-index.php', [ $this, 'redirect' ] );
		add_action( 'admin_menu', [ $this, 'removeDefaultPage' ] );
		add_action( 'admin_menu', [ $this, 'addNewPage' ] );
	}

	public abstract function render(): void;

	public function getUrl(): string {
		return admin_url( "index.php?page={$this->page_name}" );
	}

	public function redirect(): bool {
		if ( ! $this->shouldRedirect() ) {
			return false;
		}

		return $this->doRedirect();
	}

	public function removeDefaultPage(): void {
		if ( ! $this->shouldRemoveDefaultPage() ) {
			return;
		}

		remove_submenu_page( $this->root_page, $this->root_page );
	}

	public function addNewPage(): void {
		$page = add_dashboard_page(
			__( 'Dashboard', 'pressbooks' ),
			__( 'Home', 'pressbooks' ),
			'read',
			$this->page_name,
			[ $this, 'render' ],
			0,
		);

		$this->enqueueStyles( $page );
	}

	public function enqueueStyles( string $page ): void {
		add_action( "admin_print_styles-{$page}", function() {
			/** @var Assets $assets */
			$assets = app( 'Assets' );
			$assets->enqueue( 'assets/src/scripts/dashboard.js', 'pb-book-dashboard' );
		} );
	}

	protected abstract function shouldRedirect(): bool;

	protected function shouldRemoveDefaultPage(): bool {
		return true;
	}

	protected function doRedirect(): bool {
		return wp_redirect( $this->getUrl() );
	}
	protected function fetchUpdates(): void {
		$environment = defined( 'WP_ENV' ) ? WP_ENV : 'production';
		$domain = in_array( $environment, [ 'staging', 'production' ], true ) ? 'https://pressbooks.com' : 'https://dev.pressbooks.com';

		$response = wp_remote_get( "{$domain}/wp-json/dashboard/v1/release-notes", [
			'timeout' => 10,
		] );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			$this->recentUpdates = [];
			return;
		}

		$updates = json_decode( $response['body'], true );

		$this->recentUpdates = [
			...$updates,
			'domain' => $domain,
		];
	}
}
