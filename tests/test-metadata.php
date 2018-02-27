<?php

class MetadataTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Metadata
	 */
	protected $metadata;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->metadata = new \Pressbooks\Metadata();
	}

	/**
	 * @see \Pressbooks\Metadata::jsonSerialize
	 */
	public function test_Metadata_JsonSerialize() {
		$result = json_encode( $this->metadata );
		$this->assertJson( $result );
		$this->assertContains( '{"@context":"http:\/\/schema.org","@type":"Book","name":"Test Blog",', $result );

	}

	public function test_get_microdata_elements() {

		$result = \Pressbooks\Metadata\get_microdata_elements();
		$this->assertContains( '<meta', $result );
	}

	public function test_get_seo_meta_elements() {

		$result = \Pressbooks\Metadata\get_seo_meta_elements();
		$this->assertContains( '<meta', $result );
	}

	public function test_show_expanded_metadata() {
		$result = \Pressbooks\Metadata\show_expanded_metadata();
		$this->assertFalse( $result );
		update_option( 'pressbooks_show_expanded_metadata', 1 );
		$result = \Pressbooks\Metadata\show_expanded_metadata();
		$this->assertTrue( $result );
	}

	public function test_has_expanded_metadata() {
		$book = \Pressbooks\Book::getInstance();

		$this->_book();
		$meta_post = $this->metadata->getMetaPost();

		$result = \Pressbooks\Metadata\has_expanded_metadata();
		$this->assertFalse( $result );

		\Pressbooks\Book::deleteBookObjectCache();

		update_post_meta( $meta_post->ID, 'pb_audience', 'Zimmerman, Ned' );

		$result = \Pressbooks\Metadata\has_expanded_metadata();
		$this->assertTrue( $result );
	}

	public function test_book_information_to_schema() {
		$book_information = [
			'pb_authors' => 'Herman Melville',
			'pb_title' => 'Moby Dick',
		];

		$result = \Pressbooks\Metadata\book_information_to_schema( $book_information );
		$this->assertEquals( $result['name'], 'Moby Dick' );
		$this->assertEquals( $result['author'][0]['name'], 'Herman Melville' );
	}

	public function test_schema_to_book_information() {
		$schema = [
			'@context' => 'http://schema.org',
			'@type' => 'Book',
			'name' => 'Moby Dick',
			'license' => 'https://creativecommons.org/publicdomain/zero/1.0/',
			'author' => [ // PB4
				'@type' => 'Person',
				'name' => 'Herman Melville',
			],
		];

		$result = \Pressbooks\Metadata\schema_to_book_information( $schema );
		$this->assertEquals( $result['pb_title'], 'Moby Dick' );
		$this->assertEquals( $result['pb_authors'], 'Herman Melville' );
		$this->assertEquals( $result['pb_book_license'], 'public-domain' );

		$schema = [
			'@context' => 'http://schema.org',
			'@type' => 'Book',
			'name' => 'Moby Dick',
			'license' => [
				'url' => 'https://creativecommons.org/publicdomain/zero/1.0/',
				'name' => 'Public Domain (No Rights Reserved)',
				'description' => 'Override copyright.',
			],
			'author' => [
				[
					'@type' => 'Person',
					'name' => 'Herman Melville',
				],
			],
			'editor' => [
				[
					'@type' => 'Person',
					'name' => 'Test 1',
				],
			],
			'translator' => [
				[
					'@type' => 'Person',
					'name' => 'Test 2',
				],
			],
			'reviewedBy' => [
				[
					'@type' => 'Person',
					'name' => 'Test 3',
				],
			],
			'illustrator' => [
				[
					'@type' => 'Person',
					'name' => 'Test 4',
				],
			],
			'contributor' => [
				[
					'@type' => 'Person',
					'name' => 'Test 5',
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
		$this->assertEquals( $result['pb_authors'], 'Herman Melville' );
		$this->assertEquals( $result['pb_book_license'], 'public-domain' );
		$this->assertEquals( $result['pb_custom_copyright'], 'Override copyright.' );
		$this->assertEquals( $result['pb_editors'], 'Test 1' );
		$this->assertEquals( $result['pb_translators'], 'Test 2' );
		$this->assertEquals( $result['pb_reviewers'], 'Test 3' );
		$this->assertEquals( $result['pb_illustrators'], 'Test 4' );
		$this->assertEquals( $result['pb_contributors'], 'Test 5' );
		$this->assertEquals( $result['pb_audience'], 'adult' );
		$this->assertEquals( $result['pb_publication_date'], 1516838400 );
		$this->assertEquals( $result['pb_copyright_holder'], 'Test 6' );
	}

	public function test_section_information_to_schema() {
		$section_information = [
			'pb_title' => 'Loomings',
			'pb_chapter_number' => 1,
		];

		$book_information = [
			'pb_authors' => 'Herman Melville',
			'pb_title' => 'Moby Dick',
		];

		$result = \Pressbooks\Metadata\section_information_to_schema( $section_information, $book_information );
		$this->assertEquals( $result['name'], 'Loomings' );
		$this->assertEquals( $result['author'][0]['name'], 'Herman Melville' );
		$this->assertEquals( $result['position'], 1 );
	}

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
			'@context' => 'http://bib.schema.org',
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
		];

		$result = \Pressbooks\Metadata\schema_to_section_information( $section_schema, $book_schema );
		$this->assertArrayNotHasKey( 'pb_authors', $result );
		$this->assertArrayNotHasKey( 'pb_section_license', $result );

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

	public function test_get_thema_subjects() {
		$result = \Pressbooks\Metadata\get_thema_subjects();
		$this->assertArrayHasKey( 'Y', $result );
		$this->assertArrayNotHasKey( '1', $result );
		$result = \Pressbooks\Metadata\get_thema_subjects( true );
		$this->assertArrayHasKey( 'Y', $result );
		$this->assertArrayHasKey( '1', $result );
	}

	public function test_get_subject_from_thema() {
		$result = \Pressbooks\Metadata\get_subject_from_thema( '1KBC-CA-JM' );
		$this->assertEquals( 'Nova Scotia: South Shore & Kejimkujik National Park', $result );
	}

	public function test_is_bisac() {
		$result = \Pressbooks\Metadata\is_bisac( 'AB' );
		$this->assertFalse( $result );
		$result = \Pressbooks\Metadata\is_bisac( 'ANT123456' );
		$this->assertTrue( $result );
	}

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

	public function test_upgradeToPressbooksFive() {
		$this->_book();
		update_option( 'pressbooks_taxonomy_version', \Pressbooks\Taxonomy::VERSION + 999 );
		$this->metadata->upgradeToPressbooksFive();
		$this->assertEquals( \Pressbooks\Taxonomy::VERSION, get_option( 'pressbooks_taxonomy_version' ) );
	}

}
