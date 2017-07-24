<?php

namespace Pressbooks\Metadata;

use Pressbooks\Book;
use Pressbooks\Sanitize;

/**
 * Returns an html blob of meta elements based on what is set in 'Book Information'
 *
 * @return string
 */
function get_seo_meta_elements() {
	// map items that are already captured
	$meta_mapping = [
		'author' => 'pb_author',
		'description' => 'pb_about_50',
		'keywords' => 'pb_keywords_tags',
		'publisher' => 'pb_publisher',
	];
	$html = "<meta name='application-name' content='Pressbooks'>\n";
	$metadata = Book::getBookInformation();

	// create meta elements
	foreach ( $meta_mapping as $name => $content ) {
		if ( array_key_exists( $content, $metadata ) ) {
			$html .= "<meta name='" . $name . "' content='" . $metadata[ $content ] . "'>\n";
		}
	}

	return $html;
}

/**
 * Returns an html blob of microdata elements based on what is set in 'Book Information'
 *
 * @return string
 */
function get_microdata_elements() {
	$html = '';
	// map items that are already captured
	$micro_mapping = [
		'about' => 'pb_bisac_subject',
		'alternativeHeadline' => 'pb_subtitle',
		'author' => 'pb_author',
		'contributor' => 'pb_contributing_authors',
		'copyrightHolder' => 'pb_copyright_holder',
		'copyrightYear' => 'pb_copyright_year',
		'datePublished' => 'pb_publication_date',
		'description' => 'pb_about_50',
		'editor' => 'pb_editor',
		'image' => 'pb_cover_image',
		'inLanguage' => 'pb_language',
		'keywords' => 'pb_keywords_tags',
		'publisher' => 'pb_publisher',
		'isBasedOn' => 'pb_is_based_on',
	];
	$metadata = Book::getBookInformation();

	// create microdata elements
	foreach ( $micro_mapping as $itemprop => $content ) {
		if ( array_key_exists( $content, $metadata ) ) {
			if ( 'pb_publication_date' === $content ) {
				$content = date( 'Y-m-d', (int) $metadata[ $content ] );
			} else {
				$content = $metadata[ $content ];
			}
			$html .= "<meta itemprop='" . $itemprop . "' content='" . $content . "' id='" . $itemprop . "'>\n";
		}
	}

	if ( ! array_key_exists( 'pb_copyright_year', $metadata ) && array_key_exists( 'pb_publication_date', $metadata ) ) {
		$itemprop = 'copyrightYear';
		$content = strftime( '%Y', $metadata['pb_publication_date'] );
		$html .= "<meta itemprop='" . $itemprop . "' content='" . $content . "' id='" . $itemprop . "'>\n";
	}

	return $html;
}

/**
 * Takes a known string from metadata, builds a url to hit an api which returns an xml response
 * @see https://api.creativecommons.org/docs/readme_15.html
 *
 * @param string $type license type
 * @param string $copyright_holder of the page
 * @param string $src_url of the page
 * @param string $title of the page
 * @param string $lang (optional)
 *
 * @return string $xml response
 */
function get_license_xml( $type, $copyright_holder, $src_url, $title, $lang = '' ) {
	$endpoint = 'https://api.creativecommons.org/rest/1.5/';
	$lang = ( ! empty( $lang ) ) ? substr( $lang, 0, 2 ) : '';
	$expected = [
		'public-domain' => [
			'license' => 'zero',
			'commercial' => 'y',
			'derivatives' => 'y',
		],
		'cc-by' => [
			'license' => 'standard',
			'commercial' => 'y',
			'derivatives' => 'y',
		],
		'cc-by-sa' => [
			'license' => 'standard',
			'commercial' => 'y',
			'derivatives' => 'sa',
		],
		'cc-by-nd' => [
			'license' => 'standard',
			'commercial' => 'y',
			'derivatives' => 'n',
		],
		'cc-by-nc' => [
			'license' => 'standard',
			'commercial' => 'n',
			'derivatives' => 'y',
		],
		'cc-by-nc-sa' => [
			'license' => 'standard',
			'commercial' => 'n',
			'derivatives' => 'sa',
		],
		'cc-by-nc-nd' => [
			'license' => 'standard',
			'commercial' => 'n',
			'derivatives' => 'n',
		],
		'all-rights-reserved' => [],
	];

	// nothing meaningful to hit the api with, so bail
	if ( ! array_key_exists( $type, $expected ) ) {
		return '';
	}

	switch ( $type ) {
		// api doesn't have an 'all-rights-reserved' endpoint, so manual build necessary
		case 'all-rights-reserved':
			$xml = '<result><html>'
				   . "<span property='dct:title'>" . Sanitize\sanitize_xml_attribute( $title ) . '</span> &#169; '
				   . Sanitize\sanitize_xml_attribute( $copyright_holder ) . '. ' . __( 'All Rights Reserved', 'pressbooks' ) . '.</html></result>';
			break;
		default:

			$key = array_keys( $expected[ $type ] );
			$val = array_values( $expected[ $type ] );

			// build the url
			$url = $endpoint . $key[0] . '/' . $val[0] . '/get?' . $key[1] . '=' . $val[1] . '&' . $key[2] . '=' . $val[2] .
				   '&creator=' . urlencode( $copyright_holder ) . '&attribution_url=' . urlencode( $src_url ) . '&title=' . urlencode( $title ) . '&locale=' . $lang;

			$xml = wp_remote_get( $url );
			$ok = wp_remote_retrieve_response_code( $xml );

			// if server response is not ok
			if ( 200 !== absint( $ok ) ) {
				return '';
			}

			// if remote call went sideways
			if ( ! is_wp_error( $xml ) ) {
				$xml = $xml['body'];

			} else {
				// Something went wrong
				\error_log( '\Pressbooks\Metadata\get_license_xml() error: ' . $xml->get_error_message() );
			}

			break;
	}

	return $xml;
}

/**
 * Returns an HTML blob if given an XML object
 *
 * @param \SimpleXMLElement $response
 *
 * @return string $html blob of copyright information
 */
function get_web_license_html( \SimpleXMLElement $response ) {
	$html = '';

	if ( is_object( $response ) ) {
		$content = $response->asXML();
		$content = trim( str_replace( [ '<p xmlns:dct="http://purl.org/dc/terms/">', '</p>', '<html>', '</html>' ], [ '', '', '', '' ], $content ) );
		$content = preg_replace( '/http:\/\/i.creativecommons/iU', 'https://i.creativecommons', $content );

		$html = '<div class="license-attribution" xmlns:cc="http://creativecommons.org/ns#"><p xmlns:dct="http://purl.org/dc/terms/">' . rtrim( $content, '.' ) . ', ' . __( 'except where otherwise noted.', 'pressbooks' ) . '</p></div>';
	}

	return html_entity_decode( $html, ENT_XHTML, 'UTF-8' );
}

/**
 * Returns URL for saved license value.
 *
 * @since 4.0.0
 *
 * @param string
 *
 * @return string
 */
function get_url_for_license( $license ) {
	switch ( $license ) {
		case 'public-domain':
			$url = 'https://creativecommons.org/publicdomain/zero/1.0/';
			break;
		case 'cc-by':
			$url = 'https://creativecommons.org/licenses/by/4.0/';
			break;
		case 'cc-by-sa':
			$url = 'https://creativecommons.org/licenses/by-sa/4.0/';
			break;
		case 'cc-by-nd':
			$url = 'https://creativecommons.org/licenses/by-nd/4.0/';
			break;
		case 'cc-by-nc':
			$url = 'https://creativecommons.org/licenses/by-nc/4.0/';
			break;
		case 'cc-by-nc-sa':
			$url = 'https://creativecommons.org/licenses/by-nc-sa/4.0/';
			break;
		case 'cc-by-nc-nd':
			$url = 'https://creativecommons.org/licenses/by-nc-nd/4.0/';
			break;
		case 'all-rights-reserved':
			$url = 'https://choosealicense.com/no-license/';
			break;
		default:
			$url = 'https://choosealicense.com/no-license/';
	}

	return $url;
}

/**
 * @param \WP_Post $post
 */
function add_expanded_metadata_box( $post ) {

	if ( $post->post_type !== 'metadata' ) {
		return;
	}

	if ( isset( $_GET['pressbooks_show_expanded_metadata'] ) && check_admin_referer( 'pb-expanded-metadata' ) ) {
		update_option( 'pressbooks_show_expanded_metadata', $_GET['pressbooks_show_expanded_metadata'] );
	}

	$show_expanded_metadata = show_expanded_metadata();
	$has_expanded_metadata = has_expanded_metadata();

	$url = get_edit_post_link( $post->ID );
	if ( $show_expanded_metadata ) {
		$text = __( 'Hide Additional Book Information', 'pressbooks' );
		$href = wp_nonce_url( $url . '&pressbooks_show_expanded_metadata=0', 'pb-expanded-metadata' );
	} else {
		$text = __( 'Show Additional Book Information', 'pressbooks' );
		$href = wp_nonce_url( $url . '&pressbooks_show_expanded_metadata=1', 'pb-expanded-metadata' );
	}

	?>
	<div id="expanded-metadata-panel" class="postbox">
		<div class="inside">
			<p><?php _e( 'The book information you enter here appears on your book’s cover and title pages and in the metadata of your webbook and exported files.', 'pressbooks' ); ?></p>
			<?php if ( ! $show_expanded_metadata && ! $has_expanded_metadata ) { ?>
			<p><?php _e( 'If you need to enter additional information, click the button below to see all available fields.', 'pressbooks' ); ?></p>
			<?php } ?>
			<?php if ( ! $has_expanded_metadata ) { ?>
			<p><a class="button" href="<?php echo $href; ?>"><?php echo $text; ?></a></p>
			<?php } ?>
	</div>
	</div>
	<?php
}

/**
 * Should we show expanded metadata fields or not?
 *
 * @return bool
 */
function show_expanded_metadata() {
	if ( isset( $_GET['pressbooks_show_expanded_metadata'] ) && check_admin_referer( 'pb-expanded-metadata' ) ) {
		if ( ! empty( $_GET['pressbooks_show_expanded_metadata'] ) ) {
			return true;
		} else {
			return false;
		}
	} elseif ( ! empty( get_option( 'pressbooks_show_expanded_metadata' ) ) ) {
		return true;
	} elseif ( has_expanded_metadata() ) {
		update_option( 'pressbooks_show_expanded_metadata', 1 );
		return true;
	}
	return false;
}

/**
 * Is expanded metadata present in this book?
 *
 * @return bool
 */
function has_expanded_metadata() {
	$metadata = Book::getBookInformation();
	$additional_fields = [
		'pb_author_file_as',
		'pb_onsale_date',
		'pb_copyright_year',
		'pb_series_title',
		'pb_series_number',
		'pb_keywords_tags',
		'pb_hashtag',
		'pb_list_price_print',
		'pb_list_price_pdf',
		'pb_list_price_epub',
		'pb_list_price_web',
		'pb_audience',
		'pb_bisac_subject',
		'pb_bisac_regional_theme',
	];
	foreach ( $additional_fields as $field ) {
		if ( isset( $metadata[ $field ] ) && ! empty( $metadata[ $field ] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Convert Pressbooks Book Information to Schema.org-compatible metadata
 *
 * @since 4.1
 *
 * @param array $book_information
 *
 * @return array
 */
function book_information_to_schema( $book_information ) {
	 $new_book_information = [];

	 $new_book_information['@context'] = 'http://schema.org';
	 $new_book_information['@type'] = 'Book';

	 $mapped_properties = [
		 'pb_title' => 'name',
		 'pb_short_title' => 'alternateName',
		 'pb_ebook_isbn' => 'isbn',
		 'pb_keywords_tags' => 'keywords',
		 'pb_subtitle' => 'alternativeHeadline',
		 'pb_language' => 'inLanguage',
		 'pb_copyright_year' => 'copyrightYear',
		 'pb_about_50' => 'description',
		 'pb_cover_image' => 'image',
		 'pb_series_number' => 'position',
		 'pb_is_based_on' => 'isBasedOn',
	 ];

	 foreach ( $mapped_properties as $old => $new ) {
		 if ( isset( $book_information[ $old ] ) ) {
			 $new_book_information[ $new ] = $book_information[ $old ];
		 }
	 }

	 if ( isset( $book_information['pb_bisac_subject'] ) ) {
		 $bisac_subjects = explode( ', ', $book_information['pb_bisac_subject'] );
		 foreach ( $bisac_subjects as $bisac_subject ) {
			 $new_book_information['about'][] = [
				 '@type' => 'Thing',
				 'identifier' => $bisac_subject,
			 ];
		 }
	 }

	 if ( isset( $book_information['pb_author'] ) ) {
		 $new_book_information['author'] = [
			 '@type' => 'Person',
			 'name' => $book_information['pb_author'],
		 ];

		 if ( isset( $book_information['pb_author_file_as'] ) ) {
			 $new_book_information['author']['alternateName'] = $book_information['pb_author_file_as'];
		 }
	 }

	 if ( isset( $book_information['pb_contributing_authors'] ) ) {
		 $contributing_authors = explode( ', ', $book_information['pb_contributing_authors'] );
		 foreach ( $contributing_authors as $contributor ) {
			 $new_book_information['contributor'][] = [
				 '@type' => 'Person',
				 'name' => $contributor,
			 ];
		 }
	 }

	 if ( isset( $book_information['pb_editor'] ) ) {
		 $editors = explode( ', ', $book_information['pb_editor'] );
		 foreach ( $editors as $editor ) {
			 $new_book_information['editor'][] = [
				 '@type' => 'Person',
				 'name' => $editor,
			 ];
		 }
	 }

	 if ( isset( $book_information['pb_translator'] ) ) {
		 $translators = explode( ', ', $book_information['pb_translator'] );
		 foreach ( $translators as $translator ) {
			 $new_book_information['translator'][] = [
				 '@type' => 'Person',
				 'name' => $translator,
			 ];
		 }
	 }

	 if ( isset( $book_information['pb_publisher'] ) ) {
		 $new_book_information['publisher'] = [
			 '@type' => 'Organization',
			 'name' => $book_information['pb_publisher'],
		 ];

		 if ( isset( $book_information['pb_publisher_city'] ) ) {
			 $new_book_information['publisher']['address'] = [
				 '@type' => 'PostalAddress',
				 'addressLocality' => $book_information['pb_publisher_city'],
			 ];
		 }
	 }

	 if ( isset( $book_information['pb_audience'] ) ) {
		 $new_book_information['audience'] = [
			 '@type' => 'Audience',
			 'name' => $book_information['pb_audience'],
		 ];
	 }

	 if ( isset( $book_information['pb_publication_date'] ) ) {
		 $new_book_information['datePublished'] = strftime( '%F', $book_information['pb_publication_date'] );

		 if ( ! isset( $book_information['pb_copyright_year'] ) ) {
			 $new_book_information['copyrightYear'] = strftime( '%Y', $book_information['pb_publication_date'] );
		 }
	 }

	 if ( isset( $book_information['pb_copyright_holder'] ) ) { // TODO: Person or Organization?
		 $new_book_information['copyrightHolder'] = [
			 '@type' => 'Organization',
			 'name' => $book_information['pb_copyright_holder'],
		 ];
	 }

	 if ( ! isset( $book_information['pb_book_license'] ) ) {
		 $book_information['pb_book_license'] = '';
	 }

	 $new_book_information['license'] = get_url_for_license( $book_information['pb_book_license'] );

	 // TODO: educationalAlignment, educationalUse, timeRequired, typicalAgeRange, interactivityType, learningResourceType, isBasedOn, isBasedOnUrl

	 return $new_book_information;
}

/**
 * Convert Pressbooks Section Information to Schema.org-compatible metadata
 *
 * @since 4.1
 *
 * @param array $section_information
 * @param array $book_information
 *
 * @return array
 */
function section_information_to_schema( $section_information, $book_information ) {
	 $new_section_information = [];

	 $new_section_information['@context'] = 'http://bib.schema.org';
	 $new_section_information['@type'] = 'Chapter';

	 $mapped_section_properties = [
		 'pb_title' => 'name',
		 'pb_short_title' => 'alternateName',
		 'pb_subtitle' => 'alternativeHeadline',
		 'pb_is_based_on' => 'isBasedOn',
	 ];

	 $mapped_book_properties = [
		 'pb_language' => 'inLanguage',
		 'pb_copyright_year' => 'copyrightYear',
	 ];

	 foreach ( $mapped_section_properties as $old => $new ) {
		 if ( isset( $section_information[ $old ] ) ) {
			 $new_section_information[ $new ] = $section_information[ $old ];
		 }
	 }

	 foreach ( $mapped_book_properties as $old => $new ) {
		 if ( isset( $book_information[ $old ] ) ) {
			 $new_section_information[ $new ] = $book_information[ $old ];
		 }
	 }

	 if ( ! empty( $section_information['pb_chapter_number'] ) ) {
		 $new_section_information['position'] = $section_information['pb_chapter_number'];
	 }

	 if ( isset( $section_information['pb_section_author'] ) ) {
		 $new_section_information['author'] = [
			 '@type' => 'Person',
			 'name' => $section_information['pb_section_author'],
		 ];
	 } elseif ( isset( $book_information['pb_author'] ) ) {
		 $new_section_information['author'] = [
			 '@type' => 'Person',
			 'name' => $book_information['pb_author'],
		 ];

		 if ( isset( $book_information['pb_author_file_as'] ) ) {
			 $new_section_information['author']['alternateName'] = $book_information['pb_author_file_as'];
		 }
	 }

	 if ( isset( $book_information['pb_contributing_authors'] ) ) {
		 $contributing_authors = explode( ', ', $book_information['pb_contributing_authors'] );
		 foreach ( $contributing_authors as $contributor ) {
			 $new_section_information['contributor'][] = [
				 '@type' => 'Person',
				 'name' => $contributor,
			 ];
		 }
	 }

	 if ( isset( $book_information['pb_editor'] ) ) {
		 $editors = explode( ', ', $book_information['pb_editor'] );
		 foreach ( $editors as $editor ) {
			 $new_section_information['editor'][] = [
				 '@type' => 'Person',
				 'name' => $editor,
			 ];
		 }
	 }

	 if ( isset( $book_information['pb_translator'] ) ) {
		 $translators = explode( ', ', $book_information['pb_translator'] );
		 foreach ( $translators as $translator ) {
			 $new_section_information['translator'][] = [
				 '@type' => 'Person',
				 'name' => $translator,
			 ];
		 }
	 }

	 if ( isset( $book_information['pb_audience'] ) ) {
		 $new_section_information['audience'] = [
			 '@type' => 'Audience',
			 'name' => $book_information['pb_audience'],
		 ];
	 }

	 if ( isset( $book_information['pb_publisher'] ) ) {
		 $new_section_information['publisher'] = [
			 '@type' => 'Organization',
			 'name' => $book_information['pb_publisher'],
		 ];

		 if ( isset( $book_information['pb_publisher_city'] ) ) {
			 $new_section_information['publisher']['address'] = [
				 '@type' => 'PostalAddress',
				 'addressLocality' => $book_information['pb_publisher_city'],
			 ];
		 }
	 }

	 if ( isset( $book_information['pb_publication_date'] ) ) {
		 $new_section_information['datePublished'] = strftime( '%F', $book_information['pb_publication_date'] );
		 if ( ! isset( $book_information['pb_copyright_year'] ) ) {
			 $new_section_information['copyrightYear'] = strftime( '%Y', $book_information['pb_publication_date'] );
		 }
	 }

	 if ( isset( $book_information['pb_copyright_holder'] ) ) { // TODO: Person or Organization?
		 $new_section_information['copyrightHolder'] = [
			 '@type' => 'Organization',
			 'name' => $book_information['pb_copyright_holder'],
		 ];
	 }

	 if ( ! isset( $section_information['pb_section_license'] ) ) {
		 if ( isset( $book_information['pb_license'] ) ) {
			 $section_information['pb_section_license'] = $book_information['pb_license'];
		 } else {
			 $section_information['pb_section_license'] = '';
		 }
	 }

	 $new_section_information['license'] = \Pressbooks\Metadata\get_url_for_license( $section_information['pb_section_license'] );

	 if ( ! isset( $section_information['pb_is_based_on'] ) && isset( $book_information['pb_is_based_on'] ) ) {
		 $new_section_information['isBasedOn'] = $book_information['pb_is_based_on'];
	 }

	 // TODO: educationalAlignment, educationalUse, timeRequired, typicalAgeRange, interactivityType, learningResourceType, isBasedOn, isBasedOnUrl

	 return $new_section_information;
}
