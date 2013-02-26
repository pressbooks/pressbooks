<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks;


class Metadata {

	/**
	 * The value for option: pressbooks_metadata_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	static $currentVersion = 2;


	/**
	 * Deprecated meta keys represented by checkboxes in the GUI.
	 * We need to upgrade these for compatibility with custom_metdata().
	 *
	 * @var array
	 */
	public $upgradeCheckboxes = array(
		'chapter-export' => 1,
		'front-matter-export' => 1,
		'back-matter-export' => 1,
		'show-title' => 1,
	);


	function __construct() {

	}


	/**
	 * Returns the latest "metadata" post. There should be only one per book.
	 *
	 * @return \WP_Post|bool
	 */
	function getMetaPost() {

		$args = array(
			'post_type' => 'metadata',
			'posts_per_page' => 1,
			'post_status' => 'publish',
			'orderby' => 'modified',
			'no_found_rows' => true,
			'cache_results' => true,
		);

		$q = new \WP_Query();
		$results = $q->query( $args );

		if ( empty( $results ) ) {
			return false;
		}

		return $results[0];
	}


	/**
	 * Return metadata attached to the latest "metadata" post.
	 *
	 * @return array
	 */
	function getMetaPostMetadata() {

		$meta_post = $this->getMetaPost();

		if ( ! $meta_post ) {
			return array();
		}

		return get_post_meta( $meta_post->ID );
	}


	/**
	 * Return a database ID for a given meta key.
	 *
	 * @param int    $post_id
	 * @param string $meta_key
	 *
	 * @return int|bool
	 */
	function getMidByKey( $post_id, $meta_key ) {

		/** @var $wpdb wpdb */
		global $wpdb;
		$mid = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s LIMIT 1 ", $post_id, $meta_key ) );
		if ( $mid != '' ) {
			return absint( $mid );
		}

		return false;
	}


	// ----------------------------------------------------------------------------------------------------------------
	// Upgrades
	// ----------------------------------------------------------------------------------------------------------------

	/**
	 * Upgrade metadata.
	 *
	 * @param int $version
	 */
	function upgrade( $version ) {

		if ( $version < 1 ) {
			// Upgrade from version 0 (closed source service) to version 1 (initial open source offering)
			$this->upgradeEcommerce();
			$this->upgradeBookInformation();
			$this->upgradeBook();
		}
		if ( $version < 2 ) {
			// New title page feature missing in many books
			wp_insert_term( 'Title Page', 'front-matter-type', array( 'slug' => 'title-page' ) );
		}
	}


	/**
	 * Upgrade Ecommerce metadata
	 */
	function upgradeEcommerce() {

		$options = get_option( 'ecomm-url' );
		$compare = $this->getDeprecatedComparisonTable( 'ecommerce' );
		$new_options = array();

		if ( $options ) {
			foreach ( $options as $meta_key => $meta_value ) {
				$new_meta_key = @$compare[$meta_key];
				if ( $new_meta_key ) {
					$new_options[$new_meta_key] = $meta_value;
				}
			}
		}

		update_option( 'pressbooks_ecommerce_links', $new_options );
		delete_option( 'ecomm-url' );
	}


	/**
	 * Upgrade book information.
	 */
	function upgradeBookInformation() {

		// Metadata

		$meta_post = $this->getMetaPost();
		if ( ! $meta_post )
			return; // Do nothing

		$metadata = $this->getMetaPostMetadata();
		$compare = $this->getDeprecatedComparisonTable( 'metadata' );

		foreach ( $metadata as $meta_key => $meta_value ) {
			$new_meta_key = @$compare[$meta_key];
			if ( $new_meta_key ) {
				$meta_id = $this->getMidByKey( $meta_post->ID, $meta_key );
				if ( $meta_id ) {
					if ( isset( $this->upgradeCheckboxes[$meta_key] ) ) {
						$meta_value = 'on';
					} elseif ( is_array( $meta_value ) ) {
						$meta_value = array_pop( array_values( $meta_value ) );
					}

					// Overrides
					if ( 'pb_language' == $new_meta_key ) {
						$meta_value = substr( strtolower( $meta_value ), 0, 2 );
					}
					if ( 'pb_publication_date' == $new_meta_key ) {
						$meta_value = strtotime( $meta_value );
					}

					// Updating [$meta_key] to [$new_meta_key]
					update_metadata_by_mid( 'post', $meta_id, $meta_value, $new_meta_key );
				}
			}
		}
		// Force title change
		update_metadata( 'post', $meta_post->ID, 'pb_title', get_bloginfo( 'name' ) );
	}


	/**
	 * Upgrade book metadata.
	 */
	function upgradeBook() {

		$book_structure = \PressBooks\Book::getBookStructure();
		foreach ( $book_structure['__order'] as $post_id => $_ ) {

			$meta = get_post_meta( $post_id );
			$compare = $this->getDeprecatedComparisonTable( get_post_type( $post_id ) );

			foreach ( $meta as $meta_key => $meta_value ) {
				$new_meta_key = @$compare[$meta_key];
				if ( $new_meta_key ) {
					$meta_id = $this->getMidByKey( $post_id, $meta_key );
					if ( $meta_id ) {
						if ( isset( $this->upgradeCheckboxes[$meta_key] ) ) {
							$meta_value = 'on';
						} elseif ( is_array( $meta_value ) ) {
							$meta_value = array_pop( array_values( $meta_value ) );
						}
						// Updating [$meta_key] to [$new_meta_key]
						update_metadata_by_mid( 'post', $meta_id, $meta_value, $new_meta_key );
					}
				}
			}
		}

	}


	/**
	 * @deprecated
	 *
	 * @param string $table
	 * @param bool   $new_as_keys
	 *
	 * @return array
	 */
	function getDeprecatedComparisonTable( $table, $new_as_keys = false ) {

		if ( 'chapter' == $table ) {

			// Chapter
			$metadata = array(
				'short-title' => 'pb_short_title',
				'subtitle' => 'pb_subtitle',
				'chap_author' => 'pb_section_author',
				'chapter-export' => 'pb_export',
				'show-title' => 'pb_show_title'
			);

		} elseif ( 'front-matter' == $table ) {

			// Front Matter
			$metadata = array(
				'short-title' => 'pb_short_title',
				'subtitle' => 'pb_subtitle',
				'chap_author' => 'pb_section_author',
				'front-matter-export' => 'pb_export',
				'show-title' => 'pb_show_title'
			);

		} elseif ( 'back-matter' == $table ) {

			// Back Matter
			$metadata = array(
				'back-matter-export' => 'pb_export',
				'show-title' => 'pb_show_title'
			);

		} elseif ( 'ecommerce' == $table ) {

			// Ecommerce
			$metadata = array(
				'url1' => 'amazon',
				'url2' => 'oreilly',
				'url3' => 'barnesandnoble',
				'url4' => 'kobo',
				'url5' => 'ibooks',
				'url6' => 'otherservice',
			);

		} elseif ( 'metadata' == $table ) {

			// Book Information
			$metadata = array(
				'Title' => 'pb_title',
				'Short Title' => 'pb_short_title',
				'Subtitle' => 'pb_subtitle',
				'Author' => 'pb_author',
				'Author, file as' => 'pb_author_file_as',
				'Publisher' => 'pb_publisher',
				'Publication Date' => 'pb_publication_date',
				'Publisher City' => 'pb_publisher_city',
				'Cover Image' => 'pb_cover_image',
				'Copyright Year' => 'pb_copyright_year',
				'Copyright Holder' => 'pb_copyright_holder',
				'Copyright Extra Info' => 'pb_custom_copyright',
				'About (140 characters)' => 'pb_about_140',
				'About (50 words)' => 'pb_about_50',
				'About (Unlimited)' => 'pb_about_unlimited',
				'Series Title' => 'pb_series_title',
				'Series Number' => 'pb_series_number',
				'Editor' => 'pb_editor',
				'Translator' => 'pb_translator',
				'Keywords/Tags' => 'pb_keywords_tags',
				'Hashtag' => 'pb_hashtag',
				'Print ISBN' => 'pb_print_isbn',
				'Ebook ISBN' => 'pb_ebook_isbn',
				'Language' => 'pb_language',
				'List Price (Print)' => 'pb_list_price_print',
				'List Price (PDF)' => 'pb_list_price_pdf',
				'List Price (ePub)' => 'pb_list_price_epub',
				'List Price (Web)' => 'pb_list_price_web',
				'Bisac Subject 1' => 'pb_bisac_subject',
				'Bisac Regional Theme' => 'pb_bisac_regional_theme',
				'catalogue_order' => 'pb_catalogue_order',
			);

		} else {
			$metadata = array();
		}

		if ( $new_as_keys ) {
			$metadata = array_flip( $metadata );
		}

		return $metadata;
	}


	/**
	 * We don't support "the kitchen sink" when using the custom metadata plugin,
	 * render the WYSIWYG editor accordingly.
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	static function metadataManagerDefaultEditorArgs( $args ) {

		// Precedence when using the + operator to merge arrays is from left to right

		$args = array(
			'media_buttons' => false,
			'tinymce' => array(
				'theme_advanced_buttons1' => 'bold,italic,underline,strikethrough,|,link,unlink,|,numlist,bullist,|,undo,redo,pastetext,pasteword,|',
				'theme_advanced_buttons2' => '',
				'theme_advanced_buttons3' => ''
			)
		) + $args;

		return $args;
	}

}
