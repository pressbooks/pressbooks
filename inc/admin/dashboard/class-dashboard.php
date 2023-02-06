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
		add_action( 'load-index.php', [ $this, 'redirectToDashboard' ] );
		add_action( 'admin_head', [ $this, 'removeDefaultDashboard' ] );
		add_action( 'admin_menu', [ $this, 'addDashboard' ] );
	}

	public abstract function shouldRedirect(): bool;

	public abstract function addDashboard(): void;

	public abstract function renderDashboard(): void;

	public function shouldRemoveDefaultDashboard(): bool {
		return true;
	}

	public function getRedirectUrl(): string {
		return admin_url( "index.php?page={$this->page_name}" );
	}

	public function redirectToDashboard(): void {
		if ( ! $this->shouldRedirect() ) {
			return;
		}

		wp_redirect(
			$this->getRedirectUrl()
		);
	}

	public function removeDefaultDashboard(): void {
		if ( ! $this->shouldRemoveDefaultDashboard() ) {
			return;
		}

		remove_submenu_page( $this->root_page, $this->root_page );
	}

	public function enqueueStyles( string $page ): void {
		add_action( "admin_print_styles-{$page}", function() {
			$assets = new Assets( 'pressbooks', 'plugin' );

			wp_enqueue_style( 'pb-book-dashboard', $assets->getPath( 'styles/pressbooks-dashboard.css' ) );
		} );
	}
}
