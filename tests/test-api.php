<?php

class ApiTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @return Spy_REST_Server
	 */
	public function setupBookApi() {

		global $wp_rest_server;
		$server = $wp_rest_server = new \Spy_REST_Server();
		$this->_book();

		// PHPUnit is initialized as main site, $is_book hooks are never loaded...
		\Pressbooks\PostType\register_post_types();
		remove_action( 'rest_api_init', '\Pressbooks\Api\init_root' );
		add_action( 'rest_api_init', '\Pressbooks\Api\init_book' );
		add_filter( 'rest_endpoints', 'Pressbooks\Api\hide_endpoints_from_book' );
		add_filter( 'rest_url', 'Pressbooks\Api\fix_book_urls', 10, 2 );

		do_action( 'rest_api_init' );
		return $server;
	}

	/**
	 * @return Spy_REST_Server
	 */
	public function setupRootApi() {

		global $wp_rest_server;
		$server = $wp_rest_server = new \Spy_REST_Server;
		do_action( 'rest_api_init' );
		return $server;
	}

	public function test_rootEndpoints() {

		$server = $this->setupRootApi();

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
			'/pressbooks/v2/toc',
		];
		$server = $this->setupBookApi();
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
			'/pressbooks/v2/parts/999/metadata',
		];
		$server = $this->setupBookApi();
		foreach ( $incompatible_endpoints as $endpoint ) {
			$request = new \WP_REST_Request( 'GET', $endpoint );
			$response = $server->dispatch( $request );
			$status = $response->get_status();
			$this->assertEquals( $status, 404 );
		}
	}

	public function test_bookSearch() {

		$this->_book();
		update_option( 'blog_public', 1 );
		restore_current_blog();

		// Test book metadata search
		$server = $this->setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/books/search' );
		$request->set_param( 'name', 'site' );
		$request->set_param( '@type', 'book' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		// Test chapter metadata search
		$server = $this->setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/books/search' );
		$request->set_param( 'name', 'appendix' );
		$request->set_param( '@type', 'chapter' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		// Test search with 5 parameters
		$server = $this->setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/books/search' );
		$request->set_param( 'name', 'lorem,ipsum,dolor,consectetur,site' );
		$request->set_param( '@type', 'lorem,ipsum,dolor,consectetur,book' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertNotEmpty( $data );

		// Test truncated after 5 parameters
		$server = $this->setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/books/search' );
		$request->set_param( 'name', 'lorem,ipsum,dolor,consectetur,adipiscing,site' );
		$request->set_param( '@type', 'lorem,ipsum,dolor,consectetur,adipiscing,book' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEmpty( $data );

		// Test AND/OR
		$server = $this->setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/books/search' );
		$request->set_param( 'name', 'site' );
		$request->set_param( '@type', 'cake' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEmpty( $data );
	}

}
