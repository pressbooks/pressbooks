<?php

use function \Pressbooks\Redirect\{
	flusher,
	migrate_generated_content
};

class RedirectTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function test_redirect() {
		global $_pb_redirect_location;
		$_pb_redirect_location = null;

		$var = "https://press��books.co�m";
		\Pressbooks\Redirect\location( $var );
		$this->assertEquals( 'https://pressbooks.com', $_pb_redirect_location );
	}

	public function test_flusher() {
		delete_option( 'pressbooks_flusher' );
		flusher();
		$this->assertTrue( absint( get_option( 'pressbooks_flusher', 1 ) ) > 1 );
	}

	public function test_migrate_generated_content() {
		$this->_book();
		migrate_generated_content();
		$this->assertTrue( true ); // Did not crash
	}

	public function test_trim_value() {
		// Trim by reference
		$val = '   test    ';
		\Pressbooks\Redirect\trim_value( $val );
		$this->assertEquals( 'test', $val );
	}

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

	public function test_programmatic_login() {
		$this->assertFalse( \Pressbooks\Redirect\programmatic_login( 'nobody' ) );

		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		$user = get_userdata( $user_id );
		$this->assertTrue( \Pressbooks\Redirect\programmatic_login( $user->user_login ) );
		$logged_in = wp_get_current_user();
		$this->assertEquals( $logged_in->user_login, $user->user_login );
	}

}