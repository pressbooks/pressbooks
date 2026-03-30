<?php

namespace Pressbooks\Admin\Dashboard;

abstract class Dashboard {
	protected static ?Dashboard $instance = null;

	protected string $root_page = 'index.php';

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
			/** @var \PressbooksFrontendTools\Assets $assets */
			$assets = app( 'Assets' );

			// Standalone CSS must use wp_enqueue_style (not $assets->enqueue which wraps in script tags)
			wp_enqueue_style(
				'pb-dashboard-styles',
				$assets->getAssetUrl( 'assets/src/styles/pressbooks-dashboard.css' )
			);
		} );
	}

	protected abstract function shouldRedirect(): bool;

	protected function shouldRemoveDefaultPage(): bool {
		return true;
	}

	protected function doRedirect(): bool {
		return wp_redirect( $this->getUrl() );
	}

	/**
	 * @return array{content: string|null, post_id: integer|null, last_updated: string|null, domain: string|null}
	 */
	protected function fetchUpdates(): array {
		$environment = defined( 'WP_ENV' ) ? WP_ENV : 'production';
		$domain = in_array( $environment, [ 'staging', 'production' ], true ) ? 'https://pressbooks.com' : 'https://dev.pressbooks.com';

		$transient = get_transient( 'pressbooks_recent_updates' );

		if ( $transient ) {
			$updates = json_decode( $transient, true );
		} else {
			$response = wp_remote_get("{$domain}/wp-json/dashboard/v1/release-notes", [
				'timeout' => 10,
			]);

			if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
				return [];
			}

			set_transient( 'pressbooks_recent_updates', $response['body'], HOUR_IN_SECONDS );

			$updates = json_decode( $response['body'], true );
		}

		return [
			...$updates,
			'domain' => $domain,
		];
	}
}
