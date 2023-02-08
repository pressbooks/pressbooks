<?php

namespace Pressbooks\Admin\Dashboard;

use PressbooksMix\Assets;

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
		add_action( 'admin_head', [ $this, 'removeDefaultPage' ] );
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
			$assets = new Assets( 'pressbooks', 'plugin' );

			wp_enqueue_style( 'pb-book-dashboard', $assets->getPath( 'styles/pressbooks-dashboard.css' ) );
		} );
	}

	protected abstract function shouldRedirect(): bool;

	protected function shouldRemoveDefaultPage(): bool {
		return true;
	}

	protected function doRedirect(): bool {
		return wp_redirect( $this->getUrl() );
	}
}
