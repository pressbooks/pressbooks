<?php

use function \Pressbooks\Redirect\{
	flusher,
	migrate_generated_content
};

class RedirectTest extends \WP_UnitTestCase {

	use utilsTrait;

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

	public function test_programmatic_login() {
		$this->assertFalse( \Pressbooks\Redirect\programmatic_login( 'nobody' ) );

		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		$user = get_userdata( $user_id );
		$this->assertTrue( \Pressbooks\Redirect\programmatic_login( $user->user_login ) );
		$logged_in = wp_get_current_user();
		$this->assertEquals( $logged_in->user_login, $user->user_login );
	}

}