<?php

use function \Pressbooks\Redirect\{
	flusher,
	migrate_generated_content
};

class RedirectTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @group redirect
	 */
	public function test_redirect() {
		global $_pb_redirect_location;
		$_pb_redirect_location = null;

		$var = "https://press��books.co�m";
		\Pressbooks\Redirect\location( $var );
		$this->assertEquals( 'https://pressbooks.com', $_pb_redirect_location );
	}

	/**
	 * @group redirect
	 */
	public function test_flusher() {
		delete_option( 'pressbooks_flusher' );
		flusher();
		$this->assertTrue( absint( get_option( 'pressbooks_flusher', 1 ) ) > 1 );
	}

	/**
	 * @group redirect
	 */
	public function test_migrate_generated_content() {
		$this->_book();
		migrate_generated_content();
		$this->assertTrue( true ); // Did not crash
	}

	/**
	 * @group redirect
	 */
	public function test_trim_value() {
		// Trim by reference
		$val = '   test    ';
		\Pressbooks\Redirect\trim_value( $val );
		$this->assertEquals( 'test', $val );
	}

	/**
	 * @group redirect
	 */
	public function test_redirect_away_from_bad_urls() {
		global $_pb_redirect_location;
		$_pb_redirect_location = null;
		$_SERVER['HTTP_HOST'] = 'https://pressbooks.test';

		$user_id = $this->factory()->user->create( [ 'role' => 'contributor' ] );
		wp_set_current_user( $user_id );

		// Dashboard
		$_SERVER['REQUEST_URI'] = '/wp-admin';
		\Pressbooks\Redirect\redirect_away_from_bad_urls();
		$this->assertEmpty( $_pb_redirect_location );

		// Trash
		$_SERVER['REQUEST_URI'] = '/wp-admin/edit.php';
		\Pressbooks\Redirect\redirect_away_from_bad_urls();
		$this->assertEmpty( $_pb_redirect_location );

		$_SERVER['REQUEST_URI'] = '/wp-admin/edit.php';
		$_REQUEST['post_status'] = 'trash';
		\Pressbooks\Redirect\redirect_away_from_bad_urls();
		$this->assertNotEmpty( $_pb_redirect_location );
		$_pb_redirect_location = null;
		unset( $_REQUEST['post_status'] );

		$_SERVER['REQUEST_URI'] = '/wp-admin/edit.php';
		$_GET['trashed'] = 1;
		\Pressbooks\Redirect\redirect_away_from_bad_urls();
		$this->assertNotEmpty( $_pb_redirect_location );
		$_pb_redirect_location = null;
		unset( $_GET['trashed'] );

		// New posts, book types
		$_SERVER['REQUEST_URI'] = '/wp-admin/post-new.php';
		$_REQUEST['post_type'] = 'chapter';
		\Pressbooks\Redirect\redirect_away_from_bad_urls();
		$this->assertEmpty( $_pb_redirect_location );
		$_pb_redirect_location = null;
		unset( $_REQUEST['post_type'] );

		$_SERVER['REQUEST_URI'] = '/wp-admin/post-new.php';
		$_REQUEST['post_type'] = 'page';
		\Pressbooks\Redirect\redirect_away_from_bad_urls();
		$this->assertNotEmpty( $_pb_redirect_location );
		$_pb_redirect_location = null;
		unset( $_REQUEST['post_type'] );

		// Taxonomy blacklist
		$_SERVER['REQUEST_URI'] = '/wp-admin/edit-tags.php';
		$_REQUEST['taxonomy'] = 'zig-zag';
		\Pressbooks\Redirect\redirect_away_from_bad_urls();
		$this->assertEmpty( $_pb_redirect_location );
		$_pb_redirect_location = null;
		unset( $_REQUEST['taxonomy'] );

		$_SERVER['REQUEST_URI'] = '/wp-admin/edit-tags.php';
		$_REQUEST['taxonomy'] = 'chapter-type';
		\Pressbooks\Redirect\redirect_away_from_bad_urls();
		$this->assertNotEmpty( $_pb_redirect_location );
		$_pb_redirect_location = null;
		unset( $_REQUEST['taxonomy'] );

		// Disallowed pages
		$_SERVER['REQUEST_URI'] = '/wp-admin/plugin-install.php';
		\Pressbooks\Redirect\redirect_away_from_bad_urls();
		$this->assertNotEmpty( $_pb_redirect_location );
		$_pb_redirect_location = null;
	}

	/**
	 * @group redirect
	 */
	public function test_programmatic_login() {
		$this->assertFalse( \Pressbooks\Redirect\programmatic_login( 'nobody' ) );

		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		$user = get_userdata( $user_id );
		$this->assertTrue( \Pressbooks\Redirect\programmatic_login( $user->user_login ) );
		$logged_in = wp_get_current_user();
		$this->assertEquals( $logged_in->user_login, $user->user_login );
	}

	/**
	 * @test
	 * @group redirect
	 */
	public function test_break_reset_password_loop(): void {
		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		$user = get_userdata( $user_id );
		$requested_redirect_to = 'ignored';

		$redirect_to = 'wp-login.php?action=resetpass';
		$url = \Pressbooks\Redirect\break_reset_password_loop( $redirect_to, $requested_redirect_to, $user );
		$this->assertEquals( admin_url(), $url );

		$redirect_to = 'wp-login.php?action=rp';
		$requested_redirect_to = 'ignored';
		$url = \Pressbooks\Redirect\break_reset_password_loop( $redirect_to, $requested_redirect_to, $user );
		$this->assertEquals( admin_url(), $url );

		$redirect_to = '';
		$url = \Pressbooks\Redirect\break_reset_password_loop( $redirect_to, $requested_redirect_to, $user );
		$this->assertNotEquals( admin_url(), $url );

		$redirect_to = 'wp-login.php?action=rp';
		$user = new WP_Error();
		$url = \Pressbooks\Redirect\break_reset_password_loop( $redirect_to, $requested_redirect_to, $user );
		$this->assertNotEquals( admin_url(), $url );
	}

	/**
	 * @test
	 * @group redirect
	 */
	public function it_redirects_regular_users_to_the_new_user_dashboard(): void {
		$user = get_userdata(
			$this->factory()->user->create( [ 'role' => 'subscriber' ] )
		);

		$redirect_to = admin_url( 'index.php?page=pb_home_page' );

		$this->assertSame(
			$redirect_to,
			\Pressbooks\Redirect\handle_dashboard_redirect( $redirect_to, $redirect_to, $user )
		);
	}

	/**
	 * @test
	 * @group redirect
	 */
	public function it_redirects_regular_users_to_the_book_page(): void {
		$user = get_userdata(
			$this->factory()->user->create( [ 'role' => 'subscriber' ] )
		);

		$this->_book();

		$redirect_to = get_site_url( get_current_blog_id() );

		$this->assertSame(
			$redirect_to,
			\Pressbooks\Redirect\handle_dashboard_redirect( $redirect_to, $redirect_to, $user )
		);
	}

	/**
	 * @test
	 * @group redirect
	 */
	public function it_redirects_super_admins_to_the_new_network_dashboard(): void {
		$user = get_userdata(
			$this->factory()->user->create( [ 'role' => 'administrator' ] )
		);

		grant_super_admin( $user->ID );

		$redirect_to = admin_url( 'index.php?page=pb_home_page' );

		$redirected = \Pressbooks\Redirect\handle_dashboard_redirect( $redirect_to, $redirect_to, $user );

		$this->assertNotSame( $redirect_to, $redirected );
		$this->assertSame( network_admin_url( 'admin.php?page=pb_network_page' ), $redirected );

		$redirect_to = admin_url();

		$redirected = \Pressbooks\Redirect\handle_dashboard_redirect( $redirect_to, $redirect_to, $user );

		$this->assertNotSame( $redirect_to, $redirected );
		$this->assertSame( network_admin_url( 'admin.php?page=pb_network_page' ), $redirected );
	}

	/**
	 * @test
	 * @group redirect
	 */
	public function it_redirects_super_admins_to_the_book_page(): void {
		$user = get_userdata(
			$this->factory()->user->create( [ 'role' => 'administrator' ] )
		);

		grant_super_admin( $user->ID );

		$this->_book();

		$redirect_to = get_site_url( get_current_blog_id() );

		$this->assertSame(
			$redirect_to,
			\Pressbooks\Redirect\handle_dashboard_redirect( $redirect_to, $redirect_to, $user )
		);
	}

	/**
	 * @test
	 * @group redirect
	 */
	public function it_uses_redirect_to_when_no_user_is_provided(): void {
		$redirect_to = home_url( 'wp-login.php' );

		$this->assertSame(
			$redirect_to,
			\Pressbooks\Redirect\handle_dashboard_redirect( $redirect_to, $redirect_to, new WP_Error )
		);

		$this->_book();

		$redirect_to = get_site_url( get_current_blog_id() );

		$this->assertSame(
			$redirect_to,
			\Pressbooks\Redirect\handle_dashboard_redirect( $redirect_to, $redirect_to, new WP_Error )
		);
	}
}
