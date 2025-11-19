# MARC21 and BibTeX Metadata Export

This module provides bibliographic metadata export functionality for Pressbooks books in two standard formats:
- **MARC21 XML** - For libraries and cataloging services
- **BibTeX** - For academic citations and LaTeX documents

## Features

### MARC21 XML Export

Generates MARC21 XML records with the following fields:

- **Leader** - Fixed-length record metadata
- **001** - Control number (blog ID)
- **003** - Control number identifier (network domain)
- **005** - Last transaction timestamp
- **008** - Fixed-length data elements (dates, language, audience)
- **020** - ISBN (ebook and print with qualifiers)
- **041** - Language code (ISO 639-2/3)
- **100** - Main author entry with dates
- **245** - Title and subtitle with non-filing character calculation
- **264** - Publisher information (city, name, date)
- **490** - Series statement
- **520** - Summary/description
- **650** - BISAC subject headings
- **653** - Keywords (uncontrolled)
- **700** - Additional authors, editors ($4 edt), translators ($4 trl) with dates
- **856** - DOI as electronic location

### BibTeX Export

Generates `@book` entries with fields:

- **title** - Book title
- **subtitle** - Book subtitle
- **author** - Authors (formatted with "and" separator)
- **editor** - Editors (formatted with "and" separator)
- **publisher** - Publisher name
- **address** - Publisher city
- **year** - Publication year
- **isbn** - ISBN (ebook preferred, print as fallback)
- **doi** - Digital Object Identifier
- **language** - Language code
- **series** - Series title
- **number** - Series number
- **abstract** - Book description
- **keywords** - Keywords
- **url** - Book URL
- **copyright** - Copyright holder
- **note** - License information

## Usage

### In WordPress Admin

1. Navigate to a book's **Book Information** page
2. Find the **Bibliographic Metadata Export** metabox in the sidebar
3. Click **Download MARC XML** or **Download BibTeX**
4. Files download automatically with appropriate naming

### Programmatic Usage

#### MARC21 XML Generation

```php
use Pressbooks\Modules\Export\Marc\MarcXmlGenerator;

// Generate for current book
$generator = new MarcXmlGenerator();
$xml = $generator->generateXml();
$filename = $generator->getFilename();

// Or with custom metadata
$metadata = \Pressbooks\Book::getBookInformation();
$generator = new MarcXmlGenerator( $metadata );
$xml = $generator->generateXml();
```

#### BibTeX Generation

```php
use Pressbooks\Modules\Export\Marc\BibtexGenerator;

// Generate for current book
$generator = new BibtexGenerator();
$bibtex = $generator->generate();
$filename = $generator->getFilename();

// Or with custom metadata
$metadata = \Pressbooks\Book::getBookInformation();
$generator = new BibtexGenerator( $metadata );
$bibtex = $generator->generate();
```

## Validation

### MARC21 XML

Validate generated XML against the official schema:

```bash
# Download schema
wget https://www.loc.gov/standards/marcxml/schema/MARC21slim.xsd

# Validate
xmllint --noout --schema MARC21slim.xsd your-book-marc21.xml
```

### BibTeX

Test in LaTeX document:

```latex
\documentclass{article}
\begin{document}

\cite{citationkey}

\bibliographystyle{plain}
\bibliography{your-book}

\end{document}
```

## Field Mappings

### Pressbooks → MARC21

| Pressbooks Field | MARC Field | Notes |
|-----------------|------------|-------|
| pb_title | 245 $a | With non-filing characters |
| pb_subtitle | 245 $b | |
| pb_authors[0] | 100 $a | First author only |
| pb_authors[1+] | 700 $a | Additional authors |
| pb_authors birth/death | 100/700 $d | Format: YYYY-YYYY |
| pb_editors | 700 $a$4 | $4 = "edt" |
| pb_translators | 700 $a$4 | $4 = "trl" |
| pb_publisher | 264 $b | |
| pb_publisher_city | 264 $a | |
| pb_publication_date | 264 $c | Year only |
| pb_ebook_isbn | 020 $a$q | $q = "ebook" |
| pb_print_isbn | 020 $a$q | $q = "print" |
| pb_language | 041 $a, 008/35-37 | ISO 639-2/3 |
| pb_series_title | 490 $a | |
| pb_series_number | 490 $v | |
| pb_about_unlimited | 520 $a | HTML stripped |
| pb_about_50 | 520 $a | If unlimited empty |
| pb_about_140 | 520 $a | If both above empty |
| pb_bisac_subject | 650 $a$2 | $2 = "bisacsh" |
| pb_keywords_tags | 653 $a | One per keyword |
| pb_book_doi | 856 $u$z | $z = "DOI" |
| pb_audience | 008/22 | j/d/adult |

### Pressbooks → BibTeX

| Pressbooks Field | BibTeX Field |
|-----------------|--------------|
| pb_title | title |
| pb_subtitle | subtitle |
| pb_authors | author |
| pb_editors | editor |
| pb_publisher | publisher |
| pb_publisher_city | address |
| pb_publication_date | year |
| pb_ebook_isbn / pb_print_isbn | isbn |
| pb_book_doi | doi |
| pb_language | language |
| pb_series_title | series |
| pb_series_number | number |
| pb_about_50 / pb_about_unlimited / pb_about_140 | abstract |
| pb_keywords_tags | keywords |
| pb_copyright_holder | copyright |
| pb_book_license | note |

## Testing

Run tests:

```bash
# All MARC/BibTeX tests
lando phpunit tests/test-marc-export.php

# Standalone tests
cd inc/modules/export/marc
php test-marc-generation.php
php test-bibtex-generation.php
```

## Technical Details

### Character Encoding

- **MARC21**: UTF-8 with XML entity escaping
- **BibTeX**: LaTeX special character escaping (%, &, #, _, ~, ^, {, })

### Citation Key Generation

BibTeX citation keys follow the format: `authorYearTitleWord`

Example: `smith2024art` for "The Art of Open Source" by Smith (2024)

### Language Code Mapping

Automatically converts ISO 639-1 (2-letter) to ISO 639-2/3 (3-letter):

```
en → eng    fr → fre    es → spa    de → ger
it → ita    pt → por    zh → chi    ja → jpn
```

See `MarcXmlGenerator::getLanguageCode()` for complete mapping.

## Standards Compliance

- **MARC21**: Follows [Library of Congress MARC21 Format for Bibliographic Data](https://www.loc.gov/marc/bibliographic/)
- **BibTeX**: Compatible with BibTeX 0.99+ and BibLaTeX

## Hooks and Filters

### Actions

```php
// Before MARC export metabox render
do_action( 'pb_before_marc_export_metabox', $post );

// After successful download
do_action( 'pb_after_marc_download', $metadata, $format );
```

### Filters

```php
// Modify MARC record before XML generation
add_filter( 'pb_marc_record', function( $record, $metadata ) {
    // Modify $record
    return $record;
}, 10, 2 );

// Modify BibTeX entry before output
add_filter( 'pb_bibtex_entry', function( $bibtex, $metadata ) {
    // Modify $bibtex
    return $bibtex;
}, 10, 2 );
```

## Future Enhancements

- [ ] Additional MARC fields (subject classifications, physical description)
- [ ] More BibTeX entry types (@inbook, @incollection, etc.)
- [ ] Bulk export for multiple books
- [ ] OAI-PMH integration
- [ ] RIS format export
- [ ] Automatic DOI registration integration

## Credits

Developed for Pressbooks to support open access publishing and scholarly communication.

## License

GPLv3 or any later version
