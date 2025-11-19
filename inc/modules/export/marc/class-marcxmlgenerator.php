<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\Marc;

use Pressbooks\Book;
use Pressbooks\Contributors;

/**
 * Generates MARC21 XML from Pressbooks book metadata
 */
class MarcXmlGenerator {

	/**
	 * Book metadata
	 *
	 * @var array
	 */
	protected array $metadata;

	/**
	 * MARC record
	 *
	 * @var MarcRecord
	 */
	protected MarcRecord $record;

	/**
	 * Constructor
	 *
	 * @param array|null $metadata Book metadata (defaults to current book)
	 */
	public function __construct( ?array $metadata = null ) {
		$this->metadata = $metadata ?? Book::getBookInformation();
		$this->record = new MarcRecord();
	}

	/**
	 * Generate MARC record from book metadata
	 *
	 * @return MarcRecord
	 */
	public function generateRecord(): MarcRecord {
		// Add control fields
		$this->addControlFields();

		// Add data fields
		$this->addIsbn(); // 020
		$this->addLanguage(); // 041
		$this->addAuthor(); // 100
		$this->addTitle(); // 245
		$this->addPublisher(); // 264
		$this->addSeries(); // 490
		$this->addDescription(); // 520
		$this->addBisacSubjects(); // 650
		$this->addKeywords(); // 653
		$this->addAdditionalAuthors(); // 700 (authors after first)
		$this->addEditors(); // 700
		$this->addTranslators(); // 700
		$this->addDoi(); // 856

		// Sort fields by tag
		$this->record->sortFields();

		return $this->record;
	}

	/**
	 * Add control fields (001, 003, 005, 008)
	 */
	protected function addControlFields(): void {
		// 001 - Control Number
		// Use blog ID as control number
		$blog_id = function_exists( 'get_current_blog_id' ) ? get_current_blog_id() : 1;
		$this->record->addControlField( '001', (string) $blog_id );

		// 003 - Control Number Identifier
		// Use network domain
		$domain = 'pressbooks';
		if ( function_exists( 'network_site_url' ) ) {
			$domain = parse_url( network_site_url(), PHP_URL_HOST ) ?? 'pressbooks';
		}
		$this->record->addControlField( '003', $domain );

		// 005 - Date and Time of Latest Transaction
		$this->record->addControlField( '005', gmdate( 'YmdHis.0' ) );

		// 008 - Fixed-Length Data Elements
		$this->record->addControlField( '008', $this->generate008Field() );
	}

	/**
	 * Generate 008 field (fixed-length data elements)
	 *
	 * @return string
	 */
	protected function generate008Field(): string {
		// Position 00-05: Date entered (YYMMDD)
		$date_entered = gmdate( 'ymd' );

		// Position 06: Type of date/publication status (s = single known date)
		$date_type = 's';

		// Position 07-10: Date 1 (publication year)
		$date1 = '    ';
		if ( ! empty( $this->metadata['pb_publication_date'] ) ) {
			$date1 = str_pad( date( 'Y', (int) $this->metadata['pb_publication_date'] ), 4, ' ', STR_PAD_LEFT );
		}

		// Position 11-14: Date 2 (blank for single date)
		$date2 = '    ';

		// Position 15-17: Place of publication (use XXX for unknown)
		$place = 'xxu'; // Default to United States, unknown
		// Could be enhanced to map pb_publisher_city to MARC country codes

		// Position 18-21: Illustrations (blank = no illustrations)
		$illustrations = '    ';

		// Position 22: Target audience (space = unknown or not specified)
		$audience = ' ';
		if ( ! empty( $this->metadata['pb_audience'] ) ) {
			switch ( $this->metadata['pb_audience'] ) {
				case 'juvenile':
					$audience = 'j';
					break;
				case 'young-adult':
					$audience = 'd';
					break;
				case 'adult':
					$audience = ' ';
					break;
			}
		}

		// Position 23: Form of item (space = none of the following)
		$form = ' ';
		// Could be 'q' for electronic, 'o' for online

		// Position 24-27: Nature of contents (blank)
		$nature = '    ';

		// Position 28: Government publication (space = not a government publication)
		$govt_pub = ' ';

		// Position 29: Conference publication (0 = not a conference publication)
		$conf_pub = '0';

		// Position 30: Festschrift (0 = not a festschrift)
		$festschrift = '0';

		// Position 31: Index (0 = no index)
		$index = '0';

		// Position 32: Undefined (space)
		$undefined = ' ';

		// Position 33: Literary form (0 = not fiction)
		$literary_form = '0';

		// Position 34: Biography (space = no biographical material)
		$biography = ' ';

		// Position 35-37: Language (3-letter code)
		$language = $this->getLanguageCode();

		// Position 38: Modified record (space = not modified)
		$modified = ' ';

		// Position 39: Cataloging source (d = other)
		$cataloging_source = 'd';

		return $date_entered . $date_type . $date1 . $date2 . $place .
			   $illustrations . $audience . $form . $nature . $govt_pub .
			   $conf_pub . $festschrift . $index . $undefined .
			   $literary_form . $biography . $language . $modified . $cataloging_source;
	}

	/**
	 * Get 3-letter language code for 008 and 041 fields
	 *
	 * @return string
	 */
	protected function getLanguageCode(): string {
		if ( empty( $this->metadata['pb_language'] ) ) {
			return 'eng'; // Default to English
		}

		// Map ISO 639-1 (2-letter) to ISO 639-2 (3-letter) codes
		$language_map = [
			'en' => 'eng',
			'fr' => 'fre',
			'es' => 'spa',
			'de' => 'ger',
			'it' => 'ita',
			'pt' => 'por',
			'ru' => 'rus',
			'zh' => 'chi',
			'ja' => 'jpn',
			'ar' => 'ara',
			'hi' => 'hin',
			'nl' => 'dut',
			'sv' => 'swe',
			'no' => 'nor',
			'da' => 'dan',
			'fi' => 'fin',
			'pl' => 'pol',
			'tr' => 'tur',
			'ko' => 'kor',
			'el' => 'gre',
			'he' => 'heb',
			'cs' => 'cze',
			'hu' => 'hun',
			'ro' => 'rum',
			'th' => 'tha',
			'vi' => 'vie',
			'uk' => 'ukr',
			'ca' => 'cat',
			'id' => 'ind',
			'ms' => 'may',
			'fa' => 'per',
		];

		// Extract language code (may be 'en-US' format, we want just 'en')
		$lang_code = strtok( $this->metadata['pb_language'], '-_' );

		return $language_map[ $lang_code ] ?? 'eng';
	}

	/**
	 * Add ISBN field (020)
	 */
	protected function addIsbn(): void {
		// Ebook ISBN
		if ( ! empty( $this->metadata['pb_ebook_isbn'] ) ) {
			$isbn = str_replace( [ '-', ' ' ], '', $this->metadata['pb_ebook_isbn'] );
			$subfields = [
				[ 'code' => 'a', 'data' => $isbn ],
				[ 'code' => 'q', 'data' => 'ebook' ],
			];
			$this->record->addDataField( '020', ' ', ' ', $subfields );
		}

		// Print ISBN
		if ( ! empty( $this->metadata['pb_print_isbn'] ) ) {
			$isbn = str_replace( [ '-', ' ' ], '', $this->metadata['pb_print_isbn'] );
			$subfields = [
				[ 'code' => 'a', 'data' => $isbn ],
				[ 'code' => 'q', 'data' => 'print' ],
			];
			$this->record->addDataField( '020', ' ', ' ', $subfields );
		}
	}

	/**
	 * Add language field (041)
	 */
	protected function addLanguage(): void {
		if ( ! empty( $this->metadata['pb_language'] ) ) {
			$lang_code = $this->getLanguageCode();
			$subfields = [
				[ 'code' => 'a', 'data' => $lang_code ],
			];
			// Indicator 1: 0 = item not a translation/does not include a translation
			// Indicator 2: 7 = Source specified in $2
			$this->record->addDataField( '041', '0', ' ', $subfields );
		}
	}

	/**
	 * Add main author field (100)
	 */
	protected function addAuthor(): void {
		if ( ! empty( $this->metadata['pb_authors'] ) && is_array( $this->metadata['pb_authors'] ) ) {
			// First author goes in 100 field
			$first_author = $this->metadata['pb_authors'][0];

			$subfields = [
				[ 'code' => 'a', 'data' => $first_author['name'] ],
			];

			// Add birth/death dates if available
			if ( ! empty( $first_author['birth'] ) || ! empty( $first_author['death'] ) ) {
				$dates = trim( ( $first_author['birth'] ?? '' ) . '-' . ( $first_author['death'] ?? '' ) );
				$subfields[] = [ 'code' => 'd', 'data' => $dates ];
			}

			// Indicator 1: 1 = surname (single surname or family name)
			// Indicator 2: blank
			$this->record->addDataField( '100', '1', ' ', $subfields );
		}
	}

	/**
	 * Add title field (245)
	 */
	protected function addTitle(): void {
		if ( empty( $this->metadata['pb_title'] ) ) {
			return;
		}

		$title = $this->metadata['pb_title'];

		// Check if title starts with article (The, A, An, etc.)
		$non_filing_chars = $this->getNonFilingCharacters( $title );

		$subfields = [
			[ 'code' => 'a', 'data' => $title ],
		];

		// Add subtitle if present
		if ( ! empty( $this->metadata['pb_subtitle'] ) ) {
			$subfields[] = [ 'code' => 'b', 'data' => $this->metadata['pb_subtitle'] ];
		}

		// Indicator 1: 0 = no added entry (we have 100 field for author)
		//             1 = added entry (if no author)
		$ind1 = empty( $this->metadata['pb_authors'] ) ? '0' : '1';

		// Indicator 2: number of non-filing characters
		$ind2 = (string) $non_filing_chars;

		$this->record->addDataField( '245', $ind1, $ind2, $subfields );
	}

	/**
	 * Calculate non-filing characters in title
	 *
	 * @param string $title
	 * @return int
	 */
	protected function getNonFilingCharacters( string $title ): int {
		$articles = [
			'The ',
			'A ',
			'An ',
			'El ',
			'La ',
			'Los ',
			'Las ',
			'Un ',
			'Una ',
			'Le ',
			'Les ',
			'L\'',
			'Il ',
			'Der ',
			'Die ',
			'Das ',
			'Ein ',
			'Eine ',
		];

		foreach ( $articles as $article ) {
			if ( stripos( $title, $article ) === 0 ) {
				return strlen( $article );
			}
		}

		return 0;
	}

	/**
	 * Add publisher field (264)
	 */
	protected function addPublisher(): void {
		// Need at least publisher name
		if ( empty( $this->metadata['pb_publisher'] ) ) {
			return;
		}

		$subfields = [];

		// $a - Place of production, publication, distribution, manufacture
		if ( ! empty( $this->metadata['pb_publisher_city'] ) ) {
			$subfields[] = [ 'code' => 'a', 'data' => $this->metadata['pb_publisher_city'] ];
		}

		// $b - Name of producer, publisher, distributor, manufacturer
		$subfields[] = [ 'code' => 'b', 'data' => $this->metadata['pb_publisher'] ];

		// $c - Date of production, publication, distribution, manufacture, or copyright notice
		if ( ! empty( $this->metadata['pb_publication_date'] ) ) {
			$date = date( 'Y', (int) $this->metadata['pb_publication_date'] );
			$subfields[] = [ 'code' => 'c', 'data' => $date ];
		}

		// Indicator 1: blank
		// Indicator 2: 1 = publication
		$this->record->addDataField( '264', ' ', '1', $subfields );
	}

	/**
	 * Add series statement field (490)
	 */
	protected function addSeries(): void {
		if ( empty( $this->metadata['pb_series_title'] ) ) {
			return;
		}

		$subfields = [
			[ 'code' => 'a', 'data' => $this->metadata['pb_series_title'] ],
		];

		// Add series number if present
		if ( ! empty( $this->metadata['pb_series_number'] ) ) {
			$subfields[] = [ 'code' => 'v', 'data' => $this->metadata['pb_series_number'] ];
		}

		// Indicator 1: 0 = series not traced
		// Indicator 2: blank
		$this->record->addDataField( '490', '0', ' ', $subfields );
	}

	/**
	 * Add summary/description field (520)
	 */
	protected function addDescription(): void {
		// Priority: long description > short description > tagline
		$description = null;

		if ( ! empty( $this->metadata['pb_about_unlimited'] ) ) {
			// Strip HTML tags from long description
			$description = function_exists( 'wp_strip_all_tags' )
				? wp_strip_all_tags( $this->metadata['pb_about_unlimited'] )
				: strip_tags( $this->metadata['pb_about_unlimited'] );
		} elseif ( ! empty( $this->metadata['pb_about_50'] ) ) {
			$description = $this->metadata['pb_about_50'];
		} elseif ( ! empty( $this->metadata['pb_about_140'] ) ) {
			$description = $this->metadata['pb_about_140'];
		}

		if ( $description ) {
			$subfields = [
				[ 'code' => 'a', 'data' => trim( $description ) ],
			];

			// Indicator 1: blank (no display constant generated)
			// Indicator 2: blank
			$this->record->addDataField( '520', ' ', ' ', $subfields );
		}
	}

	/**
	 * Add BISAC subject headings (650)
	 */
	protected function addBisacSubjects(): void {
		if ( empty( $this->metadata['pb_bisac_subject'] ) ) {
			return;
		}

		// pb_bisac_subject may be a comma-separated string or array
		$subjects = is_array( $this->metadata['pb_bisac_subject'] )
			? $this->metadata['pb_bisac_subject']
			: array_map( 'trim', explode( ',', $this->metadata['pb_bisac_subject'] ) );

		foreach ( $subjects as $subject ) {
			if ( empty( trim( $subject ) ) ) {
				continue;
			}

			$subfields = [
				[ 'code' => 'a', 'data' => trim( $subject ) ],
				[ 'code' => '2', 'data' => 'bisacsh' ], // Source: BISAC Subject Headings
			];

			// Indicator 1: blank (no information provided)
			// Indicator 2: 7 (source specified in $2)
			$this->record->addDataField( '650', ' ', '7', $subfields );
		}
	}

	/**
	 * Add keywords (653)
	 */
	protected function addKeywords(): void {
		if ( empty( $this->metadata['pb_keywords_tags'] ) ) {
			return;
		}

		// pb_keywords_tags may be a comma-separated string or array
		$keywords = is_array( $this->metadata['pb_keywords_tags'] )
			? $this->metadata['pb_keywords_tags']
			: array_map( 'trim', explode( ',', $this->metadata['pb_keywords_tags'] ) );

		foreach ( $keywords as $keyword ) {
			if ( empty( trim( $keyword ) ) ) {
				continue;
			}

			$subfields = [
				[ 'code' => 'a', 'data' => trim( $keyword ) ],
			];

			// Indicator 1: blank (no information provided)
			// Indicator 2: blank (no information provided)
			$this->record->addDataField( '653', ' ', ' ', $subfields );
		}
	}

	/**
	 * Add editors (700)
	 */
	protected function addEditors(): void {
		if ( empty( $this->metadata['pb_editors'] ) || ! is_array( $this->metadata['pb_editors'] ) ) {
			return;
		}

		foreach ( $this->metadata['pb_editors'] as $editor ) {
			$subfields = [
				[ 'code' => 'a', 'data' => $editor['name'] ],
				[ 'code' => '4', 'data' => 'edt' ], // Relator code for editor
			];

			// Add birth/death dates if available
			if ( ! empty( $editor['birth'] ) || ! empty( $editor['death'] ) ) {
				$dates = trim( ( $editor['birth'] ?? '' ) . '-' . ( $editor['death'] ?? '' ) );
				$subfields[] = [ 'code' => 'd', 'data' => $dates ];
			}

			// Indicator 1: 1 = surname
			// Indicator 2: blank
			$this->record->addDataField( '700', '1', ' ', $subfields );
		}
	}

	/**
	 * Add translators (700)
	 */
	protected function addTranslators(): void {
		if ( empty( $this->metadata['pb_translators'] ) || ! is_array( $this->metadata['pb_translators'] ) ) {
			return;
		}

		foreach ( $this->metadata['pb_translators'] as $translator ) {
			$subfields = [
				[ 'code' => 'a', 'data' => $translator['name'] ],
				[ 'code' => '4', 'data' => 'trl' ], // Relator code for translator
			];

			// Add birth/death dates if available
			if ( ! empty( $translator['birth'] ) || ! empty( $translator['death'] ) ) {
				$dates = trim( ( $translator['birth'] ?? '' ) . '-' . ( $translator['death'] ?? '' ) );
				$subfields[] = [ 'code' => 'd', 'data' => $dates ];
			}

			// Indicator 1: 1 = surname
			// Indicator 2: blank
			$this->record->addDataField( '700', '1', ' ', $subfields );
		}
	}

	/**
	 * Add remaining authors (700)
	 * Called after adding first author to 100 field
	 */
	protected function addAdditionalAuthors(): void {
		if ( empty( $this->metadata['pb_authors'] ) || ! is_array( $this->metadata['pb_authors'] ) ) {
			return;
		}

		// Skip first author (already in 100 field)
		$authors = array_slice( $this->metadata['pb_authors'], 1 );

		foreach ( $authors as $author ) {
			$subfields = [
				[ 'code' => 'a', 'data' => $author['name'] ],
			];

			// Add birth/death dates if available
			if ( ! empty( $author['birth'] ) || ! empty( $author['death'] ) ) {
				$dates = trim( ( $author['birth'] ?? '' ) . '-' . ( $author['death'] ?? '' ) );
				$subfields[] = [ 'code' => 'd', 'data' => $dates ];
			}

			// Indicator 1: 1 = surname
			// Indicator 2: blank
			$this->record->addDataField( '700', '1', ' ', $subfields );
		}
	}

	/**
	 * Add DOI/electronic location (856)
	 */
	protected function addDoi(): void {
		if ( empty( $this->metadata['pb_book_doi'] ) ) {
			return;
		}

		// Use DOI resolver URL
		$doi_url = 'https://doi.org/' . $this->metadata['pb_book_doi'];

		$subfields = [
			[ 'code' => 'u', 'data' => $doi_url ],
			[ 'code' => 'z', 'data' => 'DOI' ],
		];

		// Indicator 1: 4 = HTTP
		// Indicator 2: 0 = Resource
		$this->record->addDataField( '856', '4', '0', $subfields );
	}

	/**
	 * Generate MARC21 XML from record
	 *
	 * @return string XML string
	 */
	public function generateXml(): string {
		// Generate record first
		$record = $this->generateRecord();

		// Create XML document
		$dom = new \DOMDocument( '1.0', 'UTF-8' );
		$dom->formatOutput = true;

		// Root element: collection
		$collection = $dom->createElement( 'collection' );
		$collection->setAttribute( 'xmlns', 'http://www.loc.gov/MARC21/slim' );
		$collection->setAttribute( 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance' );
		$collection->setAttribute( 'xsi:schemaLocation', 'http://www.loc.gov/MARC21/slim http://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd' );
		$dom->appendChild( $collection );

		// Record element
		$record_element = $dom->createElement( 'record' );
		$collection->appendChild( $record_element );

		// Leader
		$leader = $dom->createElement( 'leader', htmlspecialchars( $record->getLeader(), ENT_XML1 ) );
		$record_element->appendChild( $leader );

		// Control fields
		foreach ( $record->getControlFields() as $field ) {
			$controlfield = $dom->createElement( 'controlfield', htmlspecialchars( $field['data'], ENT_XML1 ) );
			$controlfield->setAttribute( 'tag', $field['tag'] );
			$record_element->appendChild( $controlfield );
		}

		// Data fields
		foreach ( $record->getDataFields() as $field ) {
			$datafield = $dom->createElement( 'datafield' );
			$datafield->setAttribute( 'tag', $field['tag'] );
			$datafield->setAttribute( 'ind1', $field['ind1'] );
			$datafield->setAttribute( 'ind2', $field['ind2'] );
			$record_element->appendChild( $datafield );

			// Subfields
			foreach ( $field['subfields'] as $subfield ) {
				$subfield_element = $dom->createElement( 'subfield', htmlspecialchars( $subfield['data'], ENT_XML1 ) );
				$subfield_element->setAttribute( 'code', $subfield['code'] );
				$datafield->appendChild( $subfield_element );
			}
		}

		return $dom->saveXML();
	}

	/**
	 * Get filename for MARC XML export
	 *
	 * @return string
	 */
	public function getFilename(): string {
		$title = ! empty( $this->metadata['pb_title'] )
			? sanitize_file_name( $this->metadata['pb_title'] )
			: 'book';

		return $title . '-marc21.xml';
	}
}
