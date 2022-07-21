<?php

use Pressbooks\BookDirectory;

/**
 * @group bookDirectory
 */
class BookDirectoryTest extends \WP_UnitTestCase {
	/**
	 * @var BookDirectory
	 */
	protected $book_directory;

	/**
	 * Test setup
	 */
	public function set_up() {
		parent::set_up();

		$this->book_directory = new BookDirectory();
	}

	public function test_getInstance() {
		$bookDirectory = $this->book_directory->init();
		$this->assertInstanceOf( '\Pressbooks\BookDirectory', $bookDirectory );
	}

	public function test_hooks() {
		global $wp_filter;
		$result = $this->book_directory->init();
		$this->book_directory->hooks( $result );
		$this->assertNotEmpty( $wp_filter );
	}

	public function test_deleteAction() {
		$this->assertFalse( $this->book_directory->deleteAction( get_site() ) );
	}

}
