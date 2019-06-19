<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/laf/namespace.php' );


class Admin_LafTest extends \WP_UnitTestCase {

	use utilsTrait;

	function test_can_create_new_book() {
		$reset_back_to_old_value = get_site_option( 'registration' );

		update_site_option( 'registration', 'none' );
		$this->assertFalse( \Pressbooks\Admin\Laf\can_create_new_books() );
		update_site_option( 'registration', 'user' );
		$this->assertFalse( \Pressbooks\Admin\Laf\can_create_new_books() );

		update_site_option( 'registration', 'blog' );
		$this->assertTrue( \Pressbooks\Admin\Laf\can_create_new_books() );
		update_site_option( 'registration', 'all' );
		$this->assertTrue( \Pressbooks\Admin\Laf\can_create_new_books() );

		update_site_option( 'registration', $reset_back_to_old_value );
	}

	/**
	 * @group branding
	 */
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

	/**
	 * @group branding
	 */
	function test_replace_book_admin_menu_AND_init_css_js() {

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

		// -------------------------------------------------------------------
		// Enqueue tests
		// -------------------------------------------------------------------

		global $wp_scripts, $wp_styles;
		\Pressbooks\Admin\Laf\init_css_js();

		$new_post['post_type'] = 'chapter';
		$GLOBALS['post'] = get_post( $this->factory()->post->create_object( $new_post ) );
		$GLOBALS['current_screen'] = WP_Screen::get( 'chapter' );
		do_action( 'admin_enqueue_scripts', 'post.php' );
		$this->assertContains( 'pb-post-visibility', $wp_scripts->queue );

		$new_post['post_type'] = 'back-matter';
		$GLOBALS['post'] =  get_post( $this->factory()->post->create_object( $new_post ) );
		$GLOBALS['current_screen'] = WP_Screen::get( 'back-matter' );
		do_action( 'admin_enqueue_scripts', 'post.php' );
		$this->assertContains( 'pb-post-back-matter', $wp_scripts->queue );

		$GLOBALS['post'] = ( new \Pressbooks\Metadata )->getMetaPost();
		$GLOBALS['current_screen'] = WP_Screen::get( 'metadata' );
		do_action( 'admin_enqueue_scripts', 'post.php' );
		$this->assertContains( 'pb-metadata', $wp_scripts->queue );

		$new_post['post_type'] = 'post';
		$GLOBALS['post'] =  get_post( $this->factory()->post->create_object( $new_post ) );
		$GLOBALS['current_screen'] = WP_Screen::get( 'post' );
		do_action( 'admin_enqueue_scripts', 'toplevel_page_pb_organize' );
		$this->assertContains( 'pb-organize', $wp_scripts->queue );
		$this->assertContains( 'pb-organize', $wp_styles->queue );

		do_action( 'admin_enqueue_scripts', 'toplevel_page_pb_export' );
		$this->assertContains( 'pb-export', $wp_scripts->queue );
		$this->assertContains( 'pb-export', $wp_styles->queue );

		do_action( 'admin_enqueue_scripts', 'admin_page_pb_import' );
		$this->assertContains( 'pb-import', $wp_scripts->queue );

		do_action( 'admin_enqueue_scripts', 'admin_page_pb_cloner' );
		$this->assertContains( 'pb-cloner', $wp_scripts->queue );

		unset( $GLOBALS['post'], $GLOBALS['current_screen'] ); // Cleanup
	}

	/**
	 * @group branding
	 */
	function test_custom_screen_options() {
		$x = \Pressbooks\Admin\Laf\custom_screen_options( false, 'pb_export_per_page', 9 );
		$this->assertEquals( $x, 9 );
		$x = \Pressbooks\Admin\Laf\custom_screen_options( false, 'pb_export_per_page', '9' );
		$this->assertEquals( $x, 9 );
		$x = \Pressbooks\Admin\Laf\custom_screen_options( false, 'pb_export_per_page', 'int' );
		$this->assertEquals( $x, 0 );
		$x = \Pressbooks\Admin\Laf\custom_screen_options( false, 'fake_records', 9 );
		$this->assertTrue( $x === false );
	}

	/**
	 * @group branding
	 */
	function test_reorder_book_admin_menu() {
		$order = \Pressbooks\Admin\Laf\reorder_book_admin_menu();
		$this->assertEquals( $order[4], 'post-new.php?post_type=metadata' );
	}

	/**
	 * @group branding
	 */
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

	/**
	 * @group branding
	 */
	function test_display_export() {
		$GLOBALS['hook_suffix'] = 'mock';
		ob_start();
		\Pressbooks\Admin\Laf\display_export();
		$buffer = ob_get_clean();
		$this->assertContains( '<h1>Export', $buffer );
		$this->assertContains( '<div class="clear"></div>', $buffer );
	}

	/**
	 * @group branding
	 */
	function test_admin_notices() {
		$_SESSION['pb_errors'] = 'One';
		set_site_transient( 'pb_errors' . get_current_user_id(), 'Two' );
		$_SESSION['pb_notices'] = 'Three';
		set_site_transient( 'pb_notices' . get_current_user_id(), 'Four' );
		ob_start();
		\Pressbooks\Admin\Laf\admin_notices();
		$buffer = ob_get_clean();
		$this->assertEquals( '<div class="error" role="alert"><p>One</p></div><div class="error" role="alert"><p>Two</p></div><div class="updated" role="status"><p>Three</p></div><div class="updated" role="status"><p>Four</p></div>', $buffer );

		$_SESSION['pb_errors'][] = 'One';
		$_SESSION['pb_errors'][] = 'Two';
		set_site_transient( 'pb_errors' . get_current_user_id(), [ 'Three', 'Four' ] );
		$_SESSION['pb_notices'][] = 'Five';
		$_SESSION['pb_notices'][] = 'Six';
		set_site_transient( 'pb_notices' . get_current_user_id(), [ 'Seven', 'Eight' ] );
		ob_start();
		\Pressbooks\Admin\Laf\admin_notices();
		$buffer = ob_get_clean();
		$this->assertEquals( '<div class="error" role="alert"><p>One</p></div><div class="error" role="alert"><p>Two</p></div><div class="error" role="alert"><p>Three</p></div><div class="error" role="alert"><p>Four</p></div><div class="updated" role="status"><p>Five</p></div><div class="updated" role="status"><p>Six</p></div><div class="updated" role="status"><p>Seven</p></div><div class="updated" role="status"><p>Eight</p></div>', $buffer );

		\Pressbooks\add_error( 'A' );
		\Pressbooks\add_error( 'B' );
		\Pressbooks\add_notice( 'C' );
		\Pressbooks\add_notice( 'D' );
		ob_start();
		\Pressbooks\Admin\Laf\admin_notices();
		$buffer = ob_get_clean();
		$this->assertEquals( '<div class="error" role="alert"><p>A</p></div><div class="error" role="alert"><p>B</p></div><div class="updated" role="status"><p>C</p></div><div class="updated" role="status"><p>D</p></div>', $buffer );

		ob_start();
		\Pressbooks\Admin\Laf\admin_notices();
		$buffer = ob_get_clean();
		$this->assertEmpty( $buffer );
	}

	/**
	 * @group branding
	 */
	function test_sites_to_books() {
		$result = \Pressbooks\Admin\Laf\sites_to_books( __( 'Sites' ), 'Sites', '' );
		$this->assertEquals( 'Books', $result );
	}

	/**
	 * @group branding
	 */
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
