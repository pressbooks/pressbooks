<?php

namespace Pressbooks\Admin\Menus;

use function Pressbooks\Admin\Laf\can_create_new_books;
use Illuminate\Support\Str;
use Pressbooks\Cloner\Cloner;
use WP_Admin_Bar;

class TopBar {
	protected array $order = [
		'pb-administer-network',
		'pb-my-books',
		'site-name',
		'updates',
		'pb-create-book',
		'pb-clone-book',
		'pb-add-users',
	];

	public static function init(): self {
		return tap(
			new self, fn( TopBar $instance) => $instance->hooks()
		);
	}

	public function hooks(): void {
		add_action( 'admin_bar_menu', [ $this, 'reset' ], 999 );
		add_action( 'admin_bar_menu', [ $this, 'add' ], 999 );
		add_action( 'admin_bar_menu', [ $this, 'reorder' ], 999 );
	}

	public function reset( WP_Admin_Bar $bar ): void {
		$bar->remove_node( 'wp-logo' );
		$bar->remove_node( 'pb-network-admin' ); // This will be reworked
		$bar->remove_node( 'pb-site-admin' ); // This wil be reworked
		$bar->remove_node( 'my-books' );
	}

	public function add( WP_Admin_Bar $bar ): void {
		$this->updateMyAccount( $bar );

		if ( is_super_admin() ) {
			$this->addAdministerNetwork( $bar );

			$this->addInsertUsers( $bar );
		}

		$this->addMyBooks( $bar );

		$this->addCurrentBook( $bar );

		if ( can_create_new_books() ) {
			$this->addCreateBook( $bar );
		}

		if ( Cloner::isEnabled() && ( can_create_new_books() || is_super_admin() ) ) {
			$this->addCloneBook( $bar );
		}
	}

	public function reorder( WP_Admin_Bar $bar ): void {
		$nodes = collect( $bar->get_nodes() ?? [] );

		collect( $this->order )
			->each( function ( string $id ) use ( $bar, $nodes ) {
				if ( ! $nodes->has( $id ) ) {
					return;
				}

				$bar->remove_node( $id );
				$bar->add_node( $nodes->get( $id ) );
			} );
	}

	protected function updateMyAccount( WP_Admin_Bar $bar ): void {
		$node = $bar->get_node( 'my-account' ) ?? null;

		if ( ! $node ) {
			return;
		}

		$title = Str::of( $node->title );

		$to_remove = $title->before( '<img' );

		$bar->add_node( [
			'id' => 'my-account',
			'title' => $title->remove( $to_remove ),
			'href' => null,
		] );
	}

	protected function addAdministerNetwork( WP_Admin_Bar $bar ): void {
		$metadata = [
			'class' => is_network_admin() ? 'active' : null,
		];

		$bar->add_node([
			'id' => 'pb-administer-network',
			'title' => __( 'Administer Network', 'pressbooks' ),
			'href' => network_admin_url(), // TODO: this should be the URL to the new dashboard
			'meta' => array_filter( $metadata ),
		]);
	}

	protected function addMyBooks( WP_Admin_Bar $bar ): void {
		$metadata = [
			'class' => is_main_site() && ! is_network_admin() ? 'active' : null,
		];

		$bar->add_node( [
			'id' => 'pb-my-books',
			'title' => __( 'My Books', 'pressbooks' ),
			'href' => admin_url( 'index.php?pb_home_page' ),
			'meta' => array_filter( $metadata ),
		] );

		$books = collect( $bar->user->blogs );

		if ( $books->isEmpty() ) {
			return;
		}

		$bar->add_group( [
			'parent' => 'pb-my-books',
			'id' => 'my-books-list',
		] );

		$books->each(function( object $book ) use ( $bar ) {
			if ( is_main_site( $book->userblog_id ) ) {
				return;
			}

			$title = $book->blogname ?? $book->domain;

			$bar->add_node( [
				'parent' => 'my-books',
				'id' => "book-{$book->userblog_id}",
				'title' => "<span class='blavatar' /> {$title}",
				'href' => get_admin_url( $book->userblog_id ),
			] );
		} );
	}

	protected function addCurrentBook( WP_Admin_Bar $bar ): void {

	}

	protected function addCreateBook( WP_Admin_Bar $bar ): void {
		$bar->add_node( [
			'id' => 'pb-create-book',
			'title' => __( 'Create Book', 'pressbooks' ),
			'href' => network_home_url( 'wp-signup.php' ),
			'meta' => [
				'class' => 'btn action',
			],
		] );
	}

	protected function addCloneBook( WP_Admin_Bar $bar ): void {
		$bar->add_node( [
			'id' => 'pb-clone-book',
			'title' => __( 'Clone Book', 'pressbooks' ),
			'href' => admin_url( 'admin.php?page=pb_cloner' ),
			'meta' => [
				'class' => 'btn action',
			],
		] );
	}

	protected function addInsertUsers( WP_Admin_Bar $bar ): void {
		$bar->add_node( [
			'id' => 'pb-add-users',
			'title' => __( 'Add Users', 'pressbooks' ),
			'href' => network_admin_url( 'users.php?page=user_bulk_new' ),
			'meta' => [
				'class' => 'btn action',
			],
		] );
	}
}
