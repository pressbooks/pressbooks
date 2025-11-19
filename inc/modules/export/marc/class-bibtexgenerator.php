<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\Marc;

use Pressbooks\Book;

/**
 * Generates BibTeX from Pressbooks book metadata
 */
class BibtexGenerator {

	/**
	 * Book metadata
	 *
	 * @var array
	 */
	protected array $metadata;

	/**
	 * Constructor
	 *
	 * @param array|null $metadata Book metadata (defaults to current book)
	 */
	public function __construct( ?array $metadata = null ) {
		$this->metadata = $metadata ?? Book::getBookInformation();
	}

	/**
	 * Generate BibTeX entry from book metadata
	 *
	 * @return string BibTeX formatted string
	 */
	public function generate(): string {
		// Determine entry type (default to @book)
		$entry_type = '@book';

		// Generate citation key from author + year + title
		$cite_key = $this->generateCitationKey();

		$fields = [];

		// Required fields for @book
		$fields['title'] = $this->escapeValue( $this->metadata['pb_title'] ?? 'Untitled' );

		// Authors
		if ( ! empty( $this->metadata['pb_authors'] ) && is_array( $this->metadata['pb_authors'] ) ) {
			$authors = array_map(
				function( $author ) {
					return $author['name'] ?? '';
				},
				$this->metadata['pb_authors']
			);
			$fields['author'] = $this->escapeValue( implode( ' and ', array_filter( $authors ) ) );
		}

		// Editors
		if ( ! empty( $this->metadata['pb_editors'] ) && is_array( $this->metadata['pb_editors'] ) ) {
			$editors = array_map(
				function( $editor ) {
					return $editor['name'] ?? '';
				},
				$this->metadata['pb_editors']
			);
			$fields['editor'] = $this->escapeValue( implode( ' and ', array_filter( $editors ) ) );
		}

		// Publisher
		if ( ! empty( $this->metadata['pb_publisher'] ) ) {
			$fields['publisher'] = $this->escapeValue( $this->metadata['pb_publisher'] );
		}

		// Year
		if ( ! empty( $this->metadata['pb_publication_date'] ) ) {
			$fields['year'] = date( 'Y', (int) $this->metadata['pb_publication_date'] );
		}

		// Optional fields
		if ( ! empty( $this->metadata['pb_subtitle'] ) ) {
			$fields['subtitle'] = $this->escapeValue( $this->metadata['pb_subtitle'] );
		}

		if ( ! empty( $this->metadata['pb_publisher_city'] ) ) {
			$fields['address'] = $this->escapeValue( $this->metadata['pb_publisher_city'] );
		}

		if ( ! empty( $this->metadata['pb_ebook_isbn'] ) ) {
			$isbn = str_replace( [ '-', ' ' ], '', $this->metadata['pb_ebook_isbn'] );
			$fields['isbn'] = $this->escapeValue( $isbn );
		} elseif ( ! empty( $this->metadata['pb_print_isbn'] ) ) {
			$isbn = str_replace( [ '-', ' ' ], '', $this->metadata['pb_print_isbn'] );
			$fields['isbn'] = $this->escapeValue( $isbn );
		}

		if ( ! empty( $this->metadata['pb_book_doi'] ) ) {
			$fields['doi'] = $this->escapeValue( $this->metadata['pb_book_doi'] );
		}

		if ( ! empty( $this->metadata['pb_language'] ) ) {
			// Extract language code (may be 'en-US' format, we want just 'en')
			$lang_code = strtok( $this->metadata['pb_language'], '-_' );
			$fields['language'] = $this->escapeValue( $lang_code );
		}

		if ( ! empty( $this->metadata['pb_series_title'] ) ) {
			$fields['series'] = $this->escapeValue( $this->metadata['pb_series_title'] );
		}

		if ( ! empty( $this->metadata['pb_series_number'] ) ) {
			$fields['number'] = $this->escapeValue( $this->metadata['pb_series_number'] );
		}

		// Abstract/description
		$abstract = $this->getAbstract();
		if ( $abstract ) {
			$fields['abstract'] = $this->escapeValue( $abstract );
		}

		// Keywords
		if ( ! empty( $this->metadata['pb_keywords_tags'] ) ) {
			$keywords = is_array( $this->metadata['pb_keywords_tags'] )
				? implode( ', ', $this->metadata['pb_keywords_tags'] )
				: $this->metadata['pb_keywords_tags'];
			$fields['keywords'] = $this->escapeValue( $keywords );
		}

		// URL (if available)
		$url = $this->getBookUrl();
		if ( $url ) {
			$fields['url'] = $this->escapeValue( $url );
		}

		// Copyright/license
		if ( ! empty( $this->metadata['pb_copyright_holder'] ) ) {
			$fields['copyright'] = $this->escapeValue( $this->metadata['pb_copyright_holder'] );
		}

		// Note with license info
		if ( ! empty( $this->metadata['pb_book_license'] ) ) {
			$license = $this->getLicenseName( $this->metadata['pb_book_license'] );
			if ( $license ) {
				$fields['note'] = $this->escapeValue( 'License: ' . $license );
			}
		}

		// Build BibTeX entry
		$bibtex = $entry_type . '{' . $cite_key . ",\n";

		foreach ( $fields as $key => $value ) {
			$bibtex .= "  {$key} = {" . $value . "},\n";
		}

		// Remove trailing comma and close entry
		$bibtex = rtrim( $bibtex, ",\n" ) . "\n}\n";

		return $bibtex;
	}

	/**
	 * Generate citation key
	 *
	 * Format: AuthorYearTitleWord (e.g., smith2024art)
	 *
	 * @return string
	 */
	protected function generateCitationKey(): string {
		$parts = [];

		// Author last name
		if ( ! empty( $this->metadata['pb_authors'] ) && is_array( $this->metadata['pb_authors'] ) ) {
			$first_author = $this->metadata['pb_authors'][0]['name'] ?? '';
			if ( $first_author ) {
				// Extract last name (assumes "Last, First" or "First Last" format)
				$name_parts = preg_split( '/[,\s]+/', $first_author );
				$parts[] = strtolower( $name_parts[0] );
			}
		}

		// Year
		if ( ! empty( $this->metadata['pb_publication_date'] ) ) {
			$parts[] = date( 'Y', (int) $this->metadata['pb_publication_date'] );
		}

		// First word of title
		if ( ! empty( $this->metadata['pb_title'] ) ) {
			$title_words = preg_split( '/\s+/', $this->metadata['pb_title'] );
			if ( ! empty( $title_words[0] ) ) {
				// Skip common articles
				$first_word = $title_words[0];
				if ( in_array( strtolower( $first_word ), [ 'the', 'a', 'an' ], true ) && ! empty( $title_words[1] ) ) {
					$first_word = $title_words[1];
				}
				$parts[] = strtolower( preg_replace( '/[^a-z0-9]/i', '', $first_word ) );
			}
		}

		// Fallback if no parts
		if ( empty( $parts ) ) {
			$parts[] = 'book';
			$parts[] = time();
		}

		return implode( '', $parts );
	}

	/**
	 * Get abstract/description
	 *
	 * @return string|null
	 */
	protected function getAbstract(): ?string {
		// Priority: short description > long description > tagline
		if ( ! empty( $this->metadata['pb_about_50'] ) ) {
			return strip_tags( $this->metadata['pb_about_50'] );
		} elseif ( ! empty( $this->metadata['pb_about_unlimited'] ) ) {
			$text = function_exists( 'wp_strip_all_tags' )
				? wp_strip_all_tags( $this->metadata['pb_about_unlimited'] )
				: strip_tags( $this->metadata['pb_about_unlimited'] );
			// Truncate if too long (BibTeX abstracts should be concise)
			return strlen( $text ) > 500 ? substr( $text, 0, 497 ) . '...' : $text;
		} elseif ( ! empty( $this->metadata['pb_about_140'] ) ) {
			return $this->metadata['pb_about_140'];
		}

		return null;
	}

	/**
	 * Get book URL
	 *
	 * @return string|null
	 */
	protected function getBookUrl(): ?string {
		if ( function_exists( 'get_bloginfo' ) ) {
			return get_bloginfo( 'url' );
		}

		return null;
	}

	/**
	 * Get license name
	 *
	 * @param string $license_key
	 * @return string|null
	 */
	protected function getLicenseName( string $license_key ): ?string {
		if ( ! class_exists( '\Pressbooks\Licensing' ) ) {
			return null;
		}

		$licensing = new \Pressbooks\Licensing();
		$types = $licensing->getSupportedTypes();

		return $types[ $license_key ]['desc'] ?? null;
	}

	/**
	 * Escape BibTeX value
	 *
	 * Handles special characters and braces
	 *
	 * @param string $value
	 * @return string
	 */
	protected function escapeValue( string $value ): string {
		// Remove control characters
		if ( function_exists( '\Pressbooks\Sanitize\remove_control_characters' ) ) {
			$value = \Pressbooks\Sanitize\remove_control_characters( $value );
		} else {
			$value = preg_replace( '/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value );
		}

		// Escape braces and backslashes
		$value = str_replace( [ '\\', '{', '}' ], [ '\\\\', '\\{', '\\}' ], $value );

		// Handle special LaTeX characters that might appear in titles
		$replacements = [
			'%' => '\\%',
			'&' => '\\&',
			'#' => '\\#',
			'_' => '\\_',
			'~' => '\\~{}',
			'^' => '\\^{}',
		];

		foreach ( $replacements as $char => $replacement ) {
			$value = str_replace( $char, $replacement, $value );
		}

		return trim( $value );
	}

	/**
	 * Get filename for BibTeX export
	 *
	 * @return string
	 */
	public function getFilename(): string {
		$title = 'book';
		
		if ( ! empty( $this->metadata['pb_title'] ) ) {
			if ( function_exists( 'sanitize_file_name' ) ) {
				$title = sanitize_file_name( $this->metadata['pb_title'] );
			} else {
				// Fallback sanitization
				$title = preg_replace( '/[^a-z0-9\-_]/i', '-', strtolower( $this->metadata['pb_title'] ) );
				$title = preg_replace( '/-+/', '-', $title );
				$title = trim( $title, '-' );
			}
		}

		return $title . '.bib';
	}
}
