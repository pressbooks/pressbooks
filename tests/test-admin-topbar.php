<?php

use Illuminate\Support\Str;
use Pressbooks\Admin\Menus\TopBar;

class testAdminTopbar extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @test
	 * @group topbar
	 */
	public function admin_bar_is_modified_for_super_admins(): void {
		$this->createSuperAdminUser();
		$modified_menu = $this->getAdminBar()->get_nodes();

		$site_name = get_bloginfo( 'name' );

		$expected_order = [
			'Dashboard',
			'Books',
			'Users',
			'Appearance',
			'Pages',
			'Plugins',
			'Settings',
			false,
			"<span class='blavatar'></span> {$site_name}",
			'<span class="ab-icon"></span><span class="screen-reader-text">About Pressbooks</span>',
			'<span>Administer Network</span>',
			'<span>My Books</span>',
			'<span>Create Book</span>',
			'<span>Clone Book</span>',
			'<span>Add Users</span>',
		];

		$items_ordered = $this->mapValues( $modified_menu, 'title' );

		$this->assertEquals( $expected_order, $items_ordered );
	}

	/**
	 * @test
	 * @group topbar
	 */
	public function admin_bar_is_modified_for_non_admins(): void {
		$this->createSubscriberUser();
		$modified_menu = $this->getAdminBar()->get_nodes();

		$site_name = get_bloginfo( 'name' );

		$expected_order = [
			false,
			"<span class='blavatar'></span> {$site_name}",
			'<span class="ab-icon"></span><span class="screen-reader-text">About Pressbooks</span>',
			'<span>My Books</span>',
			'<span>Create Book</span>',
			'<span>Clone Book</span>',
		];

		$items_ordered = $this->mapValues( $modified_menu , 'title' );

		$this->assertEquals( $expected_order, $items_ordered );
	}

	private function getAdminBar(): WP_Admin_Bar {
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';

		$wp_admin_bar = new \WP_Admin_Bar();
		$wp_admin_bar->initialize();

		TopBar::init();

		do_action( 'admin_bar_menu', $wp_admin_bar );
		set_current_screen( 'dashboard' );

		return $wp_admin_bar;
	}

	private function mapValues( array $items, string $key ): array {
		return array_values(
			array_map( function ( $item ) use ( $key ) {
				if ( ! is_string( $item->$key ) ) {
					return $item->$key;
				}

				$value = Str::of( $item->$key );

				return $value->contains( '<span>' ) ?
					(string) $value->remove( $value->before( '<span>' ) ) :
					(string) $value;
			}, $items )
		);
	}
}
