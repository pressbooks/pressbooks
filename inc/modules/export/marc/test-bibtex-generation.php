<?php
/**
 * Test BibTeX generation with sample data
 *
 * Run with: php test-bibtex-generation.php
 */

require_once __DIR__ . '/class-bibtexgenerator.php';

use Pressbooks\Modules\Export\Marc\BibtexGenerator;

// Sample book metadata
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
	'pb_publisher' => 'Open Press',
	'pb_publisher_city' => 'Toronto',
	'pb_publication_date' => strtotime( '2024-01-15' ),
	'pb_ebook_isbn' => '978-1-234567-89-0',
	'pb_book_doi' => '10.1234/example.doi',
	'pb_language' => 'en',
	'pb_series_title' => 'Open Knowledge Series',
	'pb_series_number' => '3',
	'pb_about_50' => 'This book explores the principles and practices of creating thriving open source software projects and communities.',
	'pb_keywords_tags' => 'open source, software development, community building, collaboration',
	'pb_book_license' => 'cc-by',
	'pb_copyright_holder' => 'Jane Smith and John Doe',
];

echo "Generating BibTeX...\n";
echo str_repeat( '=', 70 ) . "\n";

$generator = new BibtexGenerator( $sample_metadata );
$bibtex = $generator->generate();

echo $bibtex;

echo str_repeat( '=', 70 ) . "\n";
echo "✓ BibTeX generated successfully!\n";
echo "  Filename: " . $generator->getFilename() . "\n";
echo "\nTo use this in a LaTeX document:\n";
echo "  1. Save to a .bib file\n";
echo "  2. Reference with \\cite{citation-key}\n";
echo "  3. Include \\bibliography{filename} in your LaTeX document\n";
