<?php

namespace Pressbooks\Admin\Menus;

use function Pressbooks\Admin\Laf\can_create_new_books;
use function Pressbooks\Admin\NetworkManagers\is_restricted;
use Pressbooks\Cloner\Cloner;
use Pressbooks\Utility\Icons;
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
		'my-account',
	];

	protected function __construct() {}

	public static function init(): self {
		return tap(
			new self(), fn( TopBar $instance) => $instance->hooks()
		);
	}

	public function hooks(): void {
		add_action( 'admin_bar_menu', [ $this, 'reset' ], 999 );
		add_action( 'admin_bar_menu', [ $this, 'add' ], 999 );
		add_action( 'admin_bar_menu', [ $this, 'reorder' ], 999 );
	}

	public function reset( WP_Admin_Bar $bar ): void {
		$nodes = collect( [
			'wp-logo',
			'pb-network-admin',
			'pb-site-admin',
			'my-books',
			'my-books-list',
		] );

		collect( $bar->get_nodes() )
			->filter( fn ( $node ) => $nodes->contains( $node->id ) || $nodes->contains( $node->parent ) )
			->each( fn ( $node ) => $bar->remove_node( $node->id ) );
	}

	public function add( WP_Admin_Bar $bar ): void {
		$this->updateMyAccount( $bar );

		if ( is_super_admin() ) {
			$this->addAdministerNetwork( $bar );

			$this->addInsertUsers( $bar );
		}

		$this->addMyBooks( $bar );

		$this->updateCurrentBook( $bar );

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

		$avatar = get_avatar( get_current_user_id(), 48 );

		$bar->add_node( [
			'id' => $node->id,
			'title' => $avatar,
		] );
	}

	protected function addAdministerNetwork( WP_Admin_Bar $bar ): void {
		$main_id = 'pb-administer-network';
		$network_analytics_active = is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' );

		$submenus = [
			'pb-administer-network-d' => [
				'title' => __( 'Dashboard', 'pressbooks' ),
				'href' => network_admin_url( 'index.php?pb_network_page' ),
				'visible' => true,
			],
			'pb-administer-books' => [
				'title' => __( 'Books', 'pressbooks' ),
				'href' => network_admin_url( $network_analytics_active ? 'admin.php?page=pb_network_analytics_booklist' : 'sites.php' ),
				'visible' => true,
			],
			'pb-administer-users' => [
				'title' => __( 'Users', 'pressbooks' ),
				'href' => network_admin_url( $network_analytics_active ? 'admin.php?page=pb_network_analytics_userlist' : 'users.php' ),
				'visible' => true,
			],
			'pb-administer-appearance' => [
				'title' => __( 'Appearance', 'pressbooks' ),
				'href' => admin_url( 'customize.php?return=' . network_admin_url() ),
				'visible' => true,
			],
			'pb-administer-pages' => [
				'title' => __( 'Pages', 'pressbooks' ),
				'href' => admin_url( 'edit.php?post_type=page' ),
				'visible' => true,
			],
			'pb-administer-plugins' => [
				'title' => __( 'Plugins', 'pressbooks' ),
				'href' => network_admin_url( 'plugins.php' ),
				'visible' => ! is_restricted(),
			],
			'pb-administer-settings' => [
				'title' => __( 'Settings', 'pressbooks' ),
				'href' => network_admin_url( $network_analytics_active ? 'admin.php?page=pb_network_analytics_options' : 'settings.php' ),
				'visible' => true,
			],
		];

		$title = __( 'Administer Network', 'pressbooks' );

		$bar->add_node( [
			'id' => $main_id,
			'title' => "<i class='pb-heroicons pb-heroicons-building-library'></i><span>{$title}</span>",
			'href' => network_admin_url( 'index.php?pb_network_page' ),
			'meta' => [
				'class' => is_network_admin() ? 'you-are-here' : null,
			],
		] );

		collect( $submenus )
			->filter( fn ( array $submenu ) => $submenu['visible'] )
			->each(fn ( array $submenu, string $id ) => $bar->add_node( [
				'id' => $id,
				'parent' => $main_id,
				'title' => $submenu['title'],
				'href' => $submenu['href'],
			] ) );
	}

	protected function addMyBooks( WP_Admin_Bar $bar ): void {
		$metadata = [
			'class' => is_main_site() && ! is_network_admin() ? 'you-are-here' : null,
		];

		$title = __( 'My Books', 'pressbooks' );

		$bar->add_node( [
			'id' => 'pb-my-books',
			'title' => "<i class='pb-heroicons pb-heroicons-my-books'></i><span>{$title}</span>",
			'href' => admin_url( 'index.php?pb_home_page' ),
			'meta' => array_filter( $metadata ),
		] );

		$books = collect( $bar->user->blogs );

		if ( $books->isEmpty() ) {
			return;
		}

		$bar->add_group( [
			'parent' => 'pb-my-books',
			'id' => 'pb-my-books-list',
			'meta' => [
				'class' => 'ab-sub-secondary ab-submenu',
			],
		] );

		$books->each(function( object $book ) use ( $bar ) {
			if ( is_main_site( $book->userblog_id ) ) {
				return;
			}

			$title = $book->blogname ?? $book->domain;

			$bar->add_node( [
				'parent' => 'pb-my-books-list',
				'id' => "book-{$book->userblog_id}",
				'title' => "<span class='blavatar'></span> {$title}",
				'href' => get_admin_url( $book->userblog_id ),
			] );
		} );
	}

	protected function updateCurrentBook( WP_Admin_Bar $bar ): void {
		$node = $bar->get_node( 'site-name' ) ?? null;

		if ( ! $node ) {
			return;
		}

		$bar->add_node( [
			'id' => $node->id,
			'title' => "<i class='pb-heroicons pb-heroicons-book-open'></i><span>{$node->title}</span>",
		] );
	}

	protected function addCreateBook( WP_Admin_Bar $bar ): void {
		$title = __( 'Create Book', 'pressbooks' );

		$bar->add_node( [
			'id' => 'pb-create-book',
			'parent' => 'top-secondary',
			'title' => "<i class='pb-heroicons pb-heroicons-plus-circle-filled'></i><span>{$title}</span>",
			'href' => network_home_url( 'wp-signup.php' ),
			'meta' => [
				'class' => 'btn action',
			],
		] );
	}

	protected function addCloneBook( WP_Admin_Bar $bar ): void {
		$title = __( 'Clone Book', 'pressbooks' );

		$bar->add_node( [
			'id' => 'pb-clone-book',
			'parent' => 'top-secondary',
			'title' => "<i class='pb-heroicons pb-heroicons-clone-book'></i><span>{$title}</span>",
			'href' => admin_url( 'admin.php?page=pb_cloner' ),
			'meta' => [
				'class' => 'btn action',
			],
		] );
	}

	protected function addInsertUsers( WP_Admin_Bar $bar ): void {
		$title = __( 'Add Users', 'pressbooks' );

		$bar->add_node( [
			'id' => 'pb-add-users',
			'parent' => 'top-secondary',
			'title' => "<i class='pb-heroicons pb-heroicons-user'></i><span><span>{$title}</span>",
			'href' => network_admin_url( 'users.php?page=user_bulk_new' ),
			'meta' => [
				'class' => 'btn action',
			],
		] );
	}
}
