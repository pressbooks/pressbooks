<?php
/**
 * This class has two purposes:
 *  + Handle the custom metadata post, i.e. "Book Information". There should only be one metadata post per book.
 *  + Perform upgrades on individual books as Pressbooks evolves.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks;


use Pressbooks\Sanitize;


class Metadata {

	/**
	 * The value for option: pressbooks_metadata_version
	 *
	 * @see upgrade()
	 * @var int
	 */
	static $currentVersion = 10;


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

		/** @var \wpdb $wpdb */
		global $wpdb;
		$mid = $wpdb->get_var( $wpdb->prepare( "SELECT meta_id FROM $wpdb->postmeta WHERE post_id = %d AND meta_key = %s LIMIT 1 ", $post_id, $meta_key ) );
		if ( $mid != '' ) {
			return absint( $mid );
		}

		return false;
	}


	/**
	 * Returns an html blob of meta elements based on what is set in 'Book Information'
	 *
	 * @return string
	 */
	static function getSeoMetaElements() {
		// map items that are already captured
		$meta_mapping = array(
		    'author' => 'pb_author',
		    'description' => 'pb_about_50',
		    'keywords' => 'pb_keywords_tags',
		    'publisher' => 'pb_publisher'
		);
		$html = "<meta name='application-name' content='Pressbooks'>\n";
		$metadata = Book::getBookInformation();

		// create meta elements
		foreach ( $meta_mapping as $name => $content ) {
			if ( array_key_exists( $content, $metadata ) ) {
				$html .= "<meta name='" . $name . "' content='" . $metadata[$content] . "'>\n";
			}
		}

		return $html;
	}

	/**
	 * Returns an html blob of microdata elements based on what is set in 'Book Information'
	 *
	 * @return string
	 */
	static function getMicrodataElements() {
		$html = '';
		// map items that are already captured
		$micro_mapping = array(
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
		);
		$metadata = Book::getBookInformation();

		// create microdata elements
		foreach ( $micro_mapping as $itemprop => $content ) {
			if ( array_key_exists( $content, $metadata ) ) {
				if ( 'pb_publication_date' == $content ) {
					$content = date( 'Y-m-d', (int) $metadata[$content] );
				} else {
					$content = $metadata[$content];
				}
				$html .= "<meta itemprop='" . $itemprop . "' content='" . $content . "' id='" . $itemprop . "'>\n";
			}
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
	 * @return string $xml response
	 */
	static function getLicenseXml( $type, $copyright_holder, $src_url, $title, $lang = '' ) {
		$endpoint = 'https://api.creativecommons.org/rest/1.5/';
		$lang = ( ! empty( $lang ) ) ? substr( $lang, 0, 2 ) : '';
		$expected = array(
		    'public-domain' => array(
			'license' => 'zero',
			'commercial' => 'y',
			'derivatives' => 'y',
		    ),
		    'cc-by' => array(
			'license' => 'standard',
			'commercial' => 'y',
			'derivatives' => 'y',
		    ),
		    'cc-by-sa' => array(
			'license' => 'standard',
			'commercial' => 'y',
			'derivatives' => 'sa',
		    ),
		    'cc-by-nd' => array(
			'license' => 'standard',
			'commercial' => 'y',
			'derivatives' => 'n',
		    ),
		    'cc-by-nc' => array(
			'license' => 'standard',
			'commercial' => 'n',
			'derivatives' => 'y',
		    ),
		    'cc-by-nc-sa' => array(
			'license' => 'standard',
			'commercial' => 'n',
			'derivatives' => 'sa',
		    ),
		    'cc-by-nc-nd' => array(
			'license' => 'standard',
			'commercial' => 'n',
			'derivatives' => 'n',
		    ),
		    'all-rights-reserved' => array(),
//		    'other' => array(),
		);

		// nothing meaningful to hit the api with, so bail
		if ( ! array_key_exists( $type, $expected ) ) {
			return '';
		}

		switch ( $type ) {
			// api doesn't have an 'all-rights-reserved' endpoint, so manual build necessary
			case 'all-rights-reserved':
				$xml = "<result><html>"
					. "<span property='dct:title'>" . Sanitize\sanitize_xml_attribute( $title ) . "</span> &#169; "
					. Sanitize\sanitize_xml_attribute( $copyright_holder ) . '. ' . __( 'All Rights Reserved', 'pressbooks' ) . ".</html></result>";
				break;

//			case 'other':
//				 //@TODO
//				break;

			default:

				$key = array_keys( $expected[$type] );
				$val = array_values( $expected[$type] );

				// build the url
				$url = $endpoint . $key[0] . "/" . $val[0] . "/get?" . $key[1] . "=" . $val[1] . "&" . $key[2] . "=" . $val[2] .
					"&creator=" . urlencode( $copyright_holder ) . "&attribution_url=" . urlencode( $src_url ) . "&title=" . urlencode( $title ) . "&locale=" . $lang ;

				$xml = wp_remote_get( $url );
				$ok = wp_remote_retrieve_response_code( $xml );

				// if server response is not ok
				if ( 200 != $ok ) {
					return '';
				}

				// if remote call went sideways
				if ( ! is_wp_error( $xml ) ) {
					$xml = $xml['body'];

				} else {
					// Something went wrong
					\error_log( '\Pressbooks\Metadata::getLicenseXml error: ' . $xml->get_error_message() );
				}

				break;
		}

		return $xml;
	}

	/**
	 * Returns an HTML blob if given an XML object
	 *
	 * @param \SimpleXMLElement $response
	 * @return string $html blob of copyright information
	 */
	static function getWebLicenseHtml( \SimpleXMLElement $response ) {
		$html = '';

		if ( is_object( $response ) ) {
			$content = $response->asXML();
			$content = trim( str_replace( array( '<p xmlns:dct="http://purl.org/dc/terms/">', '</p>', '<html>', '</html>' ), array( '', '', '', '' ), $content ) );
			$content = preg_replace( "/http:\/\/i.creativecommons/iU", "https://i.creativecommons", $content );

			$html = '<div class="license-attribution" xmlns:cc="http://creativecommons.org/ns#"><p xmlns:dct="http://purl.org/dc/terms/">' . rtrim( $content, "." ) . ', ' . __( "except where otherwise noted.", "pressbooks" ) . '</p></div>';
		}

		return html_entity_decode( $html, ENT_XHTML, 'UTF-8' );
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
		if ( $version < 3 ) {
			$this->upgradeCustomCss();
		}
		if ( $version < 4 ) {
			$this->fixDoubleSlashBug();
		}
		if ( $version < 5 ) {
			$this->changeDefaultBookCover();
		}
		if ( $version < 6 ||$version < 7 ) {
			$this->makeThumbnailsForBookCover();
		}
		if ( $version < 8 ) {
			$this->resetLandingPage();
		}
		if ( $version < 10 ) {
			\Pressbooks\Taxonomy::insertTerms();
			flush_rewrite_rules( false );
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
						$meta_value = array_values( $meta_value );
						$meta_value = array_pop( $meta_value );
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

		$book_structure = Book::getBookStructure();
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
							$meta_value = array_values( $meta_value );
							$meta_value = array_pop( $meta_value );
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
	 * @param bool $new_as_keys
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
	 * Upgrade Custom CSS types.
	 *
	 * @see \Pressbooks\Activation::wpmuActivate
	 */
	function upgradeCustomCss() {

		/** @var $wpdb \wpdb */
		global $wpdb;

		$posts = array(
			array(
				'post_title' => __( 'Custom CSS for Ebook', 'pressbooks' ),
				'post_name' => 'epub',
				'post_type' => 'custom-css' ),
			array(
				'post_title' => __( 'Custom CSS for PDF', 'pressbooks' ),
				'post_name' => 'prince',
				'post_type' => 'custom-css' ),
			array(
				'post_title' => __( 'Custom CSS for Web', 'pressbooks' ),
				'post_name' => 'web',
				'post_type' => 'custom-css' ),
		);

		$post = array( 'post_status' => 'publish', 'post_author' => wp_get_current_user()->ID );
		$query = "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = %s AND post_name = %s AND post_status = 'publish' ";

		foreach ( $posts as $item ) {
			$exists = $wpdb->get_var( $wpdb->prepare( $query, array( $item['post_title'], $item['post_type'], $item['post_name'] ) ) );
			if ( empty( $exists ) ) {
				$data = array_merge( $item, $post );
				wp_insert_post( $data );
			}
		}

	}


	/**
	 * Fix a double slash bug by reactivating theme with new settings.
	 *
	 * @see \Pressbooks\Pressbooks::registerThemeDirectories
	 */
	function fixDoubleSlashBug() {

		$theme = wp_get_theme();
		if ( ! $theme->exists() || ! $theme->is_allowed() ) {
			return; // Do nothing
		} else {
			switch_theme( $theme->get_stylesheet() );
		}
	}


	/**
	 * Change default book cover from PNG to JPG
	 */
	function changeDefaultBookCover() {

		$post = $this->getMetaPost();

		if ( $post ) {
			$pb_cover_image = get_post_meta( $post->ID, 'pb_cover_image', true );
			if ( preg_match( '~assets/images/default-book-cover\.png$~', $pb_cover_image ) ) {
				update_post_meta( $post->ID, 'pb_cover_image', \Pressbooks\Image\default_cover_url() );
				Book::deleteBookObjectCache();
			}
		}
	}


	/**
	 * Generate thumbnails for a user uploaded cover
	 */
	function makeThumbnailsForBookCover() {

		$post = $this->getMetaPost();
		if ( $post ) {

			$pb_cover_image = get_post_meta( $post->ID, 'pb_cover_image', true );
			if ( $pb_cover_image && ! preg_match( '~assets/dist/images/default-book-cover\.jpg$~', $pb_cover_image ) ) {

				$path = \Pressbooks\Utility\get_media_path( $pb_cover_image );
				$type = wp_check_filetype( $path );
				$type = $type['type'];

				// Insert new image, create thumbnails
				$args = array(
					'post_mime_type' => $type,
					'post_title' => __( 'Cover Image', 'pressbooks' ),
					'post_content' => '',
					'post_status' => 'inherit'
				);

				include_once( ABSPATH . 'wp-admin/includes/image.php' );
				$id = wp_insert_attachment( $args, $path, $post->ID );
				wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $path ) );
				Book::deleteBookObjectCache();
			}
		}
	}


	/**
	 * Fix broken landing page
	 */
	function resetLandingPage() {

		/** @var $wpdb \wpdb */
		global $wpdb;

		update_option( 'show_on_front', 'page' );

		$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_name = 'cover' AND post_type = 'page' AND post_status = 'publish' ";
		$id = $wpdb->get_var( $sql );
		if ( $id ) {
			update_option( 'page_on_front', $id );
		}

		$sql = "SELECT ID FROM {$wpdb->posts} WHERE post_name = 'table-of-contents' AND post_type = 'page' AND post_status = 'publish' ";
		$id = $wpdb->get_var( $sql );
		if ( $id ) {
			update_option( 'page_for_posts', $id );
		}
	}


}
