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
		 'pb_about_50' => 'disambiguatingDescription',
		 'pb_about_unlimited' => 'description',
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
			$book_information['pb_book_license'] = 'all-rights-reserved';
		}

		$licensing = new Licensing;
		$book_schema['license'] = [
			'@type' => 'CreativeWork',
			'url' => $licensing->getUrlForLicense( $book_information['pb_book_license'] ),
			'name' => $licensing->getSupportedTypes()[ $book_information['pb_book_license'] ]['desc'],
		];
		if ( isset( $book_information['pb_custom_copyright'] ) ) {
			$book_schema['license']['description'] = $book_information['pb_custom_copyright'];
		}

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
		'disambiguatingDescription' => 'pb_about_50',
		'description' => 'pb_about_unlimited',
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
	if ( is_array( $book_schema['license'] ) ) {
		$book_information['pb_book_license'] = $licensing->getLicenseFromUrl( $book_schema['license']['url'] );
		if ( isset( $book_schema['license']['description'] ) ) {
			$book_information['pb_custom_copyright'] = $book_schema['license']['description'];
		}
	} else {
		$book_information['pb_book_license'] = $licensing->getLicenseFromUrl( $book_schema['license'] );
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
				$section_information['pb_section_license'] = 'all-rights-reserved';
			}
		}

		$licensing = new Licensing;
		$section_schema['license'] = [
			'@type' => 'CreativeWork',
			'url' => $licensing->getUrlForLicense( $section_information['pb_section_license'] ),
			'name' => $licensing->getSupportedTypes()[ $section_information['pb_section_license'] ]['desc'],
		];

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

	if ( is_array( $book_schema['license'] ) ) {
		$book_license = $book_schema['license']['url'];
	} else {
		$book_license = $book_schema['license'];
	}

	if ( is_array( $section_schema['license'] ) ) {
		$section_license = $section_schema['license']['url'];
	} else {
		$section_license = $section_schema['license'];
	}

	if ( $section_license !== $book_license ) {
		$licensing = new Licensing;
		$section_information['pb_section_license'] = $licensing->getLicenseFromUrl( $section_license );
	}

	if ( isset( $section_schema['isBasedOn'] ) && $section_schema['isBasedOn'] !== $book_schema['isBasedOn'] ) {
		$section_information['pb_is_based_on'] = $section_schema['isBasedOn'];
	}

	return $section_information;
}

/**
 * @since 4.4.0
 */
function get_nonacademic_subjects() {
	return [
		[
			'slug' => 'fiction',
			'label' => __( 'Fiction', 'pressbooks' ),
			'children' => [
				[ 'slug' => 'fiction-action-adventure', 'label' => __( 'Action & Adventure', 'pressbooks' ) ],
				[ 'slug' => 'fiction-crime', 'label' => __( 'Crime', 'pressbooks' ) ],
				[ 'slug' => 'fiction-children-juvenile', 'label' => __( 'Children & Juvenile', 'pressbooks' ) ],
				[ 'slug' => 'fiction-erotic', 'label' => __( 'Erotic', 'pressbooks' ) ],
				[ 'slug' => 'fiction-general', 'label' => __( 'General Fiction', 'pressbooks' ) ],
				[ 'slug' => 'fiction-historical', 'label' => __( 'Historical', 'pressbooks' ) ],
				[ 'slug' => 'fiction-humor-comedy', 'label' => __( 'Humor & Comedy', 'pressbooks' ) ],
				[ 'slug' => 'fiction-lgbtq', 'label' => __( 'LGBTQ+', 'pressbooks' ) ],
				[ 'slug' => 'fiction-literary', 'label' => __( 'Literary', 'pressbooks' ) ],
				[ 'slug' => 'fiction-mystery-suspense', 'label' => __( 'Mystery & Suspense', 'pressbooks' ) ],
				[ 'slug' => 'fiction-poetry', 'label' => __( 'Poetry', 'pressbooks' ) ],
				[ 'slug' => 'fiction-political', 'label' => __( 'Political', 'pressbooks' ) ],
				[ 'slug' => 'fiction-religious', 'label' => __( 'Religious', 'pressbooks' ) ],
				[ 'slug' => 'fiction-romance', 'label' => __( 'Romance', 'pressbooks' ) ],
				[ 'slug' => 'fiction-sci-fi-fantasy', 'label' => __( 'Sci-Fi & Fantasy', 'pressbooks' ) ],
				[ 'slug' => 'fiction-short-stories', 'label' => __( 'Short Stories', 'pressbooks' ) ],
				[ 'slug' => 'fiction-thriller-horror', 'label' => __( 'Thriller & Horror', 'pressbooks' ) ],
				[ 'slug' => 'fiction-young-adult', 'label' => __( 'Young Adult', 'pressbooks' ) ],
			],
		],
		[
			'slug' => 'non-fiction',
			'label' => __( 'Non-fiction', 'pressbooks' ),
			'children' => [
				[ 'slug' => 'non-fiction-art-design-photography','label' => __( 'Art, Design & Photography', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-biography-memoir','label' => __( 'Biography & Memoir', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-business-economics','label' => __( 'Business & Economics', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-environment-science-nature','label' => __( 'Environment, Science & Nature', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-essays','label' => __( 'Essays', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-family-relationships','label' => __( 'Family & Relationships', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-general-non-fiction', 'label' => __( 'General Non-fiction', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-health-well-being','label' => __( 'Health & Well-being', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-home-food-drink','label' => __( 'Home, Food & Drink', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-how-to-advice','label' => __( 'How-To & Advice', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-humor-comedy','label' => __( 'Humor & Comedy', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-reference-language','label' => __( 'Reference & Language', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-religion-spirituality', 'label' => __( 'Religion & Spirituality', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-social-political','label' => __( 'Social & Political', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-sports-games','label' => __( 'Sports & Games', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-technology','label' => __( 'Technology', 'pressbooks' ) ],
				[ 'slug' => 'non-fiction-travel','label' => __( 'Travel', 'pressbooks' ) ],
			],
		],
	];
}

/**
 * @since 4.4.0
 */
function get_academic_subjects() {
	return [
		[
			'slug' => 'business',
			'label' => __( 'Business', 'pressbooks' ),
			'children' => [
				[ 'slug' => 'business-accounting','label' => __( 'Accounting', 'pressbooks' ) ],
				[ 'slug' => 'business-finance','label' => __( 'Finance', 'pressbooks' ) ],
				[ 'slug' => 'business-management-information-systems','label' => __( 'Management & Information Systems', 'pressbooks' ) ],
				[ 'slug' => 'business-marketing','label' => __( 'Marketing', 'pressbooks' ) ],
				[ 'slug' => 'business-economics','label' => __( 'Economics', 'pressbooks' ) ],
				[ 'slug' => 'business-other','label' => __( 'Other', 'pressbooks' ) ],
			],
		],
		[
			'slug' => 'education',
			'label' => __( 'Education', 'pressbooks' ),
			'children' => [
				[ 'slug' => 'education-educational-psychology','label' => __( 'Educational Psychology', 'pressbooks' ) ],
				[ 'slug' => 'education-kindergarten-elementary','label' => __( 'Kindergarten & Elementary', 'pressbooks' ) ],
				[ 'slug' => 'education-physical-education', 'label' => __( 'Physical Education', 'pressbooks' ) ],
				[ 'slug' => 'education-secondary-education','label' => __( 'Secondary Education', 'pressbooks' ) ],
				[ 'slug' => 'education-post-secondary', 'label' => __( 'Post-secondary Education', 'pressbooks' ) ],
				[ 'slug' => 'education-other', 'label' => __( 'Other', 'pressbooks' ) ],
			],
		],
		[
			'slug' => 'engineering-technology',
			'label' => __( 'Engineering & Technology', 'pressbooks' ),
			'children' => [
				[ 'slug' => 'engineering-technology-architecture', 'label' => __( 'Architecture', 'pressbooks' ) ],
				[ 'slug' => 'engineering-technology-bioengineering', 'label' => __( 'Bioengineering', 'pressbooks' ) ],
				[ 'slug' => 'engineering-technology-chemical-engineering', 'label' => __( 'Chemical Engineering', 'pressbooks' ) ],
				[ 'slug' => 'engineering-technology-civil-engineering', 'label' => __( 'Civil Engineering', 'pressbooks' ) ],
				[ 'slug' => 'engineering-technology-electrical-engineering', 'label' => __( 'Electrical Engineering', 'pressbooks' ) ],
				[ 'slug' => 'engineering-technology-mechanical-engineering', 'label' => __( 'Mechanical Engineering', 'pressbooks' ) ],
				[ 'slug' => 'engineering-technology-mining-materials', 'label' => __( 'Mining & Materials', 'pressbooks' ) ],
				[ 'slug' => 'engineering-technology-urban-planning', 'label' => __( 'Urban Planning', 'pressbooks' ) ],
				[ 'slug' => 'engineering-technology-computer-science-software-engineering', 'label' => __( 'Computer Science & Software Engineering', 'pressbooks' ) ],
				[ 'slug' => 'engineering-technology-other', 'label' => __( 'Other', 'pressbooks' ) ],
			],
		],
		[
			'slug' => 'health-sciences',
			'label' => __( 'Health Sciences', 'pressbooks' ),
			'children' => [
				[ 'slug' => 'health-sciences-biochemistry-biomedical-sciences', 'label' => __( 'Biochemistry & Biomedical Sciences', 'pressbooks' ) ],
				[ 'slug' => 'health-sciences-dentistry', 'label' => __( 'Dentistry', 'pressbooks' ) ],
				[ 'slug' => 'health-sciences-medicine', 'label' => __( 'Medicine', 'pressbooks' ) ],
				[ 'slug' => 'health-sciences-nursing', 'label' => __( 'Nursing', 'pressbooks' ) ],
				[ 'slug' => 'health-sciences-nutrition', 'label' => __( 'Nutrition', 'pressbooks' ) ],
				[ 'slug' => 'health-sciences-pharmacy', 'label' => __( 'Pharmacy', 'pressbooks' ) ],
				[ 'slug' => 'health-sciences-physiotherapy-rehabilitation-therapy', 'label' => __( 'Physiotherapy & Rehabilitation Therapy', 'pressbooks' ) ],
				[ 'slug' => 'health-sciences-psychiatry-behavioural-sciences', 'label' => __( 'Psychiatry & Behavioural Sciences', 'pressbooks' ) ],
				[ 'slug' => 'health-sciences-public-health-healthy-policy', 'label' => __( 'Public Health & Healthy Policy', 'pressbooks' ) ],
				[ 'slug' => 'health-sciences-sports-medicine', 'label' => __( 'Sports Medicine', 'pressbooks' ) ],
				[ 'slug' => 'health-sciences-other', 'label' => __( 'Other', 'pressbooks' ) ],
			],
		],
		[
			'slug' => 'humanities-arts',
			'label' => __( 'Humanities & Arts', 'pressbooks' ),
			'children' => [
				[ 'slug' => 'humanities-arts-archaeology', 'label' => __( 'Archaeology', 'pressbooks' ) ],
				[ 'slug' => 'humanities-arts-art', 'label' => __( 'Art', 'pressbooks' ) ],
				[ 'slug' => 'humanities-arts-classics', 'label' => __( 'Classics', 'pressbooks' ) ],
				[ 'slug' => 'humanities-arts-design', 'label' => __( 'Design', 'pressbooks' ) ],
				[ 'slug' => 'humanities-arts-history', 'label' => __( 'History', 'pressbooks' ) ],
				[ 'slug' => 'humanities-arts-language', 'label' => __( 'Language', 'pressbooks' ) ],
				[ 'slug' => 'humanities-arts-literature', 'label' => __( 'Literature', 'pressbooks' ) ],
				[ 'slug' => 'humanities-arts-media-communication-studies', 'label' => __( 'Media & Communication Studies', 'pressbooks' ) ],
				[ 'slug' => 'humanities-arts-music', 'label' => __( 'Music', 'pressbooks' ) ],
				[ 'slug' => 'humanities-arts-philosophy', 'label' => __( 'Philosophy', 'pressbooks' ) ],
				[ 'slug' => 'humanities-arts-religious-studies', 'label' => __( 'Religious Studies', 'pressbooks' ) ],
				[ 'slug' => 'humanities-arts-other', 'label' => __( 'Other', 'pressbooks' ) ],
			],
		],
		[
			'slug' => 'law',
			'label' => __( 'Law', 'pressbooks' ),
			'children' => [
				[ 'slug' => 'law-contracts-property-commercial', 'label' => __( 'Contracts, Property & Commercial', 'pressbooks' ) ],
				[ 'slug' => 'law-criminal', 'label' => __( 'Criminal', 'pressbooks' ) ],
				[ 'slug' => 'law-human-rights', 'label' => __( 'Human Rights', 'pressbooks' ) ],
				[ 'slug' => 'law-intellectual-property', 'label' => __( 'Intellectual Property', 'pressbooks' ) ],
				[ 'slug' => 'law-international-trade', 'label' => __( 'International & Trade', 'pressbooks' ) ],
				[ 'slug' => 'law-public-law-policy', 'label' => __( 'Public Law & Policy', 'pressbooks' ) ],
				[ 'slug' => 'law-other', 'label' => __( 'Other', 'pressbooks' ) ],
			],
		],
		[
			'slug' => 'support-resources',
			'label' => __( 'Support Resources', 'pressbooks' ),
			'children' => [
				[ 'slug' => 'support-resources-college-success', 'label' => __( 'College Success', 'pressbooks' ) ],
				[ 'slug' => 'support-resources-student-guides', 'label' => __( 'Student Guides', 'pressbooks' ) ],
				[ 'slug' => 'support-resources-teaching-guides', 'label' => __( 'Teaching Guides', 'pressbooks' ) ],
				[ 'slug' => 'support-resources-toolkits', 'label' => __( 'Toolkits', 'pressbooks' ) ],
				[ 'slug' => 'support-resources-other', 'label' => __( 'Other', 'pressbooks' ) ],
			],
		],
		[
			'slug' => 'sciences',
			'label' => __( 'Sciences', 'pressbooks' ),
			'children' => [
				[ 'slug' => 'sciences-biology', 'label' => __( 'Biology', 'pressbooks' ) ],
				[ 'slug' => 'sciences-chemistry', 'label' => __( 'Chemistry', 'pressbooks' ) ],
				[ 'slug' => 'sciences-environment-earth-sciences', 'label' => __( 'Environment & Earth Sciences', 'pressbooks' ) ],
				[ 'slug' => 'sciences-geography', 'label' => __( 'Geography', 'pressbooks' ) ],
				[ 'slug' => 'sciences-mathematics', 'label' => __( 'Mathematics', 'pressbooks' ) ],
				[ 'slug' => 'sciences-physics', 'label' => __( 'Physics', 'pressbooks' ) ],
				[ 'slug' => 'sciences-other', 'label' => __( 'Other', 'pressbooks' ) ],
			],
		],
		[
			'slug' => 'social-sciences',
			'label' => __( 'Social Sciences', 'pressbooks' ),
			'children' => [
				[ 'slug' => 'social-sciences-anthropology', 'label' => __( 'Anthropology', 'pressbooks' ) ],
				[ 'slug' => 'social-sciences-gender-studies', 'label' => __( 'Gender Studies', 'pressbooks' ) ],
				[ 'slug' => 'social-sciences-indigenous-studies', 'label' => __( 'Indigenous Studies', 'pressbooks' ) ],
				[ 'slug' => 'social-sciences-linguistics', 'label' => __( 'Linguistics', 'pressbooks' ) ],
				[ 'slug' => 'social-sciences-museums-libraries-information-sciences', 'label' => __( 'Museums, Libraries & Information Sciences', 'pressbooks' ) ],
				[ 'slug' => 'social-sciences-political-science', 'label' => __( 'Political Science', 'pressbooks' ) ],
				[ 'slug' => 'social-sciences-psychology', 'label' => __( 'Psychology', 'pressbooks' ) ],
				[ 'slug' => 'social-sciences-social-work', 'label' => __( 'Social Work', 'pressbooks' ) ],
				[ 'slug' => 'social-sciences-sociology', 'label' => __( 'Sociology', 'pressbooks' ) ],
				[ 'slug' => 'social-sciences-other', 'label' => __( 'Other', 'pressbooks' ) ],
			],
		],
	];
}
