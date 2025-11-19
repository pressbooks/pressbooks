<?php
/**
 * Test MARC export functionality
 *
 * @group marc-export
 */

namespace Pressbooks\Modules\Export\Marc;

use Pressbooks\Book;

class MarcExportTest extends \WP_UnitTestCase {

	protected $metadata;
	protected $marc_export;

	public function setUp(): void {
		parent::setUp();

		$this->marc_export = new \Pressbooks\Metadata();
		
		// Create metadata post
		$meta_post = $this->factory()->post->create_and_get( [
			'post_title' => 'Book Information',
			'post_type' => 'metadata',
			'post_status' => 'publish',
		] );

		// Add sample metadata
		update_post_meta( $meta_post->ID, 'pb_title', 'Test Book Title' );
		update_post_meta( $meta_post->ID, 'pb_subtitle', 'A Test Subtitle' );
		update_post_meta( $meta_post->ID, 'pb_publisher', 'Test Publisher' );
		update_post_meta( $meta_post->ID, 'pb_publisher_city', 'Test City' );
		update_post_meta( $meta_post->ID, 'pb_publication_date', time() );
		update_post_meta( $meta_post->ID, 'pb_ebook_isbn', '9781234567890' );
		update_post_meta( $meta_post->ID, 'pb_print_isbn', '978-1-234-56789-0' );
		update_post_meta( $meta_post->ID, 'pb_language', 'en' );
		update_post_meta( $meta_post->ID, 'pb_about_50', 'This is a short description of the test book.' );
		update_post_meta( $meta_post->ID, 'pb_keywords_tags', 'testing, metadata, marc' );
		update_post_meta( $meta_post->ID, 'pb_bisac_subject', 'COM000000 COMPUTERS / General' );
		update_post_meta( $meta_post->ID, 'pb_series_title', 'Test Series' );
		update_post_meta( $meta_post->ID, 'pb_series_number', '1' );
		update_post_meta( $meta_post->ID, 'pb_book_doi', '10.1234/test.doi' );

		// Create test author
		$contributor = new \Pressbooks\Contributors();
		$contributor->insert( 'Test Author', 'pb_authors' );
		add_post_meta( $meta_post->ID, 'pb_authors', 'test-author' );
	}

	public function test_marc_record_creation() {
		$record = new MarcRecord();
		
		$this->assertNotEmpty( $record->getLeader() );
		$this->assertStringContainsString( '00000nam a2200000 i 4500', $record->getLeader() );
	}

	public function test_marc_record_control_fields() {
		$record = new MarcRecord();
		$record->addControlField( '001', '123' );
		$record->addControlField( '003', 'PB' );
		
		$fields = $record->getControlFields();
		
		$this->assertCount( 2, $fields );
		$this->assertEquals( '001', $fields[0]['tag'] );
		$this->assertEquals( '123', $fields[0]['data'] );
	}

	public function test_marc_record_data_fields() {
		$record = new MarcRecord();
		$record->addDataField( '245', '1', '0', [
			[ 'code' => 'a', 'data' => 'Test Title' ],
			[ 'code' => 'b', 'data' => 'Test Subtitle' ],
		] );
		
		$fields = $record->getDataFields();
		
		$this->assertCount( 1, $fields );
		$this->assertEquals( '245', $fields[0]['tag'] );
		$this->assertEquals( '1', $fields[0]['ind1'] );
		$this->assertEquals( '0', $fields[0]['ind2'] );
		$this->assertCount( 2, $fields[0]['subfields'] );
	}

	public function test_marc_xml_generator_creates_record() {
		$generator = new MarcXmlGenerator();
		$record = $generator->generateRecord();
		
		$this->assertInstanceOf( MarcRecord::class, $record );
		$this->assertNotEmpty( $record->getControlFields() );
		$this->assertNotEmpty( $record->getDataFields() );
	}

	public function test_marc_xml_generation() {
		$generator = new MarcXmlGenerator();
		$xml = $generator->generateXml();
		
		$this->assertNotEmpty( $xml );
		$this->assertStringContainsString( '<?xml version="1.0" encoding="UTF-8"?>', $xml );
		$this->assertStringContainsString( '<collection xmlns="http://www.loc.gov/MARC21/slim"', $xml );
		$this->assertStringContainsString( '<record>', $xml );
		$this->assertStringContainsString( '<leader>', $xml );
		$this->assertStringContainsString( '</collection>', $xml );
	}

	public function test_marc_xml_validates_structure() {
		$generator = new MarcXmlGenerator();
		$xml = $generator->generateXml();
		
		// Load XML and verify it's valid
		$dom = new \DOMDocument();
		$loaded = $dom->loadXML( $xml );
		
		$this->assertTrue( $loaded, 'Generated XML should be valid' );
		
		// Check for required elements
		$this->assertEquals( 1, $dom->getElementsByTagName( 'collection' )->length );
		$this->assertEquals( 1, $dom->getElementsByTagName( 'record' )->length );
		$this->assertEquals( 1, $dom->getElementsByTagName( 'leader' )->length );
	}

	public function test_marc_xml_contains_title() {
		$generator = new MarcXmlGenerator();
		$xml = $generator->generateXml();
		
		$this->assertStringContainsString( 'Test Book Title', $xml );
		$this->assertStringContainsString( '<datafield tag="245"', $xml );
	}

	public function test_marc_xml_contains_isbn() {
		$generator = new MarcXmlGenerator();
		$xml = $generator->generateXml();
		
		$this->assertStringContainsString( '<datafield tag="020"', $xml );
		$this->assertStringContainsString( '9781234567890', $xml );
	}

	public function test_marc_xml_filename_generation() {
		$generator = new MarcXmlGenerator();
		$filename = $generator->getFilename();
		
		$this->assertStringContainsString( 'test-book-title', $filename );
		$this->assertStringEndsWith( '-marc21.xml', $filename );
	}

	public function test_language_code_mapping() {
		$generator = new MarcXmlGenerator();
		$xml = $generator->generateXml();
		
		// Check for language field (041) with 3-letter code
		$this->assertStringContainsString( '<datafield tag="041"', $xml );
		$this->assertStringContainsString( 'eng', $xml ); // 'en' should map to 'eng'
	}

	public function test_008_field_generation() {
		$generator = new MarcXmlGenerator();
		$xml = $generator->generateXml();
		
		// Check for 008 control field
		$this->assertStringContainsString( '<controlfield tag="008"', $xml );
		
		// Should contain language code
		$this->assertStringContainsString( 'eng', $xml );
	}

	public function test_bibtex_generation() {
		$generator = new BibtexGenerator();
		$bibtex = $generator->generate();
		
		$this->assertNotEmpty( $bibtex );
		$this->assertStringContainsString( '@book{', $bibtex );
		$this->assertStringContainsString( 'title = {', $bibtex );
	}

	public function test_bibtex_contains_title() {
		$generator = new BibtexGenerator();
		$bibtex = $generator->generate();
		
		$this->assertStringContainsString( 'Test Book Title', $bibtex );
	}

	public function test_bibtex_contains_author() {
		$generator = new BibtexGenerator();
		$bibtex = $generator->generate();
		
		$this->assertStringContainsString( 'author = {', $bibtex );
		$this->assertStringContainsString( 'Test Author', $bibtex );
	}

	public function test_bibtex_contains_publisher() {
		$generator = new BibtexGenerator();
		$bibtex = $generator->generate();
		
		$this->assertStringContainsString( 'publisher = {', $bibtex );
		$this->assertStringContainsString( 'Test Publisher', $bibtex );
	}

	public function test_bibtex_contains_year() {
		$generator = new BibtexGenerator();
		$bibtex = $generator->generate();
		
		$this->assertStringContainsString( 'year = ', $bibtex );
		$this->assertStringContainsString( date( 'Y' ), $bibtex );
	}

	public function test_bibtex_contains_isbn() {
		$generator = new BibtexGenerator();
		$bibtex = $generator->generate();
		
		$this->assertStringContainsString( 'isbn = {', $bibtex );
		$this->assertStringContainsString( '9781234567890', $bibtex );
	}

	public function test_bibtex_escapes_special_chars() {
		// Create a book with special characters
		$meta_post = $this->factory()->post->create_and_get( [
			'post_title' => 'Book Information',
			'post_type' => 'metadata',
			'post_status' => 'publish',
		] );
		
		update_post_meta( $meta_post->ID, 'pb_title', 'Test & Title with % Special # Chars' );
		update_post_meta( $meta_post->ID, 'pb_publisher', 'Test Publisher' );
		
		$generator = new BibtexGenerator();
		$bibtex = $generator->generate();
		
		// Should escape special LaTeX characters
		$this->assertStringContainsString( '\\&', $bibtex );
		$this->assertStringContainsString( '\\%', $bibtex );
		$this->assertStringContainsString( '\\#', $bibtex );
	}

	public function test_bibtex_filename_generation() {
		$generator = new BibtexGenerator();
		$filename = $generator->getFilename();
		
		$this->assertStringContainsString( 'test-book-title', $filename );
		$this->assertStringEndsWith( '.bib', $filename );
	}

	public function test_bibtex_citation_key_format() {
		$generator = new BibtexGenerator();
		$bibtex = $generator->generate();
		
		// Citation key should be lowercase and contain author+year+title
		$this->assertMatchesRegularExpression( '/@book\{[a-z0-9]+,/', $bibtex );
	}
}
