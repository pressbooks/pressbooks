<?php
/**
 * Test MARC21 XML generation with sample data
 *
 * This file can be used to test MARC XML generation without needing
 * a full Pressbooks installation. Run with: php test-marc-generation.php
 */

require_once __DIR__ . '/class-marcrecord.php';
require_once __DIR__ . '/class-marcxmlgenerator.php';

use Pressbooks\Modules\Export\Marc\MarcXmlGenerator;

// Sample book metadata (simulating what Book::getBookInformation() returns)
$sample_metadata = [
	'pb_title' => 'The Art of Open Source',
	'pb_subtitle' => 'Building Communities and Software',
	'pb_authors' => [
		[
			'name' => 'Smith, Jane',
			'birth' => '1975',
			'death' => '',
		],
		[
			'name' => 'Doe, John',
			'birth' => '1980',
			'death' => '',
		],
	],
	'pb_editors' => [
		[
			'name' => 'Johnson, Sarah',
			'birth' => '',
			'death' => '',
		],
	],
	'pb_translators' => [
		[
			'name' => 'Garcia, Maria',
			'birth' => '1985',
			'death' => '',
		],
	],
	'pb_publisher' => 'Open Press',
	'pb_publisher_city' => 'Toronto',
	'pb_publication_date' => strtotime( '2024-01-15' ),
	'pb_ebook_isbn' => '978-1-234567-89-0',
	'pb_print_isbn' => '978-1-234567-90-6',
	'pb_book_doi' => '10.1234/example.doi',
	'pb_language' => 'en',
	'pb_series_title' => 'Open Knowledge Series',
	'pb_series_number' => '3',
	'pb_about_140' => 'A comprehensive guide to building open source communities',
	'pb_about_50' => 'This book explores the principles and practices of creating thriving open source software projects and communities.',
	'pb_about_unlimited' => '<p>This comprehensive guide takes you through the journey of building successful open source projects. From initial concept to community management, learn the best practices that make projects thrive.</p>',
	'pb_keywords_tags' => 'open source, software development, community building, collaboration',
	'pb_bisac_subject' => 'COMPUTERS / Software Development & Engineering / General, COMPUTERS / Open Source',
	'pb_audience' => 'adult',
];

// Generate MARC XML
echo "Generating MARC21 XML...\n";
$generator = new MarcXmlGenerator( $sample_metadata );
$xml = $generator->generateXml();

// Save to file
$filename = 'sample-marc21.xml';
file_put_contents( $filename, $xml );

echo "✓ Generated MARC21 XML: $filename\n";
echo "\nXML Preview (first 1000 characters):\n";
echo str_repeat( '=', 70 ) . "\n";
echo substr( $xml, 0, 1000 ) . "...\n";
echo str_repeat( '=', 70 ) . "\n";

echo "\nTo validate this file, download the MARC21slim schema and run:\n";
echo "xmllint --noout --schema MARC21slim.xsd $filename\n";
echo "\nSchema URL: https://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd\n";

// Display some statistics
$record = $generator->generateRecord();
echo "\n" . str_repeat( '=', 70 ) . "\n";
echo "MARC Record Statistics:\n";
echo str_repeat( '=', 70 ) . "\n";
echo "Control Fields: " . count( $record->getControlFields() ) . "\n";
echo "Data Fields: " . count( $record->getDataFields() ) . "\n";
echo "\nData Fields by Tag:\n";

$field_counts = [];
foreach ( $record->getDataFields() as $field ) {
	$tag = $field['tag'];
	$field_counts[ $tag ] = ( $field_counts[ $tag ] ?? 0 ) + 1;
}

ksort( $field_counts );
foreach ( $field_counts as $tag => $count ) {
	$field_name = get_marc_field_name( $tag );
	echo "  $tag: $field_name ($count)\n";
}

/**
 * Get human-readable name for MARC field tag
 *
 * @param string $tag
 * @return string
 */
function get_marc_field_name( string $tag ): string {
	$names = [
		'020' => 'ISBN',
		'041' => 'Language Code',
		'100' => 'Main Entry - Personal Name',
		'245' => 'Title Statement',
		'264' => 'Production, Publication, Distribution',
		'490' => 'Series Statement',
		'520' => 'Summary',
		'650' => 'Subject - Topical Term',
		'653' => 'Index Term - Uncontrolled',
		'700' => 'Added Entry - Personal Name',
		'856' => 'Electronic Location and Access',
	];

	return $names[ $tag ] ?? 'Unknown Field';
}
