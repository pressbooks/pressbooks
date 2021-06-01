<?php

use Pressbooks\DataCollector\Book as BookDataCollector;

class DataCollector_BookTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var BookDataCollector
	 */
	protected $bookDataCollector;


	/**
	 * @group datacollector
	 */
	public function setUp() {
		parent::setUp();
		$obj = BookDataCollector::init();
		remove_action( 'wp_update_site', [ $obj, 'updateSite' ], 999 );
		remove_action( 'wp_insert_post', [ $obj, 'updateMetaData' ] );
		remove_action( 'wp_delete_site', [ $obj, 'deleteSite' ], 999 );
		$this->bookDataCollector = $obj;
	}


	public static function tearDownAfterClass() {
		// Put the hooks back in place
		$obj = BookDataCollector::init();
		$obj::hooks( $obj );
	}


	/**
	 * @group datacollector
	 */
	public function test_updateSite() {
		$now = gmdate( 'Y-m-d H:i:s' );
		$this->_book();
		$new_site = $old_site = get_site();

		// Check that there is no timestamp to start
		$updated = get_site_meta( $new_site->id, BookDataCollector::TIMESTAMP, true );
		$this->assertEmpty( $updated );

		// Check that the book was updated and left a timestamp
		$this->bookDataCollector->updateSite( $new_site, $old_site );
		$updated = get_site_meta( $new_site->id, BookDataCollector::TIMESTAMP, true );
		$this->assertTrue( date_create( $updated ) >= date_create( $now ) );
	}

	/**
	 * @group datacollector
	 */
	public function test_updateMetaData() {
		$now = gmdate( 'Y-m-d H:i:s' );
		$this->_book();
		$new_site = $old_site = get_site();

		// Check that there is no timestamp to start
		$updated = get_site_meta( $new_site->id, BookDataCollector::TIMESTAMP, true );
		$this->assertEmpty( $updated );

		// Check that the book was updated and left a timestamp
		$mp = ( new \Pressbooks\Metadata() )->getMetaPost();
		$this->bookDataCollector->updateMetaData( $mp->ID, $mp, true );
		$updated = get_site_meta( $new_site->id, BookDataCollector::TIMESTAMP, true );
		$this->assertTrue( date_create( $updated ) >= date_create( $now ) );
	}

	/**
	 * @group datacollector
	 */
	public function test_deleteSite() {
		$this->_book();
		$old_site = get_site();

		// Insert a timestamp
		update_site_meta( $old_site->id, BookDataCollector::TIMESTAMP, gmdate( 'Y-m-d H:i:s' ) );

		// Check that all info (the timestamp) was deleted
		$this->bookDataCollector->deleteSite( $old_site );
		$updated = get_site_meta( $old_site->id, BookDataCollector::TIMESTAMP, true );
		$this->assertEmpty( $updated );
	}

	/**
	 * @group datacollector
	 */
	public function test_copyBookMetaIntoSiteTable() {
		$now = gmdate( 'Y-m-d H:i:s' );
		$this->_book();
		$site = get_site();

		// Check that there is no timestamp to start
		$updated = get_site_meta( $site->id, BookDataCollector::TIMESTAMP, true );
		$this->assertEmpty( $updated );

		// Check that the book was updated and left a timestamp
		$this->bookDataCollector->copyBookMetaIntoSiteTable( $site->id );
		$updated = get_site_meta( $site->id, BookDataCollector::TIMESTAMP, true );
		$this->assertTrue( date_create( $updated ) >= date_create( $now ) );
	}

	/**
	 * @group datacollector
	 */
	public function test_themaSubjectsLocale() {
		$this->assertEquals( 'en', $this->bookDataCollector->themaSubjectsLocale( 'fr' ) );
	}

	/**
	 * @group datacollector
	 */
	public function test_copyAllBooksIntoSiteTable() {
		$this->_book();
		$i = 0;
		foreach ( $this->bookDataCollector->copyAllBooksIntoSiteTable() as $_ ) {
			$i++;
		}
		$this->assertTrue( $i > 0 );
	}

	/**
	 * @group datacollector
	 */
	public function test_get() {
		$this->_book();
		$book_id = get_current_blog_id();
		delete_site_meta( $book_id, BookDataCollector::BOOK_INFORMATION_ARRAY );
		$x = $this->bookDataCollector->get( $book_id, BookDataCollector::BOOK_INFORMATION_ARRAY );
		$this->assertNotEmpty( $x );
		$this->assertIsArray( $x );

		$y = $this->bookDataCollector->get( get_current_blog_id(), 'does_not_exist' );
		$this->assertEmpty( $y );
	}

	/**
	 * @expectedException \LogicException
	 * @group datacollector
	 */
	public function test_get_LogicExeption() {
		$this->_book();
		$book_id = get_current_blog_id();
		update_site_meta( $book_id, BookDataCollector::BOOK_INFORMATION_ARRAY, new \StdClass() ); // No hackers allowed
		$x = $this->bookDataCollector->get( $book_id, BookDataCollector::BOOK_INFORMATION_ARRAY );
	}


	/**
	 * @group datacollector
	 */
	public function test_getPossibleValuesFor() {
		update_site_meta( 1, BookDataCollector::THEME, 'McLuhan' );
		update_site_meta( 2, BookDataCollector::THEME, 'Clarke' );
		update_site_meta( 3, BookDataCollector::THEME, 'Luther' );
		update_site_meta( 4, BookDataCollector::THEME, 'King, Of, The, Hill' );

		$x = $this->bookDataCollector->getPossibleValuesFor( BookDataCollector::THEME );

		$this->assertContains( 'McLuhan', $x );
		$this->assertContains( 'Clarke', $x );
		$this->assertContains( 'Luther', $x );
		$this->assertContains( 'King, Of, The, Hill', $x );
	}

	/**
	 * @group datacollector
	 */
	public function test_getPossibleCommaDelimitedValuesFor() {
		update_site_meta( 1, BookDataCollector::THEME, 'McLuhan' );
		update_site_meta( 2, BookDataCollector::THEME, 'Clarke' );
		update_site_meta( 3, BookDataCollector::THEME, 'Luther' );
		update_site_meta( 4, BookDataCollector::THEME, 'King, Of, The, Hill' );

		$x = $this->bookDataCollector->getPossibleCommaDelimitedValuesFor( BookDataCollector::THEME );

		$this->assertContains( 'McLuhan', $x );
		$this->assertContains( 'Clarke', $x );
		$this->assertContains( 'Luther', $x );
		$this->assertContains( 'King', $x );
		$this->assertContains( 'Of', $x );
		$this->assertContains( 'The', $x );
		$this->assertContains( 'Hill', $x );
	}

	/**
	 * @group datacollector
	 */
	public function test_getTotalNetworkStorageBytes() {
		$x = $this->bookDataCollector->getTotalNetworkStorageBytes();
		$this->assertIsInt( $x );
		$this->assertTrue( $x > 0 );
	}


	/**
	 * @group datacollector
	 */
	public function test_getTotalBooks() {
		$this->_book();
		$x = $this->bookDataCollector->getTotalBooks();
		$this->assertIsInt( $x );
		$this->assertTrue( $x > 0 );
	}


	/**
	 * @group datacollector
	 */
	public function test_getCoverThumbnail() {
		$this->_book();
		$path = $this->bookDataCollector->getCoverThumbnail( 1, 'https://presssbooks.test/cover-image.jpg' );
		$this->assertEquals( 'https://presssbooks.test/cover-image.jpg', $path );

		$path = $this->bookDataCollector->getCoverThumbnail( 1, 'http://presssbooks.test/no-https-cover-image.jpg' );
		$this->assertEquals( 'https://presssbooks.test/no-https-cover-image.jpg', $path );

		$image_name = 'themetamorphosis_1200x1600.jpg';

		$good_url = 'https://metamorphosiskafka.pressbooks.com/wp-content/uploads/sites/26642/2014/04/' . $image_name;
		update_post_meta( 1, 'pb_cover_image', $good_url );

		$args = [
			'post_mime_type' => 'jpg',
			'post_title' => __( 'Cover Image', 'pressbooks' ),
			'post_content' => '',
			'post_status' => 'inherit',
			'post_name' => 'pb-cover-image',
		];

		$id = wp_insert_attachment( $args, $image_name, 1 );

		wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $image_name ) );

		$path = $this->bookDataCollector->getCoverThumbnail( 1, $good_url );
		$this->assertEquals( 'https://presssbooks.test/no-https-cover-image.jpg', $path );
	}


}
