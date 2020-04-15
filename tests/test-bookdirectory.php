<?php
class BookDirectoryTest extends \WP_UnitTestCase {
	/**
	 * @var BookDirectory
	 */
	protected $book_directory;

	/**
	 * Test setup
	 */
	public function setUp() {
		parent::setUp();

		if ( ! defined( 'PB_BOOK_DIRECTORY_URL' ) ) {
			define( 'PB_BOOK_DIRECTORY_URL', 'http://10.0.2.2:3000' );
		}

		$this->book_directory = new \Pressbooks\BookDirectory();
	}

	/**
	 * @group bookDirectory
	 */
	public function test_getInstance() {
		$bookDirectory = $this->book_directory->init();
		$this->assertInstanceOf( '\Pressbooks\BookDirectory', $bookDirectory );
	}

	/**
	 * @group bookDirectory
	 */
	public function test_hooks() {
		global $wp_filter;
		$result = $this->book_directory->init();
		$this->book_directory->hooks( $result );
		$this->assertNotEmpty( $wp_filter );
	}
}
