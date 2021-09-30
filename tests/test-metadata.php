<?php

class MetadataTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Metadata
	 * @group metadata
	 */
	protected $metadata;

	/**
	 * @var \Pressbooks\Taxonomy
	 * @group metadata
	 */
	protected $taxonomy;

	/**
	 * @var \Pressbooks\Contributors
	 * @group metadata
	 */
	protected $contributor;

	/**
	 * @group metadata
	 */
	public function setUp() {
		parent::setUp();
		$this->metadata = new \Pressbooks\Metadata();
		$this->contributor = new \Pressbooks\Contributors();
		$this->taxonomy = new \Pressbooks\Taxonomy(
			$this->getMockBuilder( '\Pressbooks\Licensing' )->getMock(),
			$this->contributor
		);
	}

	/**
	 * @see \Pressbooks\Metadata::jsonSerialize
	 * @group metadata
	 */
	public function test_Metadata_JsonSerialize() {
		$result = json_encode( $this->metadata );
		$this->assertJson( $result );
		$this->assertContains( '{"@context":"http:\/\/schema.org","@type":"Book","name":"Test Blog",', $result );

	}

	/**
	 * @group metadata
	 */
	public function test_get_microdata_elements() {

		$result = \Pressbooks\Metadata\get_microdata_elements();
		$this->assertContains( '<meta', $result );
	}

	/**
	 * @group metadata
	 */
	public function test_get_seo_meta_elements() {

		$result = \Pressbooks\Metadata\get_seo_meta_elements();
		$this->assertContains( '<meta', $result );
	}

	/**
	 * @group metadata
	 */
	public function test_show_expanded_metadata() {
		$result = \Pressbooks\Metadata\show_expanded_metadata();
		$this->assertFalse( $result );
		update_option( 'pressbooks_show_expanded_metadata', 1 );
		$result = \Pressbooks\Metadata\show_expanded_metadata();
		$this->assertTrue( $result );
	}

	/**
	 * @group metadata
	 */
	public function test_has_expanded_metadata() {
		$meta_post_id = $this->metadata->getMetaPostId();
		$this->assertEquals( 0, $meta_post_id );

		$this->_book();

		$meta_post_id = $this->metadata->getMetaPostId();
		$this->assertNotEmpty( $meta_post_id );
		$this->assertTrue( $meta_post_id > 0 );

		$meta_post = $this->metadata->getMetaPost();

		$result = \Pressbooks\Metadata\has_expanded_metadata();
		$this->assertFalse( $result );

		\Pressbooks\Book::deleteBookObjectCache();

		update_post_meta( $meta_post->ID, 'pb_audience', 'Zimmerman, Ned' );

		$result = \Pressbooks\Metadata\has_expanded_metadata();
		$this->assertTrue( $result );
	}

	/**
	 * @group metadata
	 */
	public function test_book_information_to_schema() {
		$book_information = [
			'pb_authors' => 'Herman Melville',
			'pb_title' => 'Moby Dick',
			'pb_book_doi' => 'my_doi',
		];

		$result = \Pressbooks\Metadata\book_information_to_schema( $book_information );
		$this->assertEquals( $result['name'], 'Moby Dick' );
		$this->assertEquals( $result['author'][0]['name'], 'Herman Melville' );
		$this->assertEquals( $result['sameAs'], 'https://dx.doi.org/my_doi' );
		$this->assertEquals( $result['identifier']['value'], 'my_doi' );
	}

	/**
	 * @group metadata
	 */
	public function test_schema_to_book_information() {
		$schema = [
			'@context' => 'http://schema.org',
			'@type' => 'Book',
			'name' => 'Moby Dick',
			'license' => 'https://creativecommons.org/publicdomain/mark/1.0/',
			'author' => [
				[
					'@type' => 'Person',
					'name' => 'Pat Metheny',
				],
			],
			'sameAs' => 'https://dx.doi.org/my_doi',
		];

		$result = \Pressbooks\Metadata\schema_to_book_information( $schema );
		$this->assertEquals( $result['pb_title'], 'Moby Dick' );
		$this->assertEquals( $result['pb_authors'][0]['name'], 'Pat Metheny' );
		$this->assertEquals( $result['pb_book_license'], 'public-domain' );
		$this->assertEquals( $result['pb_book_doi'], 'my_doi' );

		$schema = [
			'@context' => 'http://schema.org',
			'@type' => 'Book',
			'name' => 'Moby Dick',
			'license' => [
				'url' => 'https://creativecommons.org/publicdomain/mark/1.0/',
				'name' => 'Public Domain',
				'description' => 'Override copyright.',
			],
			'author' => [
				[
					'@type' => 'Person',
					'name' => 'Pat Metheny',
					'contributor_prefix' => "Mr",
					'contributor_first_name' => "Patrick",
					'contributor_last_name' => "Metheny",
					'contributor_suffix' => "Musician",
					'contributor_picture' => "https://pressbooks.test/app/uploads/sites/5/2021/09/cropped-IMG_5226-3-scaled-1.jpg",
					'contributor_description' => "Hi <strong>I am a contributor</strong>!",
					'contributor_institution' => "PMG",
					'contributor_user_url' => "https://patmetheny.com",
					'contributor_linkedin' => "https://linkedin.com/pm",
				],
			],
			'editor' => [
				[
					'@type' => 'Person',
					'name' => 'Pat Metheny',
					'contributor_prefix' => "Mr",
					'contributor_first_name' => "Patrick",
					'contributor_last_name' => "Metheny",
					'contributor_suffix' => "Musician",
					'contributor_picture' => "https://pressbooks.test/app/uploads/sites/5/2021/09/cropped-IMG_5226-3-scaled-1.jpg",
					'contributor_description' => "Hi <strong>I am a contributor</strong>!",
					'contributor_institution' => "PMG",
					'contributor_user_url' => "https://patmetheny.com",
					'contributor_linkedin' => "https://linkedin.com/pm",
				],
				[
					'@type' => 'Person',
					'name' => 'Pedro Aznar',
					'contributor_prefix' => "Mr",
					'contributor_first_name' => "Pedro",
					'contributor_last_name' => "Aznar",
					'contributor_suffix' => "Musician",
					'contributor_picture' => "https://pressbooks.test/app/uploads/sites/5/2021/09/cropped-IMG_5226-3-scaled-2.jpg",
					'contributor_description' => "Hi <strong>I am a contributor</strong>!",
					'contributor_institution' => "PMG",
					'contributor_user_url' => "https://pedroaznar.com.com",
					'contributor_linkedin' => "https://linkedin.com/pa",
				],
			],
			'translator' => [
				[
					'@type' => 'Person',
					'name' => 'Pedro Aznar',
					'contributor_prefix' => "Mr",
					'contributor_first_name' => "Pedro",
					'contributor_last_name' => "Aznar",
					'contributor_suffix' => "Musician",
					'contributor_picture' => "https://pressbooks.test/app/uploads/sites/5/2021/09/cropped-IMG_5226-3-scaled-2.jpg",
					'contributor_description' => "Hi <strong>I am a contributor</strong>!",
					'contributor_institution' => "PMG",
					'contributor_user_url' => "https://pedroaznar.com.com",
					'contributor_linkedin' => "https://linkedin.com/pa",
				],
			],
			'reviewedBy' => [
				[
					'@type' => 'Person',
					'name' => 'Pedro Aznar',
					'contributor_prefix' => "Mr",
					'contributor_first_name' => "Pedro",
					'contributor_last_name' => "Aznar",
					'contributor_suffix' => "Musician",
					'contributor_picture' => "https://pressbooks.test/app/uploads/sites/5/2021/09/cropped-IMG_5226-3-scaled-2.jpg",
					'contributor_description' => "Hi <strong>I am a contributor</strong>!",
					'contributor_institution' => "PMG",
					'contributor_user_url' => "https://pedroaznar.com.com",
					'contributor_linkedin' => "https://linkedin.com/pa",
				],
			],
			'illustrator' => [
				[
					'@type' => 'Person',
					'name' => 'Pat Metheny',
					'contributor_prefix' => "Mr",
					'contributor_first_name' => "Patrick",
					'contributor_last_name' => "Metheny",
					'contributor_suffix' => "Musician",
					'contributor_picture' => "https://pressbooks.test/app/uploads/sites/5/2021/09/cropped-IMG_5226-3-scaled-1.jpg",
					'contributor_description' => "Hi <strong>I am a contributor</strong>!",
					'contributor_institution' => "PMG",
					'contributor_user_url' => "https://patmetheny.com",
					'contributor_linkedin' => "https://linkedin.com/pm",
				],
				[
					'@type' => 'Person',
					'name' => 'Pedro Aznar',
					'contributor_prefix' => "Mr",
					'contributor_first_name' => "Pedro",
					'contributor_last_name' => "Aznar",
					'contributor_suffix' => "Musician",
					'contributor_picture' => "https://pressbooks.test/app/uploads/sites/5/2021/09/cropped-IMG_5226-3-scaled-2.jpg",
					'contributor_description' => "Hi <strong>I am a contributor</strong>!",
					'contributor_institution' => "PMG",
					'contributor_user_url' => "https://pedroaznar.com.com",
					'contributor_linkedin' => "https://linkedin.com/pa",
				],
			],
			'contributor' => [
				[
					'@type' => 'Person',
					'name' => 'Pat Metheny',
					'contributor_prefix' => "Mr",
					'contributor_first_name' => "Patrick",
					'contributor_last_name' => "Metheny",
					'contributor_suffix' => "Musician",
					'contributor_picture' => "https://pressbooks.test/app/uploads/sites/5/2021/09/cropped-IMG_5226-3-scaled-1.jpg",
					'contributor_description' => "Hi <strong>I am a contributor</strong>!",
					'contributor_institution' => "PMG",
					'contributor_user_url' => "https://patmetheny.com",
					'contributor_linkedin' => "https://linkedin.com/pm",
				],
				[
					'@type' => 'Person',
					'name' => 'Pedro Aznar',
					'contributor_prefix' => "Mr",
					'contributor_first_name' => "Pedro",
					'contributor_last_name' => "Aznar",
					'contributor_suffix' => "Musician",
					'contributor_picture' => "https://pressbooks.test/app/uploads/sites/5/2021/09/cropped-IMG_5226-3-scaled-2.jpg",
					'contributor_description' => "Hi <strong>I am a contributor</strong>!",
					'contributor_institution' => "PMG",
					'contributor_user_url' => "https://pedroaznar.com.com",
					'contributor_linkedin' => "https://linkedin.com/pa",
				],
			],
			'audience' => [
				'@type' => 'Audience',
				'name' => 'adult',
			],
			'datePublished' => '2018-01-25',
			'copyrightHolder' => [
				'@type' => 'Organization',
				'name' => 'Test 6',
			],
		];

		$result = \Pressbooks\Metadata\schema_to_book_information( $schema );
		$this->assertEquals( $result['pb_book_license'], 'public-domain' );
		$this->assertEquals( $result['pb_custom_copyright'], 'Override copyright.' );
		$this->assertIsArray( $result['pb_authors'] );
		$this->assertIsArray( $result['pb_editors'] );
		$this->assertIsArray( $result['pb_translators'] );
		$this->assertIsArray( $result['pb_reviewers'] );
		$this->assertIsArray( $result['pb_illustrators'] );
		$this->assertIsArray( $result['pb_contributors'] );
		$this->assertArrayHasKey( 'contributor_first_name', $result['pb_authors'][0] );
		$this->assertArrayHasKey( 'contributor_last_name', $result['pb_editors'][0] );
		$this->assertArrayHasKey( 'contributor_picture', $result['pb_translators'][0] );
		$this->assertArrayHasKey( 'contributor_linkedin', $result['pb_reviewers'][0] );
		$this->assertArrayHasKey( 'contributor_user_url', $result['pb_illustrators'][0] );
		$this->assertArrayHasKey( 'contributor_institution', $result['pb_contributors'][0] );
		$this->assertEquals( $result['pb_audience'], 'adult' );
		$this->assertEquals( $result['pb_publication_date'], 1516838400 );
		$this->assertEquals( $result['pb_copyright_holder'], 'Test 6' );
	}

	/**
	 * @group metadata
	 */
	public function test_section_information_to_schema() {
		$section_information = [
			'pb_title' => 'Loomings',
			'pb_chapter_number' => 1,
			'pb_section_doi' => 'my_doi',
		];

		$book_information = [
			'pb_authors' => [
				[
					'name' => 'Herman Melville',
				],
			],
			'pb_title' => 'Moby Dick',
		];

		$result = \Pressbooks\Metadata\section_information_to_schema( $section_information, $book_information );
		$this->assertEquals( $result['name'], 'Loomings' );
		$this->assertEquals( $result['author'][0]['name'], 'Herman Melville' );
		$this->assertEquals( $result['position'], 1 );
		$this->assertEquals( $result['identifier']['value'], 'my_doi' );
	}

	/**
	 * @group metadata
	 */
	public function test_schema_to_section_information() {
		$book_schema = [
			'@context' => 'http://schema.org',
			'@type' => 'Book',
			'author' => [
				'@type' => 'Person',
				'name' => 'Herman Melville',
			],
			'name' => 'Moby Dick',
			'license' => [
				'url' => 'https://creativecommons.org/publicdomain/zero/1.0/',
				'name' => 'Public Domain (No Rights Reserved)',
			],
		];

		$section_schema = [
			'@context' => 'http://schema.org',
			'@type' => 'Chapter',
			'author' => [
				'@type' => 'Person',
				'name' => 'Herman Melville',
			],
			'name' => 'Loomings',
			'license' => [
				'url' => 'https://creativecommons.org/publicdomain/zero/1.0/',
				'name' => 'Public Domain (No Rights Reserved)',
			],
			'sameAs' => 'https://dx.doi.org/my_doi',
		];

		$result = \Pressbooks\Metadata\schema_to_section_information( $section_schema, $book_schema );
		$this->assertArrayNotHasKey( 'pb_authors', $result );
		$this->assertArrayNotHasKey( 'pb_section_license', $result );
		$this->assertEquals( $result['pb_section_doi'], 'my_doi' );

		$book_schema = [
			'@context' => 'http://schema.org',
			'@type' => 'Book',
			'author' => [
				'@type' => 'Person',
				'name' => 'Herman Melville',
			],
			'name' => 'Moby Dick',
			'license' => 'https://creativecommons.org/publicdomain/zero/1.0/',
		];

		$section_schema = [
			'@context' => 'http://bib.schema.org',
			'@type' => 'Chapter',
			'author' => [
				'@type' => 'Person',
				'name' => 'Herman Melville',
			],
			'name' => 'Loomings',
			'license' => [
				'url' => 'https://choosealicense.com/no-license/',
				'name' => 'All Rights Reserved',
			],
		];

		$result = \Pressbooks\Metadata\schema_to_section_information( $section_schema, $book_schema );
		$this->assertArrayHasKey( 'pb_section_license', $result );
	}

	/**
	 * @group metadata
	 */
	public function test_get_thema_subjects() {
		$result = \Pressbooks\Metadata\get_thema_subjects();
		$this->assertArrayHasKey( 'Y', $result );
		$this->assertArrayNotHasKey( '1', $result );
		$result = \Pressbooks\Metadata\get_thema_subjects( true );
		$this->assertArrayHasKey( 'Y', $result );
		$this->assertArrayHasKey( '1', $result );
	}

	/**
	 * @group metadata
	 */
	public function test_get_subject_from_thema() {
		$result = \Pressbooks\Metadata\get_subject_from_thema( '1KBC-CA-JM' );
		$this->assertEquals( 'Nova Scotia: South Shore and Kejimkujik National Park', $result );
	}

	/**
	 * @group metadata
	 */
	public function test_is_bisac() {
		$result = \Pressbooks\Metadata\is_bisac( 'AB' );
		$this->assertFalse( $result );
		$result = \Pressbooks\Metadata\is_bisac( 'ANT123456' );
		$this->assertTrue( $result );
	}

	/**
	 * @group metadata
	 */
	public function test_postStatiiConversion() {
		$val = $this->metadata->postStatiiConversion( 'wrong', 'wrong' );
		$this->assertEquals( 'wrong', $val );

		$val = $this->metadata->postStatiiConversion( 'does-not-exist', true );
		$this->assertEquals( 'does-not-exist', $val );

		$val = $this->metadata->postStatiiConversion( 'draft', true );
		$this->assertEquals( 'private', $val );

		$val = $this->metadata->postStatiiConversion( 'publish', true );
		$this->assertEquals( 'publish', $val );

		$val = $this->metadata->postStatiiConversion( 'draft', false );
		$this->assertEquals( 'draft', $val );

		$val = $this->metadata->postStatiiConversion( 'publish', false );
		$this->assertEquals( 'web-only', $val );
	}

	/**
	 * @group metadata
	 */
	public function test_upgradeToPressbooksFive() {
		$interactive = \Pressbooks\Interactive\Content::init();
		$this->_book();
		update_option( 'pressbooks_taxonomy_version', \Pressbooks\Taxonomy::VERSION + 999 );
		$chapters = get_posts(
			[
				'post_type' => 'chapter',
				'posts_per_page' => 1,
			]
		);
		remove_filter( 'pre_kses', [ $interactive, 'deleteIframesNotOnWhitelist' ], 1 );
		add_filter( 'wp_kses_allowed_html', [ $this, '_allowIframes' ], 10, 2 ); // Allow iframes
		$pid = wp_update_post(
			[
				'ID' => $chapters[0]->ID,
				'post_content' => $chapters[0]->post_content . '<p>There should be an iframe here:<br /><iframe width="560" height="315" src="https://www.youtube.com/embed/JgIhGTpKTwM" frameborder="0"></iframe></p>',
			]
		);
		remove_filter( 'wp_kses_allowed_html', [ $this, '_allowIframes' ] ); // Disallow iframes
		add_filter( 'pre_kses', [ $interactive, 'deleteIframesNotOnWhitelist' ], 1, 2 );
		$this->metadata->upgradeToPressbooksFive();
		$this->assertEquals( \Pressbooks\Taxonomy::VERSION, get_option( 'pressbooks_taxonomy_version' ) );
		$content = get_post_field( 'post_content', $pid );
		$this->assertContains( '<iframe width="560" height="315" src="https://www.youtube.com/embed/JgIhGTpKTwM" frameborder="0"></iframe>', $content );
	}

	/**
	 * @group metadata
	 */
	public function test_get_section_information() {
		$this->_book();
		$chapters = get_posts(
			[
				'post_type' => 'chapter',
				'posts_per_page' => 1,
			]
		);
		$section_information = \Pressbooks\Metadata\get_section_information( $chapters[0]->ID );
		$this->assertInternalType( 'array', $section_information );
		$this->assertStringStartsWith( 'Test Chapter: ', $section_information['pb_title'] );
		$this->assertEquals( 'Or, A Chapter to Test', $section_information['pb_subtitle'] );
	}

	/**
	 * @group metadata
	 */
	public function test_add_json_ld_metadata() {
		$this->_book();
		ob_start();
		\Pressbooks\Metadata\add_json_ld_metadata();
		$buffer = ob_get_clean();
		$this->assertStringStartsWith( '<script type="application/ld+json">{"@context":"http:\/\/schema.org","@type":"Book"', $buffer );
	}

	/**
	 * @group metadata
	 */
	public function test_add_citation_metadata() {
		$this->_book();
		$this->taxonomy->registerTaxonomies();
		$author = 'Some Author';
		$results = $this->contributor->insert( $author );

		$meta_post = $this->metadata->getMetaPost();
		$time = time();
		update_post_meta( $meta_post->ID, 'pb_title', 'Some Book' );
		update_post_meta( $meta_post->ID, 'pb_book_doi', '10.1000/xyz123' );
		update_post_meta( $meta_post->ID, 'pb_ebook_isbn', '9781234567897' );
		update_post_meta( $meta_post->ID, 'pb_language', 'en-ca' );
		update_post_meta( $meta_post->ID, 'pb_publication_date', $time );
		update_post_meta( $meta_post->ID, 'pb_publisher', 'Book Oven Inc.' );
		add_post_meta( $meta_post->ID, 'pb_authors', 'some-author' );

		ob_start();
		\Pressbooks\Metadata\add_citation_metadata();
		$buffer = ob_get_clean();
		$this->assertStringStartsWith( '<meta name="og:type" content="book"', $buffer );
		$this->assertContains( '<meta name="citation_title" content="Some Book">', $buffer );
		$this->assertContains( '<meta name="citation_doi" content="10.1000/xyz123">', $buffer );
		$this->assertContains( '<meta name="citation_isbn" content="9781234567897">', $buffer );
		$this->assertContains( '<meta name="citation_language" content="en-ca">', $buffer );
		$this->assertContains( '<meta name="citation_year" content="' . strftime( '%Y', $time ) . '">', $buffer );
		$this->assertContains( '<meta name="citation_publication_date" content="' . strftime( '%F', $time ) . '">', $buffer );
		$this->assertContains( '<meta name="citation_publisher" content="Book Oven Inc.">', $buffer );
		$this->assertContains( '<meta name="citation_author" content="Some Author">', $buffer );

		$chapters = get_posts(
			[
				'post_type' => 'chapter',
				'posts_per_page' => 1,
			]
		);
		$this->go_to( get_permalink( $chapters[0]->ID ) );
		global $post;
		setup_postdata( $post );
		$section_title = $post->post_title;

		ob_start();
		\Pressbooks\Metadata\add_citation_metadata();
		$buffer = ob_get_clean();

		$this->assertNotContains( '<meta name="og:type" content="book"', $buffer );
		$this->assertContains( '<meta name="citation_book_title" content="Some Book">', $buffer );
		$this->assertContains( '<meta name="citation_title" content="' . $section_title . '">', $buffer );
		$this->assertContains( '<meta name="citation_language" content="en-ca">', $buffer );
		$this->assertContains( '<meta name="citation_year" content="' . strftime( '%Y', $time ) . '">', $buffer );
		$this->assertContains( '<meta name="citation_publication_date" content="' . strftime( '%F', $time ) . '">', $buffer );
		$this->assertContains( '<meta name="citation_publisher" content="Book Oven Inc.">', $buffer );
		$this->assertContains( '<meta name="citation_author" content="Some Author">', $buffer );
	}

	/**
	 * @group metadata
	 */
	function test_add_candela_citations() {
		deactivate_plugins( 'candela-citation/candela-citation.php' );
		$html = '<p>hello</p>';
		$this->assertEquals( $html, \Pressbooks\Metadata\add_candela_citations( $html ) );
	}

	/**
	 * @group metadata
	 */
	function test_get_in_catalog_option() {
		$option = \Pressbooks\Metadata\get_in_catalog_option();
		$this->assertTrue( ! empty( $option ) );
		$this->assertTrue( is_string( $option ) );
	}

	/**
	 * @group metadata
	 */
	function test_download_thema_subjects() {
		$result = \Pressbooks\Metadata\download_thema_lang( 1, 1, 'pb_language', 'en' );
		$this->assertFalse( $result );

		$es_book = WP_CONTENT_DIR . '/uploads/assets/thema/symbionts/es.json';

		@unlink( $es_book );
		$result = \Pressbooks\Metadata\download_thema_lang( 1, 1, 'pb_language', 'es' );
		$this->assertTrue( $result );
		$this->assertFileExists( $es_book );

		$fr_ca_book = WP_CONTENT_DIR . '/uploads/assets/thema/symbionts/fr-ca.json';

		@unlink( $fr_ca_book );
		$result = \Pressbooks\Metadata\download_thema_lang( 1, 1, 'pb_language', 'fr-ca' );
		$this->assertTrue( $result );
		$this->assertFileExists( $fr_ca_book );

		$result = \Pressbooks\Metadata\download_thema_lang( 1, 1, 'pb_language', 'es-mx' );
		$this->assertFalse( $result );

	}

	/**
	 * @group metadata
	 */
	function test_register_contributor_meta_enqueue_script() {
		global $current_screen;
		$current_screen = WP_Screen::get( 'term.php' );
		\Pressbooks\Metadata\register_contributor_meta();
		global $wp_scripts;
		do_action( 'admin_enqueue_scripts', 'term.php' );
		$this->assertContains( 'pb_contributors', $wp_scripts->queue );
	}

}

