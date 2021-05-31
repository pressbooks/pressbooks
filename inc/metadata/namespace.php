<?php

namespace Pressbooks\Metadata;

use function \Pressbooks\L10n\get_book_language;
use function \Pressbooks\L10n\get_locale;
use function \Pressbooks\Sanitize\is_valid_timestamp;
use function \Pressbooks\Utility\get_contents;
use function \Pressbooks\Utility\is_assoc;
use function \Pressbooks\Utility\oxford_comma;
use function \Pressbooks\Utility\oxford_comma_explode;
use Pressbooks\Book;
use Pressbooks\Licensing;
use Pressbooks\Metadata;

/**
 * Returns an html blob of meta elements based on what is set in 'Book Information'
 *
 * @deprecated 5.7.0
 *
 * @return string
 */
function get_seo_meta_elements() {
	// map items that are already captured
	$meta_mapping = [
		'author' => 'pb_authors',
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
 * @deprecated 5.7.0
 *
 * @return string
 */
function get_microdata_elements() {
	$html = '';
	// map items that are already captured
	$micro_mapping = [
		'about' => 'pb_bisac_subject',
		'alternativeHeadline' => 'pb_subtitle',
		'author' => 'pb_authors',
		'contributor' => 'pb_contributors',
		'copyrightHolder' => 'pb_copyright_holder',
		'copyrightYear' => 'pb_copyright_year',
		'datePublished' => 'pb_publication_date',
		'description' => 'pb_about_50',
		'editor' => 'pb_editors',
		'image' => 'pb_cover_image',
		'thumbnailUrl' => 'pb_thumbnail',
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

	if ( ! array_key_exists( 'pb_copyright_year', $metadata ) && array_key_exists( 'pb_publication_date', $metadata ) && is_valid_timestamp( $metadata['pb_publication_date'] ) ) {
		$itemprop = 'copyrightYear';
		$content = strftime( '%Y', (int) $metadata['pb_publication_date'] );
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
 * @param bool  $network_excluded_directory
 *
 * @return array
 */
function book_information_to_schema( $book_information, $network_excluded_directory = false ) {
	$book_schema = [];

	$book_schema['@context'] = 'http://schema.org';
	$book_schema['@type'] = 'Book';

	$mapped_properties = [
		'pb_title' => 'name',
		'pb_short_title' => 'alternateName',
		'pb_ebook_isbn' => 'isbn',
		'pb_keywords_tags' => 'keywords',
		'pb_subtitle' => 'alternativeHeadline',
		'pb_subject' => 'genre',
		'pb_language' => 'inLanguage',
		'pb_copyright_year' => 'copyrightYear',
		'pb_about_50' => 'disambiguatingDescription',
		'pb_about_unlimited' => 'description',
		'pb_cover_image' => 'image',
		'pb_thumbnail' => 'thumbnailUrl',
		'pb_series_number' => 'position',
		'pb_is_based_on' => 'isBasedOn',
		'pb_word_count' => 'wordCount',
		'pb_storage_size' => 'storageSize',
		'pb_h5p_activities' => 'h5pActivities',
		'pb_in_catalog' => 'inCatalog',
		'pb_book_directory_excluded' => 'bookDirectoryExcluded',
	];

	foreach ( $mapped_properties as $old => $new ) {
		if ( isset( $book_information[ $old ] ) ) {
			$book_schema[ $new ] = $book_information[ $old ];
		}
	}

	if ( isset( $book_information['pb_primary_subject'] ) ) {
		$book_schema['about'][] = [
			'@type' => 'Thing',
			'identifier' => $book_information['pb_primary_subject'],
			'name' => Metadata\get_subject_from_thema( $book_information['pb_primary_subject'] ),
		];
	}

	if ( isset( $book_information['pb_additional_subjects'] ) ) {
		$additional_subjects = explode( ', ', $book_information['pb_additional_subjects'] );
		foreach ( $additional_subjects as $additional_subject ) {
			$name = Metadata\get_subject_from_thema( $additional_subject );
			$book_schema['about'][] = [
				'@type' => 'Thing',
				'identifier' => $additional_subject,
				'name' => ( is_null( $name ) || ! $name ) ? $additional_subject : $name,
			];
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

	if ( isset( $book_information['pb_authors'] ) ) {
		$authors = oxford_comma_explode( $book_information['pb_authors'] );
		foreach ( $authors as $author ) {
			$book_schema['author'][] = [
				'@type' => 'Person',
				'name' => $author,
			];
		}
	}

	if ( isset( $book_information['pb_editors'] ) ) {
		$editors = oxford_comma_explode( $book_information['pb_editors'] );
		foreach ( $editors as $editor ) {
			$book_schema['editor'][] = [
				'@type' => 'Person',
				'name' => $editor,
			];
		}
	}

	if ( isset( $book_information['pb_translators'] ) ) {
		$translators = oxford_comma_explode( $book_information['pb_translators'] );
		foreach ( $translators as $translator ) {
			$book_schema['translator'][] = [
				'@type' => 'Person',
				'name' => $translator,
			];
		}
	}

	if ( isset( $book_information['pb_reviewers'] ) ) {
		$reviewers = oxford_comma_explode( $book_information['pb_reviewers'] );
		foreach ( $reviewers as $reviewer ) {
			$book_schema['reviewedBy'][] = [
				'@type' => 'Person',
				'name' => $reviewer,
			];
		}
	}

	if ( isset( $book_information['pb_illustrators'] ) ) {
		$illustrators = oxford_comma_explode( $book_information['pb_illustrators'] );
		foreach ( $illustrators as $illustrator ) {
			$book_schema['illustrator'][] = [
				'@type' => 'Person',
				'name' => $illustrator,
			];
		}
	}

	if ( isset( $book_information['pb_contributors'] ) ) {
		$contributing_authors = oxford_comma_explode( $book_information['pb_contributors'] );
		foreach ( $contributing_authors as $contributor ) {
			$book_schema['contributor'][] = [
				'@type' => 'Person',
				'name' => $contributor,
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

	if ( isset( $book_information['pb_publication_date'] ) && is_valid_timestamp( $book_information['pb_publication_date'] ) ) {
		$book_schema['datePublished'] = strftime( '%F', (int) $book_information['pb_publication_date'] );

		if ( ! isset( $book_information['pb_copyright_year'] ) ) {
			$book_schema['copyrightYear'] = strftime( '%Y', (int) $book_information['pb_publication_date'] );
		}
	}

	if ( isset( $book_information['pb_copyright_holder'] ) ) { // TODO: Person or Organization?
		$book_schema['copyrightHolder'] = [
			'@type' => 'Organization',
			'name' => $book_information['pb_copyright_holder'],
		];
	}

	if ( ! isset( $book_information['pb_book_license'] ) ) {
		$book_information['pb_book_license'] = 'all-rights-reserved';
	}

	$licensing = new Licensing;
	$supported_types = $licensing->getSupportedTypes();
	$book_schema['license'] = [
		'@type' => 'CreativeWork',
		'url' => $licensing->getUrlForLicense( $book_information['pb_book_license'] ),
		'name' => $supported_types[ $book_information['pb_book_license'] ]['desc'] ?? 'all-rights-reserved',
		'code' => $supported_types[ $book_information['pb_book_license'] ]['abbreviation'] ?? 'All Rights Reserved',
	];
	if ( isset( $book_information['pb_custom_copyright'] ) ) {
		$book_schema['license']['description'] = $book_information['pb_custom_copyright'];
	}

	if ( isset( $book_information['pb_book_doi'] ) ) {
		$book_schema['identifier'] = [
			'@type' => 'PropertyValue',
			'propertyID' => 'DOI',
			'value' => $book_information['pb_book_doi'],
		];
		/**
		 * Filter the DOI resolver service URL (default: https://dx.doi.org).
		 *
		 * @since 5.6.0
		 */
		$doi_resolver = apply_filters( 'pb_doi_resolver', 'https://dx.doi.org' );
		$book_schema['sameAs'] = trailingslashit( $doi_resolver ) . $book_information['pb_book_doi'];
	}

	if ( isset( $book_information['pb_word_count'] ) ) {
		$book_schema['wordCount'] = intval( $book_information['pb_word_count'] );
	}

	if ( isset( $book_information['pb_storage_size'] ) ) {
		$book_schema['storageSize'] = intval( $book_information['pb_storage_size'] );
	}

	if ( isset( $book_information['pb_h5p_activities'] ) ) {
		$book_schema['h5pActivities'] = intval( $book_information['pb_h5p_activities'] );
	}

	if ( isset( $book_information['pb_in_catalog'] ) ) {
		$book_schema['inCatalog'] = $book_information['pb_in_catalog'] === '1';
	}

	if ( true === $network_excluded_directory ) {
		$book_schema['bookDirectoryExcluded'] = $network_excluded_directory && ! $book_schema['inCatalog'];
	} elseif ( isset( $book_schema['bookDirectoryExcluded'] ) ) {
		$book_schema['bookDirectoryExcluded'] = (bool) $book_information['pb_book_directory_excluded'];
	} else {
		$book_schema['bookDirectoryExcluded'] = false;
	}

	if ( isset( $book_information['last_updated'] ) ) {
		$book_schema['lastUpdated'] = $book_information['last_updated'];
	}

	if ( isset( $book_information['pb_language'] ) ) {
		$languages = \Pressbooks\L10n\supported_languages();
		$language = ( array_key_exists( $book_information['pb_language'], $languages ) ) ?
			$languages[ $book_information['pb_language'] ] : 'Unavailable code';
		$book_schema['language'] = [
			'@type' => 'Language',
			'code' => $book_information['pb_language'],
			'name' => $language,
		];
	}

	if ( isset( $book_information['site_name'] ) ) {
		$book_schema['network'] = [
			'@type' => 'Network',
			'host' => wp_parse_url( network_home_url(), PHP_URL_HOST ),
			'name' => $book_information['site_name'],
		];
	}

	// TODO: educationalAlignment, educationalUse, timeRequired, typicalAgeRange, interactivityType, learningResourceType, isBasedOnUrl

	return $book_schema;
}

/**
 * Convert book Schema.org metadata to Pressbooks Book Information
 *
 * @since 4.1
 *
 * @param array $book_schema
 *
 * @return array
 */
function schema_to_book_information( $book_schema ) {
	$book_information = [];

	if ( isset( $book_schema['description'] ) ) {
		$book_schema['description'] = html_entity_decode( $book_schema['description'] );
	}

	// Values expected to be text
	$mapped_properties = [
		'name' => 'pb_title',
		'alternateName' => 'pb_short_title',
		'isbn' => 'pb_ebook_isbn',
		'keywords' => 'pb_keywords_tags',
		'alternativeHeadline' => 'pb_subtitle',
		'genre' => 'pb_subject',
		'inLanguage' => 'pb_language',
		'copyrightYear' => 'pb_copyright_year',
		'disambiguatingDescription' => 'pb_about_50',
		'description' => 'pb_about_unlimited',
		'image' => 'pb_cover_image',
		'thumbnailUrl' => 'pb_thumbnail',
		'position' => 'pb_series_number',
		'isBasedOn' => 'pb_is_based_on',
	];

	foreach ( $mapped_properties as $old => $new ) {
		if ( isset( $book_schema[ $old ] ) ) {
			$book_information[ $new ] = $book_schema[ $old ];
		}
	}

	if ( isset( $book_schema['about'] ) ) {
		$subjects = [];
		$bisac_subjects = [];
		foreach ( $book_schema['about'] as $subject ) {
			if ( is_bisac( $subject['identifier'] ) ) {
				$bisac_subjects[] = $subject['identifier'];
			} else {
				$subjects[] = $subject['identifier'];
			}
		}
		$book_information['pb_primary_subject'] = array_shift( $subjects );
		$book_information['pb_additional_subjects'] = implode( ', ', $subjects );
		$book_information['pb_bisac_subject'] = implode( ', ', $bisac_subjects );
	}

	if ( isset( $book_schema['author'] ) ) {
		// Pressbooks 5
		$authors = [];
		foreach ( $book_schema['author'] as $author ) {
			if ( isset( $author['name'] ) ) {
				$authors[] = $author['name'];
			}
		}
		if ( empty( $authors ) && isset( $book_schema['author']['name'] ) ) {
			// Pressbooks 4
			$authors[] = $book_schema['author']['name']; // Backwards compatibility with Pressbooks 4
			if ( isset( $book_schema['author']['alternateName'] ) ) {
				$book_information['pb_author_file_as'] = $book_schema['author']['alternateName'];
			}
		} else {
			$book_information['pb_author'] = implode( ', ', $authors );
		}
		$book_information['pb_authors'] = oxford_comma( $authors );
	}

	if ( isset( $book_schema['editor'] ) ) {
		$editors = [];
		foreach ( $book_schema['editor'] as $editor ) {
			$editors[] = $editor['name'];
		}
		$book_information['pb_editors'] = oxford_comma( $editors );
	}

	if ( isset( $book_schema['translator'] ) ) {
		$translators = [];
		foreach ( $book_schema['translator'] as $translator ) {
			$translators[] = $translator['name'];
		}
		$book_information['pb_translators'] = oxford_comma( $translators );
	}

	if ( isset( $book_schema['reviewedBy'] ) ) {
		$reviewers = [];
		foreach ( $book_schema['reviewedBy'] as $reviewer ) {
			$reviewers[] = $reviewer['name'];
		}
		$book_information['pb_reviewers'] = oxford_comma( $reviewers );
	}

	if ( isset( $book_schema['illustrator'] ) ) {
		$illustrators = [];
		foreach ( $book_schema['illustrator'] as $illustrator ) {
			$illustrators[] = $illustrator['name'];
		}
		$book_information['pb_illustrators'] = oxford_comma( $illustrators );
	}

	if ( isset( $book_schema['contributor'] ) ) {
		$contributors = [];
		foreach ( $book_schema['contributor'] as $contributor ) {
			$contributors[] = $contributor['name'];
		}
		$book_information['pb_contributors'] = oxford_comma( $contributors );
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
	if ( is_array( $book_schema['license'] ) ) {
		$book_information['pb_book_license'] = $licensing->getLicenseFromUrl( $book_schema['license']['url'] );
		if ( isset( $book_schema['license']['description'] ) ) {
			$book_information['pb_custom_copyright'] = $book_schema['license']['description'];
		}
	} else {
		$book_information['pb_book_license'] = $licensing->getLicenseFromUrl( $book_schema['license'] );
	}

	if ( isset( $book_schema['sameAs'] ) ) {
		/**
		 * Filter the DOI resolver service URL (default: https://dx.doi.org).
		 *
		 * @since 5.6.0
		 */
		$doi_resolver = apply_filters( 'pb_doi_resolver', 'https://dx.doi.org' );
		$book_information['pb_book_doi'] = str_replace( trailingslashit( $doi_resolver ), '', $book_schema['sameAs'] );
	}

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

	$section_schema['@context'] = 'http://schema.org';
	$section_schema['@type'] = 'Chapter';

	$mapped_section_properties = [
		'pb_title' => 'name',
		'pb_short_title' => 'alternateName',
		'pb_subtitle' => 'alternativeHeadline',
		'pb_is_based_on' => 'isBasedOn',
	];

	$mapped_book_properties = [
		'pb_language' => 'inLanguage',
		'pb_title' => 'isPartOf',
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

	// Use section, if missing use book
	$authors = [];
	if ( ! empty( $section_information['pb_authors'] ) ) {
		$authors = oxford_comma_explode( $section_information['pb_authors'] );
	} elseif ( ! empty( $book_information['pb_authors'] ) ) {
		$authors = oxford_comma_explode( $book_information['pb_authors'] );
	}
	foreach ( $authors as $author ) {
		$section_schema['author'][] = [
			'@type' => 'Person',
			'name' => $author,
		];
	}

	if ( isset( $book_information['pb_editors'] ) ) {
		$editors = oxford_comma_explode( $book_information['pb_editors'] );
		foreach ( $editors as $editor ) {
			$section_schema['editor'][] = [
				'@type' => 'Person',
				'name' => $editor,
			];
		}
	}

	if ( isset( $book_information['pb_translators'] ) ) {
		$translators = oxford_comma_explode( $book_information['pb_translators'] );
		foreach ( $translators as $translator ) {
			$section_schema['translator'][] = [
				'@type' => 'Person',
				'name' => $translator,
			];
		}
	}

	if ( isset( $book_information['pb_reviewers'] ) ) {
		$reviewers = oxford_comma_explode( $book_information['pb_reviewers'] );
		foreach ( $reviewers as $reviewer ) {
			$section_schema['reviewedBy'][] = [
				'@type' => 'Person',
				'name' => $reviewer,
			];
		}
	}

	if ( isset( $book_information['pb_illustrators'] ) ) {
		$illustrators = oxford_comma_explode( $book_information['pb_illustrators'] );
		foreach ( $illustrators as $illustrator ) {
			$section_schema['illustrator'][] = [
				'@type' => 'Person',
				'name' => $illustrator,
			];
		}
	}

	if ( isset( $book_information['pb_contributors'] ) ) {
		$contributing_authors = oxford_comma_explode( $book_information['pb_contributors'] );
		foreach ( $contributing_authors as $contributor ) {
			$section_schema['contributor'][] = [
				'@type' => 'Person',
				'name' => $contributor,
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

	if ( isset( $book_information['pb_publication_date'] ) && is_valid_timestamp( $book_information['pb_publication_date'] ) ) {
		$section_schema['datePublished'] = strftime( '%F', (int) $book_information['pb_publication_date'] );
		if ( ! isset( $book_information['pb_copyright_year'] ) ) {
			$section_schema['copyrightYear'] = strftime( '%Y', (int) $book_information['pb_publication_date'] );
		}
	}

	if ( isset( $book_information['pb_copyright_holder'] ) ) { // TODO: Person or Organization?
		$section_schema['copyrightHolder'] = [
			'@type' => 'Organization',
			'name' => $book_information['pb_copyright_holder'],
		];
	}

	if ( empty( $section_information['pb_section_license'] ) ) {
		if ( ! empty( $book_information['pb_book_license'] ) ) {
			$section_information['pb_section_license'] = $book_information['pb_book_license'];
		} else {
			$section_information['pb_section_license'] = 'all-rights-reserved';
		}
	}

	$licensing = new Licensing;

	if ( ! $licensing->isSupportedType( $section_information['pb_section_license'] ) ) {
		$section_information['pb_section_license'] = 'all-rights-reserved';
	}

	$section_schema['license'] = [
		'@type' => 'CreativeWork',
		'url' => $licensing->getUrlForLicense( $section_information['pb_section_license'] ),
		'name' => $licensing->getSupportedTypes()[ $section_information['pb_section_license'] ]['desc'] ?? 'all-rights-reserved',
	];

	if ( ! isset( $section_information['pb_is_based_on'] ) && isset( $book_information['pb_is_based_on'] ) ) {
		$section_schema['isBasedOn'] = $book_information['pb_is_based_on'];
	}

	if ( isset( $section_information['pb_section_doi'] ) ) {
		$section_schema['identifier'] = [
			'@type' => 'PropertyValue',
			'propertyID' => 'DOI',
			'value' => $section_information['pb_section_doi'],
		];
		/**
		 * Filter the DOI resolver service URL (default: https://dx.doi.org).
		 *
		 * @since 5.6.0
		 */
		$doi_resolver = apply_filters( 'pb_doi_resolver', 'https://dx.doi.org' );
		$section_schema['sameAs'] = trailingslashit( $doi_resolver ) . $section_information['pb_section_doi'];
	}

	// TODO: educationalAlignment, educationalUse, timeRequired, typicalAgeRange, interactivityType, learningResourceType, isBasedOnUrl

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

	// Authors
	if ( isset( $section_schema['author'], $book_schema['author'] ) ) {
		$book_authors = [];
		if ( is_assoc( $book_schema['author'] ) ) {
			$book_schema['author'] = [ $book_schema['author'] ];
		}
		foreach ( $book_schema['author'] as $book_author ) {
			if ( isset( $book_author['name'] ) ) {
				$book_authors[] = $book_author['name'];
			}
		}
		$section_authors = [];
		if ( is_assoc( $section_schema['author'] ) ) {
			$section_schema['author'] = [ $section_schema['author'] ];
		}
		foreach ( $section_schema['author'] as $section_author ) {
			if ( isset( $section_author['name'] ) ) {
				$section_authors[] = $section_author['name'];
			}
		}
		if ( $section_authors !== $book_authors ) {
			$section_information['pb_authors'] = oxford_comma( $section_authors );
		}
	}

	// License
	$book_license = '';
	$section_license = '';
	if ( isset( $book_schema['license'] ) ) {
		if ( is_array( $book_schema['license'] ) ) {
			$book_license = $book_schema['license']['url'];
		} else {
			$book_license = $book_schema['license'];
		}
	}
	if ( isset( $section_schema['license'] ) ) {
		if ( is_array( $section_schema['license'] ) ) {
			$section_license = $section_schema['license']['url'];
		} else {
			$section_license = $section_schema['license'];
		}
	}
	if ( $section_license !== $book_license ) {
		$licensing = new Licensing;
		$section_information['pb_section_license'] = $licensing->getLicenseFromUrl( $section_license );
	}

	// Version Tracking
	if ( isset( $section_schema['isBasedOn'] ) ) {
		if ( empty( $book_schema['isBasedOn'] ) || $section_schema['isBasedOn'] !== $book_schema['isBasedOn'] ) {
			$section_information['pb_is_based_on'] = $section_schema['isBasedOn'];
		}
	}

	if ( isset( $section_schema['sameAs'] ) ) {
		/**
		 * Filter the DOI resolver service URL (default: https://dx.doi.org).
		 *
		 * @since 5.6.0
		 */
		$doi_resolver = apply_filters( 'pb_doi_resolver', 'https://dx.doi.org' );
		$section_information['pb_section_doi'] = str_replace( trailingslashit( $doi_resolver ), '', $section_schema['sameAs'] );
	}

	return $section_information;
}

/**
 * @return mixed|void
 */
function get_book_metadata_lang() {
	if ( Book::isBook() ) {
		$locale = get_book_language();
	} else {
		$locale = substr( get_locale(), 0, 2 );
	}
	/**
	 * @since  5.9.1
	 * @param string $locale
	 */
	return apply_filters( 'pb_thema_subjects_locale', $locale );
}


/**
 * This function returns the current's book language thema file if exists otherwise returns false
 * @return false|string
 */
function get_thema_lang_file() {

	$locale = get_book_metadata_lang();

	$thema_files_path = WP_CONTENT_DIR . '/uploads/assets/thema/symbionts/';

	$thema_file = "{$thema_files_path}{$locale}.json";

	return file_exists( $thema_file ) ? $thema_file : false;
}


/**
 * Return an array of Thema subject categories.
 *
 * @since 4.4.0
 *
 * @param bool $include_qualifiers Whether or not the Theme subject qualifiers should be included.
 *.
 * @return array
 */
function get_thema_subjects( $include_qualifiers = false ) {

	$thema_file = get_thema_lang_file();

	$thema_json = file_exists( $thema_file ) ? $thema_file : PB_PLUGIN_DIR . 'symbionts/thema/en.json';

	$json = get_contents( $thema_json );

	$values = json_decode( $json );
	$subjects = [];
	foreach ( $values->CodeList->ThemaCodes->Code as $code ) {
		if ( ctype_alpha( substr( $code->CodeValue, 0, 1 ) ) || $include_qualifiers && ctype_digit( substr( $code->CodeValue, 0, 1 ) ) ) {
			if ( strlen( $code->CodeValue ) === 1 ) {
				$subjects[ $code->CodeValue ] = [
					'label' => $code->CodeDescription,
				];
				if ( ctype_alpha( $code->CodeValue ) ) {
					$subjects[ $code->CodeValue ]['children'][ $code->CodeValue ] = $code->CodeDescription;
				}
			} else {
				$subjects[ substr( $code->CodeValue, 0, 1 ) ]['children'][ $code->CodeValue ] = $code->CodeDescription;
			}
		}
	}
	return $subjects;
}

/**
 * Retrieve the subject name from a Thema subject code.
 *
 * @since 4.4.0
 *
 * @param string $code The Thema code.
 *
 * @return string The subject name.
 */
function get_subject_from_thema( $code ) {
	$subjects = get_thema_subjects( true );
	foreach ( $subjects as $key => $group ) {
		if ( strpos( $code, strval( $key ) ) === 0 ) {
			return $group['children'][ $code ];
		}
	}

	return false;
}

/**
 * Determine if a subject code is a BISAC code.
 *
 * @since 4.4.0
 *
 * @param string $code The code.
 *
 * @return bool
 */

function is_bisac( $code ) {
	if ( strlen( $code ) === 9 ) {
		if ( ctype_alpha( substr( $code, 0, 3 ) ) && ctype_digit( substr( $code, 3, 6 ) ) ) {
			return true;
		}
	}

	return false;
}

/**
 * @since 5.0.0
 */
function register_contributor_meta() {
	$args = [
		'sanitize_callback' => 'sanitize_text_field',
	];
	register_term_meta( 'contributor', 'contributor_first_name', $args );
	register_term_meta( 'contributor', 'contributor_last_name', $args );
}

/**
 * Ensure book data models are registered.
 *
 * These should already have been initialized by hooks, but sometimes they are disabled because we don't want them in the root site.
 */
function init_book_data_models() {
	if ( ! post_type_exists( 'chapter' ) ) {
		\Pressbooks\PostType\register_post_types();
	}
	if ( get_post_status_object( 'web-only' ) === null ) {
		\Pressbooks\PostType\register_post_statii();
	}
	if ( ! taxonomy_exists( 'front-matter-type' ) ) {
		\Pressbooks\Taxonomy::init()->registerTaxonomies();
	}
}

/**
 * Get the section metadata for a given ID.
 *
 * @since 5.7.0
 *
 * @param int $post_id
 *
 * @return array
 */
function get_section_information( $post_id ) {
	$section_meta = get_post_meta( $post_id, '', true );
	$section_meta['pb_title'] = get_the_title( $post_id );
	if ( get_post_type( $post_id ) === 'chapter' ) {
		$section_meta['pb_chapter_number'] = pb_get_chapter_number( $post_id );
	}
	foreach ( $section_meta as $key => $value ) {
		if ( is_array( $value ) ) {
			$section_meta[ $key ] = array_pop( $value );
		}
	}
	// Override Contributors
	$contributors = new \Pressbooks\Contributors();
	foreach ( $contributors->getAll( $post_id ) as $key => $val ) {
		$section_meta[ $key ] = $val;
	};

	return $section_meta;
}


/**
 * Echo the JSON-LD metadata tag for a book or section.
 *
 * @since 5.7.0
 *
 * @return null
 */
function add_json_ld_metadata() {

	$context = is_singular( [ 'front-matter', 'part', 'chapter', 'back-matter' ] ) ? 'section' : 'book';
	if ( $context === 'section' ) {
		global $post;
		$section_information = get_section_information( $post->ID );
		$book_information = Book::getBookInformation();
		$metadata = section_information_to_schema( $section_information, $book_information );
	} else {
		$metadata = new Metadata();
	}
	printf( '<script type="application/ld+json">%s</script>', wp_json_encode( $metadata ) );
}

/**
 * Echo HighWire Press-compatible meta tags for Google Scholar and Zotero integration.
 *
 * @since 5.7.0
 *
 * @return null
 */
function add_citation_metadata() {
	$context = is_singular( [ 'front-matter', 'part', 'chapter', 'back-matter' ] ) ? 'section' : 'book';
	$book_information = Book::getBookInformation();
	$tags = [];

	$map = [
		'citation_book_title' => 'isPartOf',
		'citation_title' => 'name',
		'citation_year' => 'copyrightYear',
		'citation_publication_date' => 'datePublished',
		'citation_language' => 'inLanguage',
		'citation_keywords' => 'keywords',
		'citation_publisher' => 'publisher.name',
		'citation_isbn' => 'isbn',
		'citation_doi' => 'identifier.value',
	];

	if ( $context === 'section' ) {
		global $post;
		$section_information = get_section_information( $post->ID );
		$metadata = section_information_to_schema( $section_information, $book_information );
		foreach ( $map as $to => $from ) {
			if ( strpos( $from, '.' ) ) {
				$pieces = explode( '.', $from );
				if ( isset( $metadata[ $pieces[0] ][ $pieces[1] ] ) && ! empty( $metadata[ $pieces[0] ][ $pieces[1] ] ) ) {
					$tags[] = sprintf( '<meta name="%1$s" content="%2$s">', $to, $metadata[ $pieces[0] ][ $pieces[1] ] );
				}
			} else {
				if ( isset( $metadata[ $from ] ) && ! empty( $metadata[ $from ] ) ) {
					$tags[] = sprintf( '<meta name="%1$s" content="%2$s">', $to, $metadata[ $from ] );
				}
			}
		}
		if ( isset( $metadata['author'] ) ) {
			foreach ( $metadata['author'] as $author ) {
				$tags[] = sprintf( '<meta name="%1$s" content="%2$s">', 'citation_author', $author['name'] );
			}
		}
	} else {
		$metadata = book_information_to_schema( $book_information );
		$tags[] = sprintf( '<meta name="%1$s" content="%2$s">', 'og:type', 'book' );
		foreach ( $map as $to => $from ) {
			if ( strpos( $from, '.' ) ) {
				$pieces = explode( '.', $from );
				if ( isset( $metadata[ $pieces[0] ][ $pieces[1] ] ) && ! empty( $metadata[ $pieces[0] ][ $pieces[1] ] ) ) {
					$tags[] = sprintf( '<meta name="%1$s" content="%2$s">', $to, $metadata[ $pieces[0] ][ $pieces[1] ] );
				}
			} else {
				if ( isset( $metadata[ $from ] ) && ! empty( $metadata[ $from ] ) ) {
					$tags[] = sprintf( '<meta name="%1$s" content="%2$s">', $to, $metadata[ $from ] );
				}
			}
		}
		if ( isset( $metadata['author'] ) ) {
			foreach ( $metadata['author'] as $author ) {
				$tags[] = sprintf( '<meta name="%1$s" content="%2$s">', 'citation_author', $author['name'] );
			}
		}
	}
	echo implode( "\n", $tags );
}

/**
 * @see https://github.com/lumenlearning/candela-citation
 * @see https://github.com/lumenlearning/candela-bombadil
 *
 * @since 5.8.1
 *
 * @return string
 */
function add_candela_citations( $content ) {
	if ( is_file( WP_PLUGIN_DIR . '/candela-citation/candela-citation.php' ) ) {
		if ( is_plugin_active_for_network( 'candela-citation/candela-citation.php' ) || is_plugin_active( 'candela-citation/candela-citation.php' ) ) {

			// Candela Citations, out-of-the-box, already works with exports using pb_append_front_matter_content,
			// pb_append_chapter_content, and pb_append_back_matter_content filters. They also handle appending webbook
			// chapters with the Bombadil Theme.
			//
			// For backwards compatibility, this function should only print Candela Citations when we are in a webbook chapter
			// (that isn't Bombadil).

			$is_book = Book::isBook();
			$is_not_admin = ( ! is_admin() );
			$is_not_bombadil = ( wp_get_theme()->get_stylesheet() !== 'candela-bombadil' );

			if ( $is_book && $is_not_admin && $is_not_bombadil ) {
				$post = get_post();
				if ( $post ) {
					$citation = \Candela\Citation::renderCitation( $post->ID );
					if ( $citation ) {
						$new_html = '
			 <section class="citations-section" role="contentinfo">
			 <h3>Candela Citations</h3>
					 <div>
						 <div id="citation-list-' . $post->ID . '">
							 ' . $citation . '
						 </div>
					 </div>
			 </section>';
						$content .= $new_html;
					}
				}
			}
		}
	}
	return $content;
}


/**
 * Return $option to use in get_option() for "is this book in the network catalog?"
 *
 * @return string
 */
function get_in_catalog_option() {
	// Try to find Aldine
	if ( defined( '\Aldine\Admin\BLOG_OPTION' ) ) {
		return \Aldine\Admin\BLOG_OPTION;
	} else {
		// Fallback to old pressbooks-publisher value
		return 'pressbooks_publisher_in_catalog';
	}
}

/**
 * This function download the thema subjects from the pressbooks symbionts repo when the book metadata is updated
 * @param $meta_id
 * @param $post_id
 * @param $meta_key
 * @param $meta_value
 * @return true|false
 */
function download_thema_lang( $meta_id, $post_id, $meta_key, $meta_value ) {
	if ( 'pb_language' !== $meta_key || $meta_value === 'en' ) {
		return false;
	}

	$thema_lang = $meta_value;

	$thema_lang_baseurl = 'https://raw.githubusercontent.com/pressbooks/symbionts-thema/main/';

	$basepath = WP_CONTENT_DIR . '/uploads/assets/thema/symbionts/';

	$local_file = "{$basepath}{$thema_lang}.json";

	if ( ! is_dir( $basepath ) ) {
		mkdir( $basepath, 0755, true );
	}

	$download_file = "{$thema_lang_baseurl}{$thema_lang}.json";

	// Check if there is a thema file available for download if not just skip the downloading request
	$response = wp_remote_head( $download_file );
	$status = wp_remote_retrieve_response_code( $response );

	// Proceed to download the file
	if ( $status === 200 ) {
		if ( ! file_exists( $local_file ) ) {
			if ( ! function_exists( 'download_url' ) ) {
				require_once( ABSPATH . 'wp-admin/includes/file.php' );
			}
			// If a file is available for download download the file
			$downloaded = download_url( $download_file );
			if ( is_wp_error( $downloaded ) ) {
				$_SESSION['pb_errors'][] = sprintf(
					__(
						'The %1$s Thema subject terms requested for this book could not be downloaded from %2$s. Please report this error to your network manager.',
						'pressbooks'
					),
					$thema_lang,
					'<code>' . $download_file . '</code>'
				);
				return false;
			} else {
				copy( $downloaded, $local_file );
				return unlink( $downloaded );
			}
		}
	}

	return false;
}

/**
 * Download thema file if the thema lang file is not downloaded
 * @param $post
 */
function check_thema_lang_file( $post ) {

	if ( $post->post_type !== 'metadata' ) {
		return;
	}

	if ( ! get_thema_lang_file() ) {
		download_thema_lang( $post, $post->ID, 'pb_language', get_book_metadata_lang() );
	}

}
