<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/laf/namespace.php' );


class Admin_LafsTest extends \WP_UnitTestCase {

	use utilsTrait;

	function test_add_footer_link() {

		ob_start();
		\Pressbooks\Admin\Laf\add_footer_link();
		$buffer = ob_get_clean();

		$this->assertContains( 'Powered by', $buffer );
		$this->assertContains( 'Pressbooks', $buffer );
	}

	function test_admin_title() {

		$result = \Pressbooks\Admin\Laf\admin_title( 'Hello WordPress!' );
		$this->assertEquals( $result, 'Hello Pressbooks!' );

		$result = \Pressbooks\Admin\Laf\admin_title( 'Hello World!' );
		$this->assertEquals( $result, 'Hello World!' );
	}

	function test_replace_book_admin_menu() {

		global $menu, $submenu;

		// Fake load the admin menu
		$this->_book();
		$user_id = $this->factory->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		include_once( ABSPATH . '/wp-admin/menu.php' );

		\Pressbooks\Admin\Laf\replace_book_admin_menu();

		$this->assertEquals( $menu[12][0], 'Book Info' );
		$this->assertEquals( $menu[14][0], 'Export' );
		$this->assertEquals( $menu[16][0], 'Publish' );

		$this->assertArrayHasKey( 'edit.php?post_type=part', $submenu );
		$this->assertArrayHasKey( 'edit.php?post_type=chapter', $submenu );
	}

}
