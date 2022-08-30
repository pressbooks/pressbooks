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
	public function set_up() {
		parent::set_up();

		$obj = BookDataCollector::init();

		remove_action( 'wp_update_site', [ $obj, 'updateSite' ], 999 );
		remove_action( 'wp_insert_post', [ $obj, 'updateMetaData' ] );
		remove_action( 'wp_delete_site', [ $obj, 'deleteSite' ], 999 );

		$this->bookDataCollector = $obj;
	}

	public static function tear_down_after_class() {
		// Put the hooks back in place
		$obj = BookDataCollector::init();

		$obj::hooks( $obj );

		$_SERVER['SERVER_PORT'] = '';
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
	public function test_check_book_meta_is_copied(): void {
		$this->_book();

		$contributors = [
			'pb_authors' => [
				[ 'name' => 'John Doe', 'slug' => 'john' ],
				[ 'name' => 'Antonio Sanchez', 'slug' => 'antonio' ],
			],
			'pb_editors' => [
				[ 'name' =>  'Pat Metheny', 'slug' => 'pat' ],
				[ 'name' =>  'Pedro Aznar', 'slug' => 'pedro' ],
			],
		];

		$site = get_site();
		$metadata_post = ( new \Pressbooks\Metadata() )->getMetaPost();

		foreach ( $contributors as $contributor_type => $contributors_array ) {
			foreach ( $contributors_array as $contributor ) {
				add_post_meta( $metadata_post->ID, $contributor_type, $contributor['name'] );
				( new \Pressbooks\Contributors() )->insert( $contributor, $metadata_post->ID, $contributor_type );
			}
		}

		add_post_meta( $metadata_post->ID, 'pb_institutions', 'CA-ON-001' );
		add_post_meta( $metadata_post->ID, 'pb_institutions', 'CA-ON-002' );
		add_post_meta( $metadata_post->ID, 'pb_primary_subject', 'ABA' );
		add_post_meta( $metadata_post->ID, 'pb_additional_subjects', 'AVP, AVR, AVRQ' );
		add_post_meta( $metadata_post->ID, 'pb_institutions', 'CA-ON-002' );
		add_post_meta( $metadata_post->ID, 'pb_publisher', 'Publisher Name' );

		wp_cache_flush();

		$this->bookDataCollector->copyBookMetaIntoSiteTable( $site->id );

		$data_collected = [
			'pb_authors' => get_site_meta( $site->id, BookDataCollector::AUTHORS ),
			'pb_editors' => get_site_meta( $site->id, BookDataCollector::EDITORS ),
		];

		foreach ( $contributors as $contributor_type => $contributors_array ) {
			foreach ( $contributors_array as $contributor ) {
				$this->assertContains( $contributor['name'], $data_collected[ $contributor_type ] );
			}
		}

		$this->assertContains( 'Algoma University', get_site_meta( $site->id, BookDataCollector::INSTITUTIONS ) );
		$this->assertContains( 'Algonquin College', get_site_meta( $site->id, BookDataCollector::INSTITUTIONS ) );
		$this->assertContains( 'Musicians, singers, bands and groups', get_site_meta( $site->id, BookDataCollector::ADDITIONAL_SUBJECTS ) );
		$this->assertContains( 'Musical instruments', get_site_meta( $site->id, BookDataCollector::ADDITIONAL_SUBJECTS ) );
		$this->assertContains( 'Mechanical musical instruments', get_site_meta( $site->id, BookDataCollector::ADDITIONAL_SUBJECTS ) );
		$this->assertEquals( 'Publisher Name', get_site_meta( $site->id, BookDataCollector::PUBLISHER, true ) );
		$this->assertEquals( 'Theory of art', get_site_meta( $site->id, BookDataCollector::SUBJECT, true ) );
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
	 * @group datacollector
	 */
	public function test_get_LogicExeption() {
		$this->expectException(\LogicException::class);
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
		add_action( 'wp_update_site', [ $this->bookDataCollector, 'updateSite' ], 999, 2 );

		$this->_book();
		$x = $this->bookDataCollector->getTotalBooks();

		$this->assertIsInt( $x );
		$this->assertEquals( 1, $x );

		remove_action( 'wp_update_site', [ $this->bookDataCollector, 'updateSite' ], 999 );
	}

	/**
	 * @group datacollector
	 */
	public function test_getCoverThumbnail() {
		$this->_book();

		global $blog_id;

		$path = $this->bookDataCollector->getCoverThumbnail( $blog_id, 'https://presssbooks.test/cover-image.jpg' );
		$this->assertEquals( 'https://presssbooks.test/cover-image.jpg', $path );

		$path = $this->bookDataCollector->getCoverThumbnail( $blog_id, 'http://presssbooks.test/server-whitout-ssl-image.jpg' );
		$this->assertEquals( 'http://presssbooks.test/server-whitout-ssl-image.jpg', $path );

		$_SERVER['SERVER_PORT'] = '443';

		$path = $this->bookDataCollector->getCoverThumbnail( $blog_id, 'http://presssbooks.test/https-cover-image.jpg' );
		$this->assertEquals( 'https://presssbooks.test/https-cover-image.jpg', $path );

		$attachment_id = $this->factory->attachment->create_upload_object( __DIR__ . '/data/skates.jpg', $blog_id );
		$attachment_path = wp_get_attachment_url( $attachment_id );

		$path = $this->bookDataCollector->getCoverThumbnail( $blog_id, $attachment_path, $attachment_id );

		$this->assertEquals( 1, preg_match( '/https:\/\/.*-350x467\.jpg/', $path ) );
	}
}
