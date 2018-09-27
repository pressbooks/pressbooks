<?php

class ApiTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function test_rootEndpoints() {

		$server = $this->_setupRootApi();

		// Test that endpoints exist
		$endpoints = [
			'/pressbooks/v2/books',
			'/pressbooks/v2/books/search',
		];
		foreach ( $endpoints as $endpoint ) {
			$request = new \WP_REST_Request( 'OPTIONS', $endpoint );
			$response = $server->dispatch( $request );
			$data = $response->get_data();
			$this->assertEquals( 'pressbooks/v2', $data['namespace'] );
		}
	}

	public function test_BookEndpoints() {

		// Test that endpoints exist
		$endpoints = [
			'/pressbooks/v2/parts',
			'/pressbooks/v2/front-matter',
			'/pressbooks/v2/front-matter-type',
			'/pressbooks/v2/front-matter/999/metadata',
			'/pressbooks/v2/front-matter/999/revisions',
			'/pressbooks/v2/chapters',
			'/pressbooks/v2/chapter-type',
			'/pressbooks/v2/chapters/999/metadata',
			'/pressbooks/v2/chapters/999/revisions',
			'/pressbooks/v2/back-matter',
			'/pressbooks/v2/back-matter-type',
			'/pressbooks/v2/back-matter/999/metadata',
			'/pressbooks/v2/back-matter/999/revisions',
			'/pressbooks/v2/metadata',
			'/pressbooks/v2/glossary',
			'/pressbooks/v2/glossary-type',
			'/pressbooks/v2/glossary/999/metadata',
			'/pressbooks/v2/glossary/999/revisions',
			'/pressbooks/v2/toc',
		];
		$server = $this->_setupBookApi();
		foreach ( $endpoints as $endpoint ) {
			$request = new \WP_REST_Request( 'OPTIONS', $endpoint );
			$response = $server->dispatch( $request );
			$data = $response->get_data();
			$this->assertEquals( 'pressbooks/v2', $data['namespace'] );
		}

		// Test that incompatible endpoints are removed
		$incompatible_endpoints = [
			'/wp/v2/posts',
			'/wp/v2/pages',
			'/wp/v2/tags',
			'/wp/v2/categories',
			'/wp/v2/front-matter-type',
			'/wp/v2/chapter-type',
			'/wp/v2/back-matter-type',
			'/wp/v2/glossary-type',
			'/pressbooks/v2/parts/999/metadata',
		];
		$server = $this->_setupBookApi();
		foreach ( $incompatible_endpoints as $endpoint ) {
			$request = new \WP_REST_Request( 'GET', $endpoint );
			$response = $server->dispatch( $request );
			$status = $response->get_status();
			$this->assertEquals( 404, $status );
		}
	}

	public function test_bookSearch() {

		$this->_book();
		update_option( 'blog_public', 1 );
		restore_current_blog();

		// Test book metadata search
		$server = $this->_setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/books/search' );
		$request->set_param( 'name', 'site' );
		$request->set_param( '@type', 'book' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		// Test chapter metadata search
		$server = $this->_setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/books/search' );
		$request->set_param( 'name', 'appendix' );
		$request->set_param( '@type', 'chapter' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		// Test search with 5 parameters
		$server = $this->_setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/books/search' );
		$request->set_param( 'name', 'lorem,ipsum,dolor,consectetur,site' );
		$request->set_param( '@type', 'lorem,ipsum,dolor,consectetur,book' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		// Test truncated after 5 parameters
		$server = $this->_setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/books/search' );
		$request->set_param( 'name', 'lorem,ipsum,dolor,consectetur,adipiscing,site' );
		$request->set_param( '@type', 'lorem,ipsum,dolor,consectetur,adipiscing,book' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEmpty( $data );

		// Test AND/OR
		$server = $this->_setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/books/search' );
		$request->set_param( 'name', 'site' );
		$request->set_param( '@type', 'cake' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEmpty( $data );
	}

	public function test_is_enabled() {
		$result = \Pressbooks\Api\is_enabled();
		$this->assertTrue( is_bool( $result ) );
	}

	/**
	 * @see \Pressbooks\Api\init_batch for documentation
	 */
	public function test_batch() {
		$server = $this->_setupBookApi();

		// Set admin with site wide permissions
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		update_site_option( 'site_admins', [ wp_get_current_user()->user_login ] );

		// Invalid request

		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/batch' );
		$response = $server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );

		// URL Format

		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/batch' );
		parse_str( 'requests[]=/pressbooks/v2/front-matter&requests[]=/pressbooks/v2/back-matter', $params );
		$request->set_query_params( $params );
		$response = $server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 2, count( $data ) );
		$this->assertInstanceOf( '\WP_REST_Response', $data[0] );
		$this->assertInstanceOf( '\WP_REST_Response', $data[1] );
		$this->assertEquals( 200, $data[0]->get_data()['status'] );
		$this->assertEquals( 200, $data[1]->get_data()['status'] );
		$this->assertEquals( 'Introduction', $data[0]->get_data()['body'][0]['title']['rendered'] );
		$this->assertEquals( 'Appendix', $data[1]->get_data()['body'][0]['title']['rendered'] );

		// JSON Object Format

		$this->assertFalse( get_user_by( 'slug', 'batchuser001' ) );
		$this->assertFalse( get_user_by( 'slug', 'batchuser002' ) );

		$post = '
			{
				"requests": [
					{
						"path": "/wp/v2/users",
						"headers": [],
						"body": {"username": "batchuser001", "email": "batchuser001@pressbooks.test", "password": "abcd1234"},
						"method": "POST"
					},
					{
						"path": "/wp/v2/users",
						"headers": [],
						"body": {"username": "batchuser002", "email": "batchuser002@pressbooks.test", "password": "abcd1234"},
						"method": "POST"
					}
				]
			}';

		$request = new \WP_REST_Request( 'POST', '/pressbooks/v2/batch' );
		$request->set_body_params( json_decode( $post, true ) );
		$response = $server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 2, count( $data ) );
		$this->assertInstanceOf( '\WP_REST_Response', $data[0] );
		$this->assertInstanceOf( '\WP_REST_Response', $data[1] );
		$this->assertEquals( 201, $data[0]->get_data()['status'] );
		$this->assertEquals( 201, $data[1]->get_data()['status'] );

		$this->assertInstanceOf( '\WP_User', get_user_by( 'slug', 'batchuser001' ) );
		$this->assertInstanceOf( '\WP_User', get_user_by( 'slug', 'batchuser002' ) );
	}

}
