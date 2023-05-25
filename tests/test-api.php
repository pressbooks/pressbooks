<?php

use Pressbooks\Admin\Network\SharingAndPrivacyOptions;
use Pressbooks\Api\Endpoints\Controller\Posts;
use Pressbooks\Container;

use Pressbooks\DataCollector\Book;
use function \Pressbooks\Metadata\book_information_to_schema;

class ApiTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @group api
	 */
	public function test_rootEndpoints() {
		$server = $this->_setupRootApi();

		// Test that endpoints exist
		$endpoints = [
			'/pressbooks/v2/books',
		];
		foreach ( $endpoints as $endpoint ) {
			$request = new \WP_REST_Request( 'OPTIONS', $endpoint );
			$response = $server->dispatch( $request );
			$data = $response->get_data();
			$this->assertEquals( 'pressbooks/v2', $data['namespace'] );
		}
	}

	/**
	 * @group api
	 */
	public function test_booksEndpointMetadata() {
		$this->_book();
		$server = $this->_setupRootApi();
		$endpoint = '/pressbooks/v2/books';
		$request = new \WP_REST_Request( 'GET', $endpoint );
		$response = $server->dispatch( $request );
		$data = $response->get_data()[0];

		$this->assertArrayHasKey( 'metadata', $data );
		$this->assertArrayHasKey( 'wordCount', $data['metadata'] );
		$this->assertArrayHasKey( 'storageSize', $data['metadata'] );
		$this->assertArrayHasKey( 'h5pActivities', $data['metadata'] );
		$this->assertArrayHasKey( 'inCatalog', $data['metadata'] );
		$this->assertArrayHasKey( 'bookDirectoryExcluded', $data['metadata'] );
		$this->assertArrayHasKey( 'license', $data['metadata'] );
		$this->assertArrayHasKey( 'code', $data['metadata']['license'] );

		$this->assertIsInt( $data['metadata']['wordCount'] );
		$this->assertIsInt( $data['metadata']['storageSize'] );
		$this->assertIsInt( $data['metadata']['h5pActivities'] );
		$this->assertIsBool( $data['metadata']['inCatalog'] );
		$this->assertIsString( $data['metadata']['license']['code'] );
	}

	/**
	 * @test
	 * @group api
	 */
	public function it_filters_books_by_title(): void {
		$data = $this->setupBookEndpoint( [
			'title' => [
				'test',
				'stuff',
			],
		]);
		$this->assertEquals( 2, count( $data ) );

		foreach ( $data as $book ) {
			$this->assertMatchesRegularExpression( '/test|stuff/i', strtolower( $book['metadata']['name'] ) );
		}
	}

	/**
	 * @test
	 * @group api
	 */
	public function it_excludes_books_by_title(): void {
		$data = $this->setupBookEndpoint( [
			'title' => [
				'-book'
			],
		] );
		$this->assertEquals( 1, count( $data ) );

		$this->assertStringNotContainsString( 'book', strtolower( $data[0]['metadata']['name'] ) );
	}

	/**
	 * @test
	 * @group api
	 */
	public function it_filters_books_by_word_count_gte(): void {
		$data = $this->setupBookEndpoint( [
			'words' => 'gte_1000',
		] );
		$this->assertEquals( 3, count( $data ) );

		foreach ( $data as $book ) {
			$this->assertGreaterThanOrEqual( 1000, (int) $book['metadata']['wordCount'] );
		}
	}

	/**
	 * @test
	 * @group api
	 */
	public function it_filters_books_by_word_count_lte(): void {
		$data = $this->setupBookEndpoint( [
			'words' => 'lte_2000',
		] );

		$this->assertEquals( 2, count( $data ) );

		foreach ( $data as $book ) {
			$this->assertLessThanOrEqual( 2000, (int) $book['metadata']['wordCount'] );
		}
	}

	/**
	 * @test
	 * @group api
	 */
	public function it_filters_books_by_license_codes(): void {
		$data = $this->setupBookEndpoint( [
			'license_code' => [
				'CC BY',
				'Public Domain',
			],
		] );

		$this->assertEquals( 3, count( $data ) );

		foreach ( $data as $book ) {
			$this->assertMatchesRegularExpression( '/CC BY|Public Domain/i', $book['metadata']['license']['code'] );
		}
	}

	/**
	 * @test
	 * @group api
	 */
	public function it_filters_book_by_directory_included(): void {
		$data = $this->setupBookEndpoint( [
			'in_directory' => true,
		] );

		$this->assertEquals( 2, count( $data ) );

		foreach ( $data as $book ) {
			$this->assertFalse( $book['metadata']['bookDirectoryExcluded'] );
		}
	}

	/**
	 * @test
	 * @group api
	 */
	public function it_filters_book_by_directory_excluded(): void {
		$data = $this->setupBookEndpoint( [
			'in_directory' => false,
		] );

		$this->assertEquals( 2, count( $data ) );

		foreach ( $data as $book ) {
			$this->assertTrue( $book['metadata']['bookDirectoryExcluded'] );
		}
	}

	/**
	 * @test
	 * @group api
	 */
	public function it_filters_book_by_directory_include_catalog_network_setting(): void {
		$data = $this->setupBookEndpoint( [
			'in_directory' => true,
		], true );



		$this->assertEquals( 3, count( $data ) );

		foreach ( $data as $book ) {
			$this->assertFalse( $book['metadata']['bookDirectoryExcluded'] );
		}
	}

	/**
	 * @test
	 * @group api
	 */
	public function it_filters_book_by_directory_catalog_network_setting(): void {
		$data = $this->setupBookEndpoint( [
			'in_directory' => false,
		], true );


		$this->assertEquals( 1, count( $data ) );
		$this->assertTrue( $data[0]['metadata']['bookDirectoryExcluded'] );
	}

	/**
	 * Create a set of books with metadata and setup API endpoint for books.
	 *
	 * @param array $params
	 * @param bool $exclude_directory_catalog
	 * @return array
	 */
	private function setupBookEndpoint( array $params, bool $exclude_directory_catalog = false ): array {
		$licenses_map = [
			'Public Domain' => 'public-domain',
			'All Rights Reserved' => 'all-rights-reserved',
			'CC BY' => 'cc-by',
		];

		$network_options = get_site_option( SharingAndPrivacyOptions::getSlug() );
		$network_options[ SharingAndPrivacyOptions::NETWORK_DIRECTORY_EXCLUDED ] = $exclude_directory_catalog;
		update_site_option( SharingAndPrivacyOptions::getSlug(), $network_options );

		$metadata = [
			[
				Book::TITLE => 'Test PB Book',
				Book::WORD_COUNT => 1000,
				Book::BOOK_DIRECTORY_EXCLUDED => 1,
				Book::LICENSE_CODE => 'Public Domain',
				Book::IN_CATALOG => 1,
			],
			[
				Book::TITLE => 'Awesome textbook',
				Book::WORD_COUNT => 2340,
				Book::LICENSE_CODE => 'Public Domain',
				Book::IN_CATALOG => 1,
			],
			[
				Book::TITLE => 'Book about stuff',
				Book::WORD_COUNT => 50,
				Book::LICENSE_CODE => 'CC BY',
				Book::IN_CATALOG => 0,
			],
			[
				Book::TITLE => 'This is about things',
				Book::WORD_COUNT => 7477,
				Book::BOOK_DIRECTORY_EXCLUDED => 1,
				Book::LICENSE_CODE => 'All Rights Reserved',
				Book::IN_CATALOG => 1,
			],
		];
		$book_collector = new Book();

		foreach ( $metadata as $meta ) {
			$this->_book();
			$book_id = get_current_blog_id();
			foreach ( $meta as $key => $value ) {
				$metadata_info_array = $book_collector->get( $book_id, Book::BOOK_INFORMATION_ARRAY );

				if ( $key === Book::LICENSE_CODE ) {
					$metadata_info_array['pb_book_license'] = $licenses_map[ $value ];
				} else {
					$metadata_info_array[ $key ] = $value;
				}

				update_site_meta( $book_id, Book::BOOK_INFORMATION_ARRAY, $metadata_info_array );

				update_site_meta( $book_id, $key, $value );
			}
		}

		$server = $this->_setupRootApi();

		$endpoint = '/pressbooks/v2/books';
		$request = new \WP_REST_Request( 'GET', $endpoint );
		$request->set_query_params( $params );
		$response = $server->dispatch( $request );
		return $response->get_data();
	}

	/**
	 * @group api
	 * @test
	 */
	public function clone_complete_endpoint_invalid_token(): void {
		$server = $this->_setupRootApi();
		$endpoint = '/pressbooks/v2/clone/complete';
		$request = new \WP_REST_Request( 'POST', $endpoint );
		$request->set_body_params( [
			'token' => 'invalid_token',
			'url' => 'https://example.com',
			'name' => 'Test',
		] );
		$response = $server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_invalid_param', $data['code'] );
		$this->assertEquals( 'Invalid parameter(s): token', $data['message'] );
	}

	/**
	 * @group api
	 * @test
	 */
	public function clone_complete_endpoint_valid_token(): void {
		$tokens = new \Pressbooks\CloneTokens();
		$token = $tokens->generateToken();

		$server = $this->_setupRootApi();
		$endpoint = '/pressbooks/v2/clone/complete';
		$request = new \WP_REST_Request( 'POST', $endpoint );
		$request->set_body_params( [
			'token' => $token,
			'url' => 'https://example.com',
			'name' => 'Test',
		] );
		$response = $server->dispatch( $request );
		$this->assertEquals( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertTrue( $data['success'] );
	}

	/**
	 * @group api
	 * @test
	 */
	public function clone_complete_endpoint_valid_token_with_invalid_url(): void {
		$tokens = new \Pressbooks\CloneTokens();
		$token = $tokens->generateToken();

		$server = $this->_setupRootApi();
		$endpoint = '/pressbooks/v2/clone/complete';
		$request = new \WP_REST_Request( 'POST', $endpoint );
		$request->set_body_params( [
			'token' => $token,
			'url' => 'invalid_url',
			'name' => 'Test',
		] );
		$response = $server->dispatch( $request );
		$this->assertEquals( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertEquals( 'rest_invalid_param', $data['code'] );
		$this->assertEquals( 'Invalid parameter(s): url', $data['message'] );
	}

	/**
	 * @group api
	 */
	public function test_booksEndpointStylesResponse() {
		$this->_book();
		$server = $this->_setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/styles' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'epub', $data );
		$this->assertArrayHasKey( 'prince', $data );
		$this->assertArrayHasKey( 'web', $data );
		$this->assertIsString( $data['epub'] );
		$this->assertIsString( $data['prince'] );
		$this->assertIsString( $data['web'] );
	}

	/**
	 * @group api
	 */
	public function test_BookEndpointStyles() {
		$this->_book();
		$styles_container = Container::get( 'Styles' );
		$styles_container->registerPosts();
		$styles_container->initPosts();
		foreach ( [ 'web', 'epub', 'prince' ] as $slug ) {
			$post = $styles_container->getPost( $slug );
			$post_params = [
				'ID' => $post->ID,
				'post_content' => ".$slug-class { margin: auto; }",
			];
			wp_update_post( $post_params, true );
		}
		$server = $this->_setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/styles' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( '.epub-class { margin: auto; }', $data['epub'] );
		$this->assertEquals( '.web-class { margin: auto; }', $data['web'] );
		$this->assertEquals( '.prince-class { margin: auto; }', $data['prince'] );
	}

	/**
	 * @group api
	 */
	public function test_booksEndpointThemeResponse() {
		$this->_book();
		$options_classes = [
			'\Pressbooks\Modules\ThemeOptions\GlobalOptions',
			'\Pressbooks\Modules\ThemeOptions\WebOptions',
			'\Pressbooks\Modules\ThemeOptions\PDFOptions',
			'\Pressbooks\Modules\ThemeOptions\EbookOptions',
		];
		$slugs = [];
		foreach ( $options_classes as $option_class ) {
			$slug = call_user_func( $option_class . '::getSlug' );
			$slugs[] = $slug;
			add_option(
				'pressbooks_theme_options_' . $slug,
				call_user_func( $option_class . '::getDefaults' )
			);
		}

		$server = $this->_setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/theme' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'options', $data );
		$this->assertArrayHasKey( 'name', $data );
		$this->assertArrayHasKey( 'version', $data );
		$this->assertArrayHasKey( 'stylesheet', $data );
		foreach ( $slugs as $slug ) {
			$this->assertArrayHasKey( $slug, $data['options'] );
			$this->assertIsArray( $data['options'][ $slug ] );
		}
	}

	/**
	 * @group api
	 */
	public function test_booksEndpointTheme() {
		$this->_book();
		$pdf_settings = \Pressbooks\Modules\ThemeOptions\PDFOptions::getDefaults();
		$pdf_settings['pdf_footnote_font_size'] = '12';
		add_option( 'pressbooks_theme_options_pdf', $pdf_settings );
		$global_settings = \Pressbooks\Modules\ThemeOptions\GlobalOptions::getDefaults();
		$global_settings['chapter_label'] = 'Section';
		add_option( 'pressbooks_theme_options_global', $global_settings );
		$ebook_settings = \Pressbooks\Modules\ThemeOptions\EbookOptions::getDefaults();
		$ebook_settings['ebook_body_font'] = '11';
		add_option( 'pressbooks_theme_options_ebook', $ebook_settings );
		$web_settings = \Pressbooks\Modules\ThemeOptions\WebOptions::getDefaults();
		$web_settings['webbook_width'] = '45em';
		add_option( 'pressbooks_theme_options_web', $web_settings );

		$server = $this->_setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/theme' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals($web_settings['webbook_width'], $data['options']['web']['webbook_width'] );
		$this->assertEquals($global_settings['chapter_label'], $data['options']['global']['chapter_label'] );
		$this->assertEquals($pdf_settings['pdf_footnote_font_size'], $data['options']['pdf']['pdf_footnote_font_size'] );
		$this->assertEquals($ebook_settings['ebook_body_font'], $data['options']['ebook']['ebook_body_font'] );
	}

	/**
	 * @group api
	 */
	public function test_informationToSchema() {
		$book_information = [
			"pb_authors" => [
				[
					'name' => 'admin',
					'contributor_first_name' => 'Pat',
					'contributor_last_name' => 'Metheny',
					'contributor_description' => 'The drummer is the leader of any band',
				],
			],
            "pb_title" => "The onboarding process",
			"pb_language" => "en",
			"pb_cover_image" => "https://pressbooks.test/app/plugins/pressbooks/assets/dist/images/default-book-cover.jpg",
			"pb_thumbnail" => "https://pressbooks.test/app/plugins/pressbooks/assets/dist/images/default-book-cover.jpg",
            "pb_primary_subject" => "YXHB",
			"pb_additional_subjects" => "ATL, ABK",
			"pb_subtitle" => "subtitle test",
			"pb_word_count" => "4840",
            "pb_storage_size" => "39570177",
            "pb_in_catalog" => 1,
            "pb_h5p_activities" => 6,
            "pb_book_url" => "https://pressbooks.test/theonboarding",
            "pb_book_directory_excluded" => 1,
			"last_updated" => 1584921600,
		];
		$schema = book_information_to_schema( $book_information );

		$this->assertArrayHasKey( 'about', $schema );
		$this->assertArrayHasKey( 'language', $schema );
		$this->assertArrayHasKey( 'wordCount', $schema );
		$this->assertArrayHasKey( 'h5pActivities', $schema );
		$this->assertArrayHasKey( 'thumbnailUrl', $schema );
		$this->assertArrayHasKey( 'author', $schema );
	}

	/**
	 * @group api
	 */
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

	/**
	 * @group api
	 */
	public function test_partsEndpoint() {
		$server = $this->_setupBookApi();

		new Posts('part');

		$visible_part = [
			'post_type'    => 'part',
			'post_title'   => 'Visible',
			'post_content' => 'This space intentionally left blank.',
			'post_status'  => 'publish',
		];
		$invisible_part = [
			'post_type'    => 'part',
			'post_title'   => 'Invisible',
			'post_content' => 'This space intentionally left blank.',
			'post_status'  => 'publish',
		];

		$visible_id = $this->factory()->post->create_object( $visible_part );
		delete_post_meta( $visible_id, 'pb_part_invisible' );

		$invisible_id = $this->factory()->post->create_object( $invisible_part );
		update_post_meta( $invisible_id, 'pb_part_invisible', 'on' );

		$request = new \WP_REST_Request( 'GET', "/pressbooks/v2/parts/{$visible_id}" );
		$response = $server->dispatch( $request );
		$data = $response->get_data();

		$this->assertFalse($data['meta']['pb_part_invisible']);
		$this->assertEquals('', $data['meta']['pb_part_invisible_string']);

		$request = new \WP_REST_Request( 'GET', "/pressbooks/v2/parts/{$invisible_id}" );
		$response = $server->dispatch( $request );
		$data = $response->get_data();

		$this->assertNull($data['meta']['pb_part_invisible']);
		$this->assertEquals('on', $data['meta']['pb_part_invisible_string']);
	}

	/**
	 * @group api
	 */
	public function test_book_api_filter_modified_since() {
		$epochNow = strtotime( 'today' );
		$epochFuture = strtotime( '+1 week' );

		$this->_book();
		$server = $this->_setupRootApi();
		$endpoint = '/pressbooks/v2/books';
		$request = new \WP_REST_Request( 'GET', $endpoint );

		$request->set_query_params( [ 'modified_since' => $epochNow ] );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertCount( 1, $data );

		$request->set_query_params( [ 'modified_since' => $epochFuture ] );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEmpty( $data );

		$this->_book();
		$request->set_query_params( [ 'modified_since' => '' ] );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertCount( 2, $data );
	}

	/**
	 * @group api
	 */
	public function test_is_enabled() {
		$result = \Pressbooks\Api\is_enabled();
		$this->assertTrue( is_bool( $result ) );
	}

	/**
	 * @see \Pressbooks\Api\init_batch for documentation
	 * @group api
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

	/**
	 * Test /pressbooks/v2/glossary
	 * @group api
	 */
	public function test_glossaryApi() {
		$server = $this->_setupBookApi();

		new Posts('glossary');

		$term1 = [
			'post_type'    => 'glossary',
			'post_title'   => 'Synapse',
			'post_content' => 'Definition',
			'post_status'  => 'publish',
		];
		$term2 = [
			'post_type'    => 'glossary',
			'post_title'   => 'Not done',
			'post_content' => 'This term is not done so the status is private.',
			'post_status'  => 'private',
		];
		$term3 = [
			'post_type'    => 'glossary',
			'post_title'   => 'ML',
			'post_content' => 'Machine learning is a method of data analysis that automates analytical model building',
			'post_status'  => 'publish',
		];

		$term4 = [
			'post_type'    => 'glossary',
			'post_title'   => 'Moved to trash',
			'post_content' => 'This term was moved to trash.',
			'post_status'  => 'trash',
		];

		$this->factory()->post->create_object( $term1 );
		$this->factory()->post->create_object( $term2 );
		$this->factory()->post->create_object( $term3 );
		$this->factory()->post->create_object( $term4 );

		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/glossary' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( 3, count( $data ) );
		$this->assertEquals( 'Private: Not done', $data[0]['title']['rendered'] );
		$this->assertEquals( 'Not done', $data[0]['title']['raw'] );
		$this->assertEquals( 'Synapse', $data[1]['title']['rendered'] );
	}

	/**
	 * @test
	 * @group api
	 */
	public function set_api_permissions_item(): void {
		$this->_book();
		new Posts('front-matter');
		$post1 = [
			'post_type'    => 'front-matter',
			'post_title'   => 'Front matter title I',
			'post_content' => 'This is a front matter content I',
			'post_status'  => 'publish',
		];
		$post2 = [
			'post_type'    => 'front-matter',
			'post_title'   => 'Front matter title II',
			'post_content' => 'This is a front matter content II',
			'post_status'  => 'private',
		];
		$this->factory()->post->create_object( $post1 );
		$this->factory()->post->create_object( $post2 );

		update_option( 'blog_public', 0 );

		$server = $this->_setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/toc' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'code', $data );
		$this->assertArrayHasKey( 'data', $data );
		$this->assertEquals( $data['code'], 'rest_forbidden' );
		$this->assertEquals( $data['data']['status'], 401 );

		add_filter( 'pb_set_api_items_permission', '__return_true' );

		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/toc' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( 3, count( $data['front-matter'] ) );
		$this->assertEquals( $post1['post_title'], $data['front-matter'][0]['title'] );
		$this->assertEquals( $post2['post_title'], $data['front-matter'][1]['title'] );
	}

	/**
	 * @test
	 * @group api
	 */
	public function clone_token_is_valid(): void {
		$this->_book();
		new Posts('front-matter');
		$post1 = [
			'post_type'    => 'front-matter',
			'post_title'   => 'Front matter title I',
			'post_content' => 'This is a front matter content I',
			'post_status'  => 'publish',
		];
		$post2 = [
			'post_type'    => 'front-matter',
			'post_title'   => 'Front matter title II',
			'post_content' => 'This is a front matter content II',
			'post_status'  => 'private',
		];
		$this->factory()->post->create_object( $post1 );
		$this->factory()->post->create_object( $post2 );

		$server = $this->_setupRootApi();
		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/toc' );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertIsString( $data['clone_token'] );
	}

	/**
	 * @test
	 * @group api
	 */
	public function get_password_protected_posts(): void {
		$this->_book();
		new Posts('back-matter');
		$protected_post = [
			'post_type'    => 'back-matter',
			'post_title'   => 'Back matter title I',
			'post_content' => 'This is a back matter content I',
			'post_status'  => 'publish',
			'post_password' => '123456',
		];
		$protected_post_id = $this->factory()->post->create_object( $protected_post );
		update_option( 'blog_public', 0 );

		$server = $this->_setupRootApi();

		$request = new \WP_REST_Request( 'GET', '/pressbooks/v2/back-matter/' . $protected_post_id );
		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEmpty( $data['content']['raw'] );

		add_filter( 'pb_set_api_items_permission', '__return_true' );

		$response = $server->dispatch( $request );
		$data = $response->get_data();
		$this->assertEquals( $protected_post['post_content'], $data['content']['raw'] );
	}

	/**
	 * @test
	 * @group api
	 */
	public function it_disable_users_endpoint(): void {
		$server = $this->_setupRootApi();

		$request = new WP_REST_Request( 'GET', '/wp/v2/users' );

		$response = $server->dispatch( $request );
		$data = $response->data;

		$this->assertEquals( 404, $response->status );
		$this->assertEquals( 'No route was found matching the URL and request method.', $data['message'] );
	}
}
