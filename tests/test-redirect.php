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

}