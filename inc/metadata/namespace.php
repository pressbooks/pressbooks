<?php

namespace Pressbooks\Metadata;

use Pressbooks\Book;
use Pressbooks\Licensing;
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
	 $book_schema = [];

	 $book_schema['@context'] = 'http://schema.org';
	 $book_schema['@type'] = 'Book';

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
			 $book_schema[ $new ] = $book_information[ $old ];
			}
		}

		if ( isset( $book_information['pb_bisac_subject'] ) ) {
			$bisac_subjects = explode( ', ', $book_information['pb_bisac_subject'] );
			foreach ( $bisac_subjects as $bisac_subject ) {
				$book_schema['about'][] = [
				 '@type' => 'Thing',
				 'identifier' => $bisac_subject,
				];
			}
		}

		if ( isset( $book_information['pb_author'] ) ) {
			$book_schema['author'] = [
			 '@type' => 'Person',
			 'name' => $book_information['pb_author'],
			];

			if ( isset( $book_information['pb_author_file_as'] ) ) {
				$book_schema['author']['alternateName'] = $book_information['pb_author_file_as'];
			}
		}

		if ( isset( $book_information['pb_contributing_authors'] ) ) {
			$contributing_authors = explode( ', ', $book_information['pb_contributing_authors'] );
			foreach ( $contributing_authors as $contributor ) {
				$book_schema['contributor'][] = [
				 '@type' => 'Person',
				 'name' => $contributor,
				];
			}
		}

		if ( isset( $book_information['pb_editor'] ) ) {
			$editors = explode( ', ', $book_information['pb_editor'] );
			foreach ( $editors as $editor ) {
				$book_schema['editor'][] = [
				 '@type' => 'Person',
				 'name' => $editor,
				];
			}
		}

		if ( isset( $book_information['pb_translator'] ) ) {
			$translators = explode( ', ', $book_information['pb_translator'] );
			foreach ( $translators as $translator ) {
				$book_schema['translator'][] = [
				 '@type' => 'Person',
				 'name' => $translator,
				];
			}
		}

		if ( isset( $book_information['pb_publisher'] ) ) {
			$book_schema['publisher'] = [
			 '@type' => 'Organization',
			 'name' => $book_information['pb_publisher'],
			];

			if ( isset( $book_information['pb_publisher_city'] ) ) {
				$book_schema['publisher']['address'] = [
				 '@type' => 'PostalAddress',
				 'addressLocality' => $book_information['pb_publisher_city'],
				];
			}
		}

		if ( isset( $book_information['pb_audience'] ) ) {
			$book_schema['audience'] = [
			 '@type' => 'Audience',
			 'name' => $book_information['pb_audience'],
			];
		}

		if ( isset( $book_information['pb_publication_date'] ) ) {
			$book_schema['datePublished'] = strftime( '%F', $book_information['pb_publication_date'] );

			if ( ! isset( $book_information['pb_copyright_year'] ) ) {
				$book_schema['copyrightYear'] = strftime( '%Y', $book_information['pb_publication_date'] );
			}
		}

		if ( isset( $book_information['pb_copyright_holder'] ) ) { // TODO: Person or Organization?
			$book_schema['copyrightHolder'] = [
			 '@type' => 'Organization',
			 'name' => $book_information['pb_copyright_holder'],
			];
		}

		if ( ! isset( $book_information['pb_book_license'] ) ) {
			$book_information['pb_book_license'] = '';
		}

		$licensing = new Licensing;
		$book_schema['license'] = $licensing->getUrlForLicense( $book_information['pb_book_license'] );

		// TODO: educationalAlignment, educationalUse, timeRequired, typicalAgeRange, interactivityType, learningResourceType, isBasedOn, isBasedOnUrl

		return $book_schema;
}

/**
 * Convert book Schema.org metadata to Pressbooks Book Information
 *
 * @since 4.1
 *
 * @param array $schema
 *
 * @return array
 */
function schema_to_book_information( $book_schema ) {
	$book_information = [];

	if ( isset( $book_schema['description'] ) ) {
		$book_schema['description'] = html_entity_decode( $book_schema['description'] );
	}

	$mapped_properties = [
		'name' => 'pb_title',
		'alternateName' => 'pb_short_title',
		'isbn' => 'pb_ebook_isbn',
		'keywords' => 'pb_keywords_tags',
		'alternativeHeadline' => 'pb_subtitle',
		'inLanguage' => 'pb_language',
		'copyrightYear' => 'pb_copyright_year',
		'description' => 'pb_about_50',
		'image' => 'pb_cover_image',
		'position' => 'pb_series_number',
		'isBasedOn' => 'pb_is_based_on',
	];

	foreach ( $mapped_properties as $old => $new ) {
		if ( isset( $book_schema[ $old ] ) ) {
			$book_information[ $new ] = $book_schema[ $old ];
		}
	}

	if ( isset( $book_schema['about'] ) ) {
		$bisac_subjects = [];
		foreach ( $book_schema['about'] as $bisac_subject ) {
			$bisac_subjects[] = $bisac_subject['identifier'];
		}
		$book_information['pb_bisac_subject'] = implode( ', ', $bisac_subjects );
	}

	if ( isset( $book_schema['author'] ) ) {
		$book_information['pb_author'] = $book_schema['author']['name'];
		if ( isset( $book_schema['author']['alternateName'] ) ) {
			$book_information['pb_author_file_as'] = $book_schema['author']['alternateName'];
		}
	}

	if ( isset( $book_schema['contributor'] ) ) {
		$contributors = [];
		foreach ( $book_schema['contributor'] as $contributor ) {
			$contributors[] = $contributor['name'];
		}
		$book_information['pb_contributing_authors'] = implode( ', ', $contributors );
	}

	if ( isset( $book_schema['editor'] ) ) {
		$editors = [];
		foreach ( $book_schema['editor'] as $editor ) {
			$editors[] = $editor['name'];
		}
		$book_information['pb_editor'] = implode( ', ', $editors );
	}

	if ( isset( $book_schema['translator'] ) ) {
		$translators = [];
		foreach ( $book_schema['translator'] as $translator ) {
			$translators[] = $translator['name'];
		}
		$book_information['pb_translator'] = implode( ', ', $translators );
	}

	if ( isset( $book_schema['publisher'] ) ) {
		$book_information['pb_publisher'] = $book_schema['publisher']['name'];

		if ( isset( $book_schema['publisher']['address'] ) ) {
			$book_information['pb_publisher_city'] = $book_schema['publisher']['address']['addressLocality'];
		}
	}

	if ( isset( $book_schema['audience'] ) ) {
		$book_information['pb_audience'] = $book_schema['audience']['name'];
	}

	if ( isset( $book_schema['datePublished'] ) ) {
		$book_information['pb_publication_date'] = strtotime( $book_schema['datePublished'] );
	}

	if ( isset( $book_schema['copyrightHolder'] ) ) {
		$book_information['pb_copyright_holder'] = $book_schema['copyrightHolder']['name'];
	}

	$licensing = new Licensing;
	$book_information['pb_book_license'] = $licensing->getLicenseFromUrl( $book_schema['license'] );

	return $book_information;
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
	 $section_schema = [];

	 $section_schema['@context'] = 'http://bib.schema.org';
	 $section_schema['@type'] = 'Chapter';

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
			 $section_schema[ $new ] = $section_information[ $old ];
			}
		}

		foreach ( $mapped_book_properties as $old => $new ) {
			if ( isset( $book_information[ $old ] ) ) {
				$section_schema[ $new ] = $book_information[ $old ];
			}
		}

		if ( ! empty( $section_information['pb_chapter_number'] ) ) {
			$section_schema['position'] = $section_information['pb_chapter_number'];
		}

		if ( isset( $section_information['pb_section_author'] ) ) {
			$section_schema['author'] = [
			 '@type' => 'Person',
			 'name' => $section_information['pb_section_author'],
			];
		} elseif ( isset( $book_information['pb_author'] ) ) {
			$section_schema['author'] = [
			 '@type' => 'Person',
			 'name' => $book_information['pb_author'],
			];

			if ( isset( $book_information['pb_author_file_as'] ) ) {
				$section_schema['author']['alternateName'] = $book_information['pb_author_file_as'];
			}
		}

		if ( isset( $book_information['pb_contributing_authors'] ) ) {
			$contributing_authors = explode( ', ', $book_information['pb_contributing_authors'] );
			foreach ( $contributing_authors as $contributor ) {
				$section_schema['contributor'][] = [
				 '@type' => 'Person',
				 'name' => $contributor,
				];
			}
		}

		if ( isset( $book_information['pb_editor'] ) ) {
			$editors = explode( ', ', $book_information['pb_editor'] );
			foreach ( $editors as $editor ) {
				$section_schema['editor'][] = [
				 '@type' => 'Person',
				 'name' => $editor,
				];
			}
		}

		if ( isset( $book_information['pb_translator'] ) ) {
			$translators = explode( ', ', $book_information['pb_translator'] );
			foreach ( $translators as $translator ) {
				$section_schema['translator'][] = [
				 '@type' => 'Person',
				 'name' => $translator,
				];
			}
		}

		if ( isset( $book_information['pb_audience'] ) ) {
			$section_schema['audience'] = [
			 '@type' => 'Audience',
			 'name' => $book_information['pb_audience'],
			];
		}

		if ( isset( $book_information['pb_publisher'] ) ) {
			$section_schema['publisher'] = [
			 '@type' => 'Organization',
			 'name' => $book_information['pb_publisher'],
			];

			if ( isset( $book_information['pb_publisher_city'] ) ) {
				$section_schema['publisher']['address'] = [
				 '@type' => 'PostalAddress',
				 'addressLocality' => $book_information['pb_publisher_city'],
				];
			}
		}

		if ( isset( $book_information['pb_publication_date'] ) ) {
			$section_schema['datePublished'] = strftime( '%F', $book_information['pb_publication_date'] );
			if ( ! isset( $book_information['pb_copyright_year'] ) ) {
				$section_schema['copyrightYear'] = strftime( '%Y', $book_information['pb_publication_date'] );
			}
		}

		if ( isset( $book_information['pb_copyright_holder'] ) ) { // TODO: Person or Organization?
			$section_schema['copyrightHolder'] = [
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

		$licensing = new Licensing;
		$section_schema['license'] = $licensing->getUrlForLicense( $section_information['pb_section_license'] );

		if ( ! isset( $section_information['pb_is_based_on'] ) && isset( $book_information['pb_is_based_on'] ) ) {
			$section_schema['isBasedOn'] = $book_information['pb_is_based_on'];
		}

		// TODO: educationalAlignment, educationalUse, timeRequired, typicalAgeRange, interactivityType, learningResourceType, isBasedOn, isBasedOnUrl

		return $section_schema;
}

/**
 * Convert section Schema.org metadata to Pressbooks Section Information
 *
 * @since 4.1
 *
 * @param array $section_schema
 * @param array $book_schema
 *
 * @return array
 */
function schema_to_section_information( $section_schema, $book_schema ) {
	$section_information = [];

	$mapped_section_properties = [
		'alternateName' => 'pb_short_title',
		'alternativeHeadline' => 'pb_subtitle',
	];

	foreach ( $mapped_section_properties as $old => $new ) {
		if ( isset( $section_schema[ $old ] ) ) {
			$section_information[ $new ] = $section_schema[ $old ];
		}
	}

	if ( $section_schema['author']['name'] !== $book_schema['author']['name'] ) {
		$section_information['pb_section_author'] = $section_schema['author']['name'];
	}

	if ( $section_schema['license'] !== $book_schema['license'] ) {
		$licensing = new Licensing;
		$section_information['pb_section_license'] = $licensing->getLicenseFromUrl( $section_schema['license'] );
	}

	if ( isset( $section_schema['isBasedOn'] ) && $section_schema['isBasedOn'] !== $book_schema['isBasedOn'] ) {
		$section_information['pb_is_based_on'] = $section_schema['isBasedOn'];
	}

	return $section_information;
}
