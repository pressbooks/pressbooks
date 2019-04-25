<?php

class Admin_SiteMapTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Admin\SiteMap()
	 * @group sitemap
	 */
	protected $sitemap;

	/**
	 * @group sitemap
	 */
	public function setUp() {
		parent::setUp();
		$this->sitemap = new \Pressbooks\Admin\SiteMap();
	}


	/**
	 * @group eventstreams
	 */
	public function test_renderPage() {

		// Fake load the admin menu
		global $menu, $submenu;
		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		include_once( ABSPATH . '/wp-admin/menu.php' );

		// Fake load the $wp_admin_bar
		global $wp_admin_bar;
		_wp_admin_bar_init();
		do_action_ref_array( 'admin_bar_menu', [ &$wp_admin_bar ] );

		// This test verifies that something renders,
		// but because Hooks that replace look and feel haven't been loaded,
		// it's the WordPress defaults.

		$this->sitemap->adminBar();
		ob_start();
		$this->sitemap->renderPage();
		$buffer = ob_get_clean();

		$this->assertContains( '<h1>Site Map</h1>', $buffer );
		$this->assertContains( '<h2>Admin Bar</h2>', $buffer );
		$this->assertContains( '<h2>Side Menu</h2>', $buffer );
		$this->assertContains( '>Log Out</a>', $buffer );
		$this->assertContains( '>Dashboard</a>', $buffer );
	}


}
