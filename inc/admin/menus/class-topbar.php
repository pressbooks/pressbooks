<?php

namespace Pressbooks\Admin\Menus;

use function Pressbooks\Admin\Laf\can_create_new_books;
use function Pressbooks\Admin\NetworkManagers\is_restricted;
use Pressbooks\Cloner\Cloner;
use Pressbooks\Utility\Icons;
use WP_Admin_Bar;

class TopBar {
	protected array $order = [
		'pb-logo',
		'pb-administer-network',
		'pb-my-books',
		'site-name',
		'view',
		'updates',
		'pb-create-book',
		'pb-clone-book',
		'pb-add-users',
		'my-account',
	];

	protected function __construct( protected Icons $icons ) {
	}

	public static function init(): self {
		return tap(
			new self( new Icons ), fn( TopBar $instance) => $instance->hooks()
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

		$this->addPressbooksLogo( $bar );

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

	protected function addPressbooksLogo( WP_Admin_Bar $bar ): void {
		$bar->add_menu(
			[
				'id' => 'pb-logo',
				'title' => '<span class="ab-icon"></span><span class="screen-reader-text">' . __( 'About Pressbooks', 'pressbooks' ) . '</span>',
				'href' => 'https://pressbooks.com',
			]
		);
	}

	protected function addAdministerNetwork( WP_Admin_Bar $bar ): void {
		$main_id = 'pb-administer-network';

		$network_analytics_active = is_plugin_active( 'pressbooks-network-analytics/pressbooks-network-analytics.php' );
		$koko_analytics_active = is_plugin_active( 'koko-analytics/koko-analytics.php' );
		$pressbooks_stats_active = is_plugin_active( 'pressbooks-stats/pressbooks-stats.php' );

		$submenus = [
			'pb-administer-network-d' => [
				'title' => __( 'Dashboard', 'pressbooks' ),
				'href' => network_admin_url( 'index.php?page=pb_network_page' ),
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
				'href' => admin_url( 'customize.php' ),
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
			'pb-administer-stats' => [
				'title' => __( 'Stats', 'pressbooks' ),
				'href' => $this->getStatsPageUrl( $network_analytics_active, $koko_analytics_active, $pressbooks_stats_active ),
				'visible' => $network_analytics_active || $koko_analytics_active || ( ! is_restricted() && $pressbooks_stats_active ),
			],
		];

		$title = __( 'Administer Network', 'pressbooks' );
		$svg = $this->icons->render( 'building-library' );

		$bar->add_node( [
			'id' => $main_id,
			'title' => "$svg <span>{$title}</span>",
			'href' => network_admin_url( 'index.php?page=pb_network_page' ),
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
		$svg = $this->icons->render( 'my-books' );

		$bar->add_node( [
			'id' => 'pb-my-books',
			'title' => "$svg <span>{$title}</span>",
			'href' => get_admin_url( get_main_site_id() ) . 'index.php?page=pb_home_page',
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

		$svg = $this->icons->render( 'book-open' );
		$title = $node->title;

		$bar->add_node( [
			'id' => $node->id,
			'title' => "$svg <span>{$node->title}</span>",
		] );
	}

	protected function addCreateBook( WP_Admin_Bar $bar ): void {
		$title = __( 'Create Book', 'pressbooks' );
		$svg = $this->icons->render( icon: 'plus-circle', solid: true );

		$bar->add_node( [
			'id' => 'pb-create-book',
			'parent' => 'top-secondary',
			'title' => "$svg <span>{$title}</span>",
			'href' => network_home_url( 'wp-signup.php' ),
			'meta' => [
				'class' => 'btn action',
			],
		] );
	}

	protected function addCloneBook( WP_Admin_Bar $bar ): void {
		$title = __( 'Clone Book', 'pressbooks' );
		$svg = $this->icons->render( icon: 'clone-books', solid: true );

		$bar->add_node( [
			'id' => 'pb-clone-book',
			'parent' => 'top-secondary',
			'title' => "$svg <span>{$title}</span>",
			'href' => admin_url( 'admin.php?page=pb_cloner' ),
			'meta' => [
				'class' => 'btn action',
			],
		] );
	}

	protected function addInsertUsers( WP_Admin_Bar $bar ): void {
		$title = __( 'Add Users', 'pressbooks' );
		$svg = $this->icons->render( icon: 'user-plus', solid: true );

		$bar->add_node( [
			'id' => 'pb-add-users',
			'parent' => 'top-secondary',
			'title' => "$svg <span>{$title}</span>",
			'href' => network_admin_url( 'users.php?page=user_bulk_new' ),
			'meta' => [
				'class' => 'btn action',
			],
		] );
	}

	protected function getStatsPageUrl( bool $network_analytics_active, bool $koko_analytics_active, bool $pressbooks_stats_active ): string|null {
		if ( $network_analytics_active ) {
			return network_admin_url( 'admin.php?page=pb_network_analytics_admin' );
		}

		if ( $koko_analytics_active ) {
			return admin_url( 'admin.php?page=koko-analytics' );
		}

		// Only super admins are allowed to see pb_stats
		if ( ! is_restricted() && $pressbooks_stats_active ) {
			return network_admin_url( 'admin.php?page=pb_stats' );
		}

		return null;
	}
}
