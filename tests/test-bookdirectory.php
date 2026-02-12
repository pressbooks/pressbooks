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
		// Mock the HTTP request to return an error
		add_filter( 'pre_http_request', function() {
			return new \WP_Error( 'http_error', 'Connection failed' );
		}, 10 );

		$result = $this->book_directory->deleteAction( get_site() );
		remove_all_filters( 'pre_http_request' );

		$this->assertFalse( $result );
	}

	public function test_deleteBookFromDirectory_invalidUrl() {
		// Set invalid endpoint URL
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'not-a-valid-url' );

		$result = $this->book_directory->deleteBookFromDirectory( [ 1 ] );
		$this->assertFalse( $result );
	}

	public function test_deleteBookFromDirectory_usesCurrentBlogIdWhenNoIdsProvided() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		// Mock wp_remote_post
		add_filter( 'pre_http_request', function( $response, $args, $url ) {
			$body = json_decode( $args['body'], true );
			$this->assertIsArray( $body['book_ids'] );
			$this->assertCount( 1, $body['book_ids'] );
			$this->assertEquals( get_current_blog_id(), $body['book_ids'][0] );
			
			return [
				'response' => [ 'code' => 200 ],
				'body' => '{"success": true}',
			];
		}, 10, 3 );

		$result = $this->book_directory->deleteBookFromDirectory();
		remove_all_filters( 'pre_http_request' );
		
		$this->assertTrue( $result );
	}

	public function test_deleteBookFromDirectory_successfulDeletion() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		add_filter( 'pre_http_request', function( $response, $args, $url ) {
			return [
				'response' => [ 'code' => 200 ],
				'body' => '{"success": true}',
			];
		}, 10, 3 );

		$result = $this->book_directory->deleteBookFromDirectory( [ 123 ] );
		remove_all_filters( 'pre_http_request' );

		$this->assertTrue( $result );
	}

	public function test_deleteBookFromDirectory_wpError() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		// Get current removals to verify rollback
		$removals_before = get_site_option( BookDirectory::DELETIONS_META_KEY, [] );

		add_filter( 'pre_http_request', function() {
			return new \WP_Error( 'http_error', 'Connection timeout' );
		}, 10 );

		$result = $this->book_directory->deleteBookFromDirectory( [ 123 ] );
		remove_all_filters( 'pre_http_request' );

		$this->assertFalse( $result );
		
		// Verify removals were rolled back
		$removals_after = get_site_option( BookDirectory::DELETIONS_META_KEY, [] );
		$this->assertEquals( $removals_before, $removals_after );
	}

	public function test_deleteBookFromDirectory_nonSuccessStatusCode() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		$removals_before = get_site_option( BookDirectory::DELETIONS_META_KEY, [] );

		add_filter( 'pre_http_request', function( $response, $args, $url ) {
			return [
				'response' => [ 'code' => 500 ],
				'body' => '{"message": "Internal server error"}',
			];
		}, 10, 3 );

		$result = $this->book_directory->deleteBookFromDirectory( [ 123 ] );
		remove_all_filters( 'pre_http_request' );

		$this->assertFalse( $result );
		
		// Verify removals were rolled back
		$removals_after = get_site_option( BookDirectory::DELETIONS_META_KEY, [] );
		$this->assertEquals( $removals_before, $removals_after );
	}

	public function test_deleteBookFromDirectory_validationError() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		add_filter( 'pre_http_request', function( $response, $args, $url ) {
			return [
				'response' => [ 'code' => 422 ],
				'body' => '{"message": "Validation failed", "errors": {"book_ids": ["Invalid book IDs"]}}',
			];
		}, 10, 3 );

		$result = $this->book_directory->deleteBookFromDirectory( [ 123 ] );
		remove_all_filters( 'pre_http_request' );

		$this->assertFalse( $result );
	}

	public function test_deleteBookFromDirectory_storesRemovalMetadata() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		// Clear existing removals
		delete_site_option( BookDirectory::DELETIONS_META_KEY );

		add_filter( 'pre_http_request', function( $response, $args, $url ) {
			return [
				'response' => [ 'code' => 200 ],
				'body' => '{"success": true}',
			];
		}, 10, 3 );

		$this->book_directory->deleteBookFromDirectory( [ 123 ] );
		remove_all_filters( 'pre_http_request' );

		$removals = get_site_option( BookDirectory::DELETIONS_META_KEY, [] );
		$this->assertIsArray( $removals );
		$this->assertCount( 1, $removals );
		$this->assertStringStartsWith( BookDirectory::DELETION_PREFIX, $removals[0] );
	}

	public function test_deleteBookFromDirectory_sendsCorrectPayload() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		$captured_data = null;

		add_filter( 'pre_http_request', function( $response, $args, $url ) use ( &$captured_data ) {
			$this->assertEquals( 'https://api.example.com/delete', $url );
			$this->assertEquals( 'application/json', $args['headers']['Content-Type'] );
			$this->assertEquals( 15, $args['timeout'] );
			
			$captured_data = json_decode( $args['body'], true );
			
			return [
				'response' => [ 'code' => 200 ],
				'body' => '{"success": true}',
			];
		}, 10, 3 );

		$this->book_directory->deleteBookFromDirectory( [ 123, 456 ] );
		remove_all_filters( 'pre_http_request' );

		$this->assertIsArray( $captured_data );
		$this->assertArrayHasKey( 'sid', $captured_data );
		$this->assertArrayHasKey( 'network', $captured_data );
		$this->assertArrayHasKey( 'book_ids', $captured_data );
		$this->assertEquals( [ 123, 456 ], $captured_data['book_ids'] );
		$this->assertStringStartsWith( BookDirectory::DELETION_PREFIX, $captured_data['sid'] );
	}

	public function test_setBookPrivate_callsDeleteWhenPrivate() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		add_filter( 'pre_http_request', function( $response, $args, $url ) {
			return [
				'response' => [ 'code' => 200 ],
				'body' => '{"success": true}',
			];
		}, 10, 3 );

		$result = $this->book_directory->setBookPrivate( 1, 0 );
		remove_all_filters( 'pre_http_request' );

		$this->assertTrue( $result );
	}

	public function test_setBookPrivate_doesNothingWhenPublic() {
		$result = $this->book_directory->setBookPrivate( 0, 1 );
		$this->assertNull( $result );
	}

	public function test_softDeleteActions_archived() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		add_filter( 'pre_http_request', function() {
			return [
				'response' => [ 'code' => 200 ],
				'body' => '{"success": true}',
			];
		}, 10 );

		$previous = get_site();
		$previous->archived = '0';
		
		$updated = clone $previous;
		$updated->archived = '1';

		$result = $this->book_directory->softDeleteActions( $updated, $previous );
		remove_all_filters( 'pre_http_request' );

		$this->assertTrue( $result );
	}

	public function test_softDeleteActions_deactivated() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		add_filter( 'pre_http_request', function() {
			return [
				'response' => [ 'code' => 200 ],
				'body' => '{"success": true}',
			];
		}, 10 );

		$previous = get_site();
		$previous->deleted = '0';
		
		$updated = clone $previous;
		$updated->deleted = '1';

		$result = $this->book_directory->softDeleteActions( $updated, $previous );
		remove_all_filters( 'pre_http_request' );

		$this->assertTrue( $result );
	}

	public function test_softDeleteActions_spam() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		add_filter( 'pre_http_request', function() {
			return [
				'response' => [ 'code' => 200 ],
				'body' => '{"success": true}',
			];
		}, 10 );

		$previous = get_site();
		$previous->spam = '0';
		
		$updated = clone $previous;
		$updated->spam = '1';

		$result = $this->book_directory->softDeleteActions( $updated, $previous );
		remove_all_filters( 'pre_http_request' );

		$this->assertTrue( $result );
	}

	public function test_softDeleteActions_urlChanged() {
		$reflection = new \ReflectionClass( BookDirectory::class );
		$property = $reflection->getProperty( 'delete_book_endpoint' );
		$property->setAccessible( true );
		$property->setValue( null, 'https://api.example.com/delete' );

		add_filter( 'pre_http_request', function() {
			return [
				'response' => [ 'code' => 200 ],
				'body' => '{"success": true}',
			];
		}, 10 );

		$previous = get_site();
		$previous->path = '/old-path/';
		
		$updated = clone $previous;
		$updated->path = '/new-path/';

		$result = $this->book_directory->softDeleteActions( $updated, $previous );
		remove_all_filters( 'pre_http_request' );

		$this->assertTrue( $result );
	}

	public function test_softDeleteActions_noChanges() {
		$previous = get_site();
		$updated = clone $previous;

		$result = $this->book_directory->softDeleteActions( $updated, $previous );
		$this->assertNull( $result );
	}
}
