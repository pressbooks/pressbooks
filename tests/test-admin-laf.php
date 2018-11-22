<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/laf/namespace.php' );


class Admin_LafTest extends \WP_UnitTestCase {

	use utilsTrait;

	function test_add_footer_link() {

		ob_start();
		\Pressbooks\Admin\Laf\add_footer_link();
		$buffer = ob_get_clean();

		$this->assertContains( 'Powered by', $buffer );
		$this->assertContains( 'Pressbooks', $buffer );

		add_filter( 'pb_help_link', function() {
			return 'https://pressbooks.community/';
		} );

		add_filter( 'pb_contact_link', function() {
			return 'https://pressbooks.org/contact';
		} );

		ob_start();
		\Pressbooks\Admin\Laf\add_footer_link();
		$buffer = ob_get_clean();

		$this->assertContains( 'https://pressbooks.community/', $buffer );
		$this->assertContains( 'https://pressbooks.org/contact', $buffer );
	}

	function test_replace_book_admin_menu() {

		global $menu, $submenu;

		// Fake load the admin menu
		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		include_once( ABSPATH . '/wp-admin/menu.php' );

		\Pressbooks\Admin\Laf\replace_book_admin_menu();

		$this->assertEquals( $menu[12][0], 'Book Info' );
		$this->assertEquals( $menu[14][0], 'Export' );
		$this->assertEquals( $menu[16][0], 'Publish' );
		$this->assertNotContains(
			[
				'QuickLaTex',
				'manage_options',
				'quicklatex-settings',
				'quicklatex_options_do_page',
			],
			$submenu['options-general.php']
		);
	}

	function test_reorder_book_admin_menu() {
		$order = \Pressbooks\Admin\Laf\reorder_book_admin_menu();
		$this->assertEquals( $order[4], 'post-new.php?post_type=metadata' );
	}

	function test_replace_menu_bar_my_sites() {
		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		$wp_admin_bar = new \WP_Admin_Bar();
		$wp_admin_bar->initialize();
		\Pressbooks\Admin\Laf\replace_menu_bar_my_sites( $wp_admin_bar );

		$node = $wp_admin_bar->get_node( 'my-books' );
		$this->assertTrue( is_object( $node ) );
		$this->assertEquals( $node->id, 'my-books' );

		$node = $wp_admin_bar->get_node( 'clone-a-book' );
		$this->assertTrue( is_object( $node ) );
		$this->assertEquals( $node->id, 'clone-a-book' );
	}

	function test_display_export() {
		ob_start();
		\Pressbooks\Admin\Laf\display_export();
		$buffer = ob_get_clean();
		$this->assertContains( '<h2>Export', $buffer );
		$this->assertContains( '<div class="clear"></div>', $buffer );
	}

	function test_sites_to_books() {
		$result = \Pressbooks\Admin\Laf\sites_to_books( __( 'Sites' ), 'Sites', '' );
		$this->assertEquals( 'Books', $result );
	}

	function test_edit_screen_navigation() {

		$this->_book();

		// Mock
		global $post, $pagenow;
		$pid1 = \Pressbooks\Book::getFirst( true );
		$post = get_post( $pid1 );
		$pid2 = \Pressbooks\Book::get( 'next', true );
		$post = get_post( $pid2 );
		$pagenow = 'post.php';
		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		ob_start();
		\Pressbooks\Admin\Laf\edit_screen_navigation( $post );
		$buffer = ob_get_clean();

		$this->assertContains( '<nav id="pb-edit-screen-navigation" role="navigation"', $buffer );
		$this->assertContains( '<a href', $buffer );
		$this->assertContains( 'Edit Previous', $buffer );
		$this->assertContains( 'Edit Next', $buffer );
	}
}
