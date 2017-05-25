<?php

require_once( PB_PLUGIN_DIR . 'inc/class-book.php' );

class BookTest extends \WP_UnitTestCase {

	protected $book_structure;
	protected $page;

	/**
	 * Setup tests
	 */
	public function setUp() {
		$new_post = array(
			'post_title' => 'test chapter',
			'post_type' => 'chapter',
			'post_status' => 'publish',
			'post_content' => 'some content',
		);
		$pid = wp_insert_post( $new_post );
		update_post_meta( $pid, 'pb_export', 'on' );

		$this->book_structure = \Pressbooks\Book::getBookStructure();
		$this->page = $this->book_structure['__orphans'][0]; // In __orphans because doesn't belong to a part
	}

	/**
	 * @covers \Pressbooks\Book::getBookStructure
	 */
	public function test_returnsExportMetaValue() {
		$this->assertTrue( $this->page['export'] );
	}

	/**
	 * @covers \Pressbooks\Book::getBookStructure
	 */
	public function test_returnsCachedExportValue() {
		delete_post_meta( $this->page['ID'], 'pb_export' );
		$book_structure = \Pressbooks\Book::getBookStructure();
		$this->page = $book_structure['__orphans'][0];

		$this->assertTrue( $this->page['export'] );
	}

	/**
	 * @covers \Pressbooks\Book::getBookStructure
	 */
	public function test_returnsLatestExportValueNoCache() {
		delete_post_meta( $this->page['ID'], 'pb_export' );
		wp_cache_flush();
		$book_structure = \Pressbooks\Book::getBookStructure();

		$this->assertFalse( $book_structure['__orphans'][0]['export'] );
	}

}
