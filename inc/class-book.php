<?php
/**
 * A book is a discrete, collection of text (and other media), that is designed by an author(s) as an internally
 * complete representation of an idea, or set of ideas; emotion or set of emotions; and transmitted to readers in
 * various formats.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
// TODO: Security audit
// @phpcs:disable Pressbooks.Security.ValidatedSanitizedInput.InputNotValidated
// @phpcs:disable Pressbooks.Security.EscapeOutput.OutputNotEscaped
// @phpcs:disable WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition

namespace Pressbooks;

use Pressbooks\DataCollector\Book as BookDataCollector;
use Pressbooks\Modules\Export\Export;
use Pressbooks\Modules\Export\Xhtml\Xhtml11;

class Book {

	const SUBSECTIONS_TRANSIENT = 'pb_book_subsections';
	const SUBSECTION_PROCESSING_TRANSIENT = 'pb_getting_all_subsections';

	/**
	 * @var Book
	 */
	protected static $instance;

	/**
	 * @var array
	 */
	protected static $__order = [];

	/**
	 * @return Book
	 */
	public static function getInstance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}

	/**
	 * Unfortunate legacy code of only static methods that, per our own coding standards,
	 * should have been namespaced functions. Calling this constructor is pointless.
	 */
	private function __construct() {
	}

	/**
	 * Prevent the instance from being cloned (which would create a second instance of it)
	 */
	private function __clone() {
	}

	/**
	 * Prevent from being unserialized (which would create a second instance of it)
	 */
	public function __wakeup() {
	}

	/**
	 * Check if the current blog_id is considered a "book"
	 *
	 * @return bool
	 */
	static function isBook() {

		// Currently, the main site is considered a "blog/landing page" whereas everything else is considered a "book".
		// We might improve this in the future.

		$is_book = ( is_main_site() === false );

		return $is_book;
	}

	/**
	 * Returns book information in a useful, string only, format. Data is converted to HTML.
	 *
	 * @param int $id The book ID.
	 * @param bool $contributors_as_string Read contributors list as a string.
	 * @param bool $read_contributors_from_cache Read contributors from cache, if book information is stored in wp cache.
	 *
	 * @return array
	 */
	static function getBookInformation( $id = null, $contributors_as_string = true, $read_contributors_from_cache = true ) {

		if ( ! empty( $id ) && is_int( $id ) ) {
			$blog_id = $id;
			switch_to_blog( $blog_id );
		} else {
			global $blog_id;
		}

		// ----------------------------------------------------------------------------
		// Book Information
		// ----------------------------------------------------------------------------

		$book_information = [];
		$meta = new Metadata();
		$meta_post = $meta->getMetaPost();

		$contributors = new Contributors();

		// -----------------------------------------------------------------------------
		// Is cached?
		// -----------------------------------------------------------------------------

		$cache_id = "book-inf-$blog_id";
		if ( static::useCache() ) {
			$cached_book_information = wp_cache_get( $cache_id, 'pb' );
			if ( $cached_book_information ) {
				$contributors_cached_as_string = true;
				foreach ( $contributors->valid as $contributor_type ) {
					if ( isset( $cached_book_information[ $contributor_type ] ) ) {
						$contributors_cached_as_string = is_string( $cached_book_information[ $contributor_type ] );
						break;
					}
				}
				if ( $meta_post && ( ! $read_contributors_from_cache || $contributors_cached_as_string !== $contributors_as_string ) ) {
					$cached_book_information = array_merge(
						$cached_book_information,
						$contributors->getAll(
							$meta_post->ID,
							$contributors_as_string,
							true
						)
					);
				}
				return $cached_book_information;
			}
		}

		if ( $meta_post ) {
			// Contributors
			$book_information = array_merge(
				$book_information,
				$contributors->getAll(
					$meta_post->ID,
					$contributors_as_string,
					true
				)
			);

			// Post Meta
			$expected_array = [ 'pb_keywords_tags', 'pb_additional_subjects', 'pb_bisac_subject' ];
			$expected_the_content = [ 'pb_custom_copyright', 'pb_about_unlimited' ];
			$expected_url = [ 'pb_cover_image' ];
			foreach ( get_post_meta( $meta_post->ID ) as $key => $val ) {
				// Skip anything not prefixed with pb_
				if ( 0 !== strpos( $key, 'pb_' ) ) {
					continue;
				}
				// Skip contributor meta (already done, look up)
				if ( $contributors->isValid( $key ) || $contributors->isDeprecated( $key ) ) {
					continue;
				}

				if ( $key === 'pb_institutions' ) {
					$book_information[ $key ] = $val;

					continue;
				}

				// We only care about strings
				if ( is_array( $val ) ) {
					if ( false !== in_array( $key, $expected_array, true ) ) {
						$val = implode( ', ', $val );
					} else {
						$val = array_values( $val );
						$val = array_pop( $val );
					}
				}

				// Skip empty values
				if ( ! trim( $val ) ) {
					continue;
				}

				if ( false !== in_array( $key, $expected_the_content, true ) ) {
					$val = wptexturize( $val );
					$val = wpautop( $val );
				} else {
					$val = htmlspecialchars( $val, ENT_NOQUOTES | ENT_XHTML, 'UTF-8', false );
				}

				// Normalize URLs
				if ( in_array( $key, $expected_url, true ) ) {
					$val = set_url_scheme( $val );
				}

				// Remove invisible control characters that break XML
				$val = \Pressbooks\Sanitize\remove_control_characters( $val );

				$book_information[ $key ] = $val;
			}
		}

		// Return our best guess if no book information has been entered.
		if ( empty( $book_information ) ) {
			$book_information['pb_title'] = get_bloginfo( 'name' );
			if ( ! function_exists( 'get_user_by' ) ) {
				include( ABSPATH . 'wp-includes/pluggable.php' );
			}
			$author = get_user_by( 'email', get_bloginfo( 'admin_email' ) );
			$author_metadata = $author->display_name ?? '';
			$book_information['pb_authors'] = $contributors_as_string ? $author_metadata : [ [ 'name' => $author_metadata ] ];
			$book_information['pb_cover_image'] = \Pressbooks\Image\default_cover_url();
		}

		// -----------------------------------------------------------------------------
		// Cache & Return
		// -----------------------------------------------------------------------------

		if ( static::useCache() ) {
			wp_cache_set( $cache_id, $book_information, 'pb', DAY_IN_SECONDS );
		}

		if ( ! empty( $id ) && is_int( $id ) ) {
			restore_current_blog();
		}

		return $book_information;
	}

	/**
	 * Get global, web, pdf and ebook theme options
	 *
	 * @return array
	 */
	public static function getThemeOptions() : array {
		$options_classes = [
			'\Pressbooks\Modules\ThemeOptions\GlobalOptions',
			'\Pressbooks\Modules\ThemeOptions\WebOptions',
			'\Pressbooks\Modules\ThemeOptions\PDFOptions',
			'\Pressbooks\Modules\ThemeOptions\EbookOptions',
		];

		return array_reduce( $options_classes, static function( $theme_options, $option_class ) {
			$slug = call_user_func( $option_class . '::getSlug' );
			$options = get_option( 'pressbooks_theme_options_' . $slug );
			return $options ?
				array_merge( $theme_options, [ $slug => ( new $option_class( $options ) )->options ] ) :
				$theme_options;
		}, [] );
	}

	/**
	 * Add notice about invalidated Bisac codes if there are 1 or more invalidated codes in metadata.
	 *
	 * @return bool
	 */
	static function notifyBisacCodesRemoved() {
		global $blog_id;
		$book_data_collector = BookDataCollector::init();
		$book_information_array = $book_data_collector->get( $blog_id, BookDataCollector::BOOK_INFORMATION_ARRAY );
		if ( self::removeInvalidatedBisacCodes( $blog_id, $book_information_array ) ) {
			add_error( __(
				"This book was using a <a href='https://bisg.org/page/InactivatedCodes' target='_blank'> retired BISAC subject term </a>, which has been replaced in your book with a recommended BISAC replacement. You may wish to check the BISAC subject terms manually to confirm that you are satisfied with these replacements."
			) );
			return true;
		}
		return false;
	}

	/**
	 * Delete invalidated Bisac Codes from blogmeta and postmeta tables.
	 *
	 * @param int $blog_id
	 * @param array $book_information_array
	 * @return bool
	 */
	static function removeInvalidatedBisacCodes( int $blog_id, array $book_information_array ) {
		if ( array_key_exists( 'pb_bisac_subject', $book_information_array ) ) {
			$book_information_array['pb_bisac_subject'] = explode(
				', ',
				$book_information_array['pb_bisac_subject']
			);
			$book_information_array['pb_bisac_subject'] = self::getReplacementForInvalidatedBisacCodes(
				$book_information_array['pb_bisac_subject']
			);
			$book_information_array['pb_bisac_subject'] = join(
				', ',
				$book_information_array['pb_bisac_subject']
			);
			return self::removeInvalidatedBisacCodesFromPostMeta() &&
				update_site_meta( $blog_id, BookDataCollector::BOOK_INFORMATION_ARRAY, $book_information_array );
		}
		return false;
	}

	/**
	 * Remove invalidated Bisac codes from postmeta table.
	 *
	 * @return bool
	 */
	static function removeInvalidatedBisacCodesFromPostMeta() {
		$meta = new Metadata();
		$meta_post = $meta->getMetaPost();
		$metadata = get_post_meta( $meta_post->ID );
		if ( array_key_exists( 'pb_bisac_subject', $metadata ) ) {
			$metadata['pb_bisac_subject'] = self::getReplacementForInvalidatedBisacCodes( $metadata['pb_bisac_subject'] );
			delete_post_meta( $meta_post->ID, 'pb_bisac_subject' );
			foreach ( $metadata['pb_bisac_subject'] as $bisac_code ) {
				add_metadata( 'post', $meta_post->ID, 'pb_bisac_subject', $bisac_code );
			}
			return true;
		}
		return false;
	}

	/**
	 * Get invalidated Bisac codes replacement given a list of Bisac codes.
	 *
	 * @param array $bisac_codes
	 * @return array
	 */
	static function getReplacementForInvalidatedBisacCodes( array $bisac_codes ) {
		return apply_filters( 'get_invalidated_codes_alternatives_mapped', $bisac_codes );
	}

	/**
	 * Returns an array representing the entire structure of a book, in correct order,
	 * with a minimum amount of fields. Data is raw and must be post-processed.
	 *
	 * @see bottom of this file for more info
	 *
	 * @param int $id
	 *
	 * @return array
	 */
	static function getBookStructure( $id = null ) {

		if ( ! empty( $id ) && is_int( $id ) ) {
			$blog_id = $id;
			switch_to_blog( $id );
		} else {
			global $blog_id;
		}

		// -----------------------------------------------------------------------------
		// Is cached?
		// -----------------------------------------------------------------------------

		$cache_id = "book-str-$blog_id";
		if ( static::useCache() ) {
			$book_structure = wp_cache_get( $cache_id, 'pb' );
			if ( $book_structure ) {
				return $book_structure;
			}
		}

		// -----------------------------------------------------------------------------
		// Query our custom post types, keep minimal data in $book_structure
		// -----------------------------------------------------------------------------

		$post_ids_to_export = static::getPostsIdsToExport();

		$custom_types = [
			'front-matter',
			'part',
			'chapter',
			'back-matter',
		];

		$book_structure = [];
		foreach ( $custom_types as $type ) {
			$book_structure[ $type ] = [];
		}

		$q = new \WP_Query();
		$results = $q->query(
			[
				'post_type' => $custom_types,
				'posts_per_page' => -1, // @phpcs:ignore
				'post_status' => 'any',
				'orderby' => 'menu_order',
				'order' => 'ASC',
				'no_found_rows' => true,
				'cache_results' => true,
			]
		);

		/** @var \WP_Post $post */
		foreach ( $results as $post ) {
			// Fix empty slugs
			$post_name = empty( trim( $post->post_name ) ) ? uniqid( 'slug-' ) : $post->post_name;

			$book_structure[ $post->post_type ][] = [
				'ID' => $post->ID,
				'post_title' => $post->post_title,
				'post_name' => $post_name,
				'post_author' => (int) $post->post_author,
				'comment_count' => (int) $post->comment_count,
				'menu_order' => $post->menu_order,
				'post_status' => $post->post_status,
				'post_parent' => $post->post_parent,
				'export' => ( isset( $post_ids_to_export[ $post->ID ] ) && 'on' === $post_ids_to_export[ $post->ID ] ) ? true : false,
				'has_post_content' => ! empty( trim( $post->post_content ) ),
				'word_count' => \Pressbooks\Utility\word_count( $post->post_content ),
			];
		}

		// -----------------------------------------------------------------------------
		// Add Chapters to Parts
		// -----------------------------------------------------------------------------

		foreach ( $book_structure['part'] as $i => $part ) {
			// There's no `pb_export` for parts. We instead have `pb_part_invisible` and it doesn't mean the same thing. Force to true.
			$book_structure['part'][ $i ]['export'] = true;
			$book_structure['part'][ $i ]['chapters'] = [];
		}

		foreach ( $book_structure['chapter'] as $i => $chapter ) {
			foreach ( $book_structure['part'] as $j => $part ) {
				if ( $part['ID'] === $chapter['post_parent'] ) {
					$book_structure['part'][ $j ]['chapters'][] = $chapter;
					unset( $book_structure['chapter'][ $i ] );
					continue 2;
				}
			}
		}

		/* Track unexpected orphans, unset() chapter from $book_structure and $types */

		if ( count( $book_structure['chapter'] ) ) {
			$book_structure['__orphans'] = $book_structure['chapter'];
		}

		unset( $book_structure['chapter'] );
		$custom_types = array_diff( $custom_types, [ 'chapter' ] );

		// -----------------------------------------------------------------------------
		// Create __order arrays, remove post_parent
		// -----------------------------------------------------------------------------

		$book_structure['__order'] = [];
		foreach ( $custom_types as $type ) {
			foreach ( $book_structure[ $type ] as $i => $struct ) {
				unset( $book_structure[ $type ][ $i ]['post_parent'] );
				if ( 'part' !== $type ) {
					$book_structure['__order'][ $struct['ID'] ] = [
						'export' => $struct['export'],
						'post_status' => $struct['post_status'],
						'post_name' => $struct['post_name'],
						'post_type' => $type,
					];
				} else {
					if ( $struct['has_post_content'] && get_post_meta( $struct['ID'], 'pb_part_invisible', true ) !== 'on' ) {
						$book_structure['__order'][ $struct['ID'] ] = [
							'export' => $struct['export'],
							'post_status' => $struct['post_status'],
							'post_name' => $struct['post_name'],
							'post_type' => 'part',
						];
					}
					foreach ( $struct['chapters'] as $j => $chapter ) {
						unset( $book_structure[ $type ][ $i ]['chapters'][ $j ]['post_parent'] );
						if ( $struct['has_post_content'] && get_post_meta( $struct['ID'], 'pb_part_invisible', true ) !== 'on' ) {
							$book_structure['__order'][ $struct['ID'] ] = [
								'export' => $struct['export'],
								'post_status' => $struct['post_status'],
								'post_name' => $struct['post_name'],
								'post_type' => 'part',
							];
						}
						$book_structure['__order'][ $chapter['ID'] ] = [
							'export' => $chapter['export'],
							'post_status' => $chapter['post_status'],
							'post_name' => $chapter['post_name'],
							'post_type' => 'chapter',
						];
					}
				}
			}
		}
		static::$__order = $book_structure['__order'];

		// -----------------------------------------------------------------------------
		// Cache & Return
		// -----------------------------------------------------------------------------

		if ( static::useCache() ) {
			wp_cache_set( $cache_id, $book_structure, 'pb', DAY_IN_SECONDS );
		}

		if ( ! empty( $id ) && is_int( $id ) ) {
			restore_current_blog();
		}

		return $book_structure;
	}

	/**
	 * Returns an array representing the entire structure of a book, in correct order,
	 * with a maximum amount of fields. Data is raw and must be post-processed.
	 *
	 * @see bottom of this file for more info
	 * @return array
	 */
	static function getBookContents() {

		global $blog_id;

		// -----------------------------------------------------------------------------
		// Is cached?
		// -----------------------------------------------------------------------------

		$cache_id = "book-cnt-$blog_id";
		if ( static::useCache() ) {
			$book_contents = wp_cache_get( $cache_id, 'pb' );
			if ( $book_contents ) {
				return $book_contents;
			}
		}

		// -----------------------------------------------------------------------------
		// Using + to merge arrays...
		// Precedence when using the + operator to merge arrays is from left to right
		// -----------------------------------------------------------------------------

		$book_contents = static::getBookStructure();

		foreach ( $book_contents as $type => $struct ) {

			if ( preg_match( '/^__/', $type ) ) {
				continue; // Skip __magic keys
			}

			if ( 'part' === $type ) {
				foreach ( $struct as $i => $part ) {
					$book_contents[ $type ][ $i ] = $part + get_post( $part['ID'], ARRAY_A );
					foreach ( $part['chapters'] as $j => $chapter ) {
						$book_contents[ $type ][ $i ]['chapters'][ $j ] = $chapter + get_post( $chapter['ID'], ARRAY_A );
					}
				}
			} else {
				foreach ( $struct as $i => $val ) {
					$book_contents[ $type ][ $i ] = $val + get_post( $val['ID'], ARRAY_A );
				}
			}
		}

		// -----------------------------------------------------------------------------
		// Cache & Return
		// -----------------------------------------------------------------------------

		if ( static::useCache() ) {
			wp_cache_set( $cache_id, $book_contents, 'pb', DAY_IN_SECONDS );
		}

		return $book_contents;
	}

	/**
	 * @param bool $selected_for_export (optional)
	 *
	 * @return int
	 */
	static function wordCount( $selected_for_export = false ) {
		$wc = 0;
		$wc_selected_for_export = 0;
		foreach ( static::getBookStructure() as $key => $section ) {
			if ( $key === 'front-matter' || $key === 'back-matter' ) {
				foreach ( $section as $val ) {
					$wc += $val['word_count'];
					if ( $val['export'] ) {
						$wc_selected_for_export += $val['word_count'];
					}
				}
			}
			if ( $key === 'part' ) {
				foreach ( $section as $part ) {
					foreach ( $part['chapters'] as $val ) {
						$wc += $val['word_count'];
						if ( $val['export'] ) {
							$wc_selected_for_export += $val['word_count'];
						}
					}
				}
			}
		}

		return $selected_for_export ? $wc_selected_for_export : $wc;
	}

	/**
	 *
	 */
	static function ajaxWordCount() {
		if ( check_ajax_referer( 'pb-update-word-count-for-export' ) ) {
			echo \Pressbooks\Book::wordCount( true );
			wp_die();
		}
	}

	/**
	 * Delete the Book Object cache(s)
	 */
	static function deleteBookObjectCache() {

		global $blog_id;

		// Book Object
		wp_cache_delete( "book-inf-$blog_id", 'pb' ); // Delete the cached value for getBookInformation()
		wp_cache_delete( "book-str-$blog_id", 'pb' ); // Delete the cached value for getBookStructure()
		wp_cache_delete( "book-cnt-$blog_id", 'pb' ); // Delete the cached value for getBookContents()
		static::$__order = [];

		// Subsections
		delete_transient( static::SUBSECTIONS_TRANSIENT );

		// User Catalog
		( new Catalog() )->deleteCacheByBookId( $blog_id );

		// Output buffers
		delete_transient( Xhtml11::TRANSIENT );

		/**
		 * @since 5.0.0
		 *
		 * @param int $blog_id
		 */
		do_action( 'pb_cache_delete', $blog_id );
		set_transient( 'pb_cache_deleted', time(), DAY_IN_SECONDS );
	}

	/**
	 * Returns an array of subsections in front matter, back matter, or chapters.
	 *
	 * @param $id
	 *
	 * @return array|false
	 */
	static function getSubsections( $id ) {
		$parent = get_post( $id );
		if ( empty( $parent ) ) {
			return false;
		}
		$has_heading_shortcode = has_shortcode( $parent->post_content, 'heading' );
		if ( stripos( $parent->post_content, '<h1' ) === false && $has_heading_shortcode === false ) {
			// No <h1> or [heading] shortcode, nothing to do
			return false;
		}

		if ( $has_heading_shortcode ) {
			// Only render heading shortcode into <h1> if we have to
			$content = \Pressbooks\Utility\do_shortcode_by_tags( $parent->post_content, [ 'heading' ] );
			$content = strip_shortcodes( $content );
		} else {
			$content = $parent->post_content;
		}
		$content = strip_tags( $content, '<h1>' );  // Strip everything except <h1> to speed up load time

		$type = $parent->post_type;
		$output = [];
		$s = 1;

		$doc = new HtmlParser( true ); // Because we are not saving, use internal parser to speed up load time
		$dom = $doc->loadHTML( $content );
		$sections = $dom->getElementsByTagName( 'h1' );
		foreach ( $sections as $section ) {
			/** @var $section \DOMElement */
			$output[ $type . '-' . $id . '-section-' . $s ] = wptexturize( $section->textContent );
			$s++;
		}

		if ( empty( $output ) ) {
			return false;
		}

		return $output;
	}

	/**
	 * Returns an array of front matter, chapters, and back matter which contain subsections.
	 *
	 * @param array $book_structure The book structure from getBookStructure()
	 * @return array The subsections, grouped by parent post type
	 */
	static function getAllSubsections( $book_structure ) {
		if ( Export::shouldParseSubsections() ) {
			$book_subsections_transient = \Pressbooks\Book::SUBSECTIONS_TRANSIENT;
			$subsection_processing_transient = \Pressbooks\Book::SUBSECTION_PROCESSING_TRANSIENT;
			$book_subsections = get_transient( $book_subsections_transient );
			if ( ! $book_subsections ) {
				$book_subsections = [];
				if ( ! get_transient( $subsection_processing_transient ) ) {
					set_transient( $subsection_processing_transient, 1, 5 * MINUTE_IN_SECONDS );
					foreach ( $book_structure['front-matter'] as $section ) {
						$subsections = \Pressbooks\Book::getSubsections( $section['ID'] );
						if ( $subsections ) {
							$book_subsections['front-matter'][ $section['ID'] ] = $subsections;
						}
					}
					foreach ( $book_structure['part'] as $key => $part ) {
						if ( ! empty( $part['chapters'] ) ) {
							foreach ( $part['chapters'] as $section ) {
								$subsections = \Pressbooks\Book::getSubsections( $section['ID'] );
								if ( $subsections ) {
									$book_subsections['chapters'][ $section['ID'] ] = $subsections;
								}
							}
						}
					}
					foreach ( $book_structure['back-matter'] as $section ) {
						$subsections = \Pressbooks\Book::getSubsections( $section['ID'] );
						if ( $subsections ) {
							$book_subsections['back-matter'][ $section['ID'] ] = $subsections;
						}
					}
					delete_transient( $subsection_processing_transient );
				}
			}
			set_transient( $book_subsections_transient, $book_subsections );
			return $book_subsections;
		}
		return [];
	}

	/**
	 * Returns chapter, front or back matter content with section ID and classes added.
	 *
	 * @param string $content
	 * @param int $id
	 *
	 * @return string|false
	 */
	static function tagSubsections( $content, $id ) {
		$parent = get_post( $id );
		if ( empty( $parent ) ) {
			return false;
		}
		if ( stripos( $content, '<h1' ) === false ) {
			return false;
		}

		$type = $parent->post_type;
		$s = 1;

		$doc = new HtmlParser();
		$dom = $doc->loadHTML( $content );
		$sections = $dom->getElementsByTagName( 'h1' );
		foreach ( $sections as $section ) {
			/** @var $section \DOMElement */
			$old_id = $section->getAttribute( 'id' );
			$old_class = $section->getAttribute( 'class' );
			$new_id = "{$type}-{$id}-section-" . $s++;
			$new_class = trim( "section-header {$old_class} {$old_id}" );
			$section->setAttribute( 'id', $new_id );
			$section->setAttribute( 'class', $new_class );
		}

		return $doc->saveHTML( $dom );
	}

	/**
	 * WP_Ajax hook. Updates a post's privacy setting (whether the post is published or privately published)
	 */
	static function updateGlobalPrivacyOptions() {
		if ( check_ajax_referer( 'pb-organize-book-privacy' ) ) {
			$blog_public = absint( $_POST['blog_public'] );

			if ( current_user_can( 'manage_options' ) ) {
				update_option( 'blog_public', $blog_public );
			}
		}
	}

	/**
	 * Fetch next, previous or first post
	 *
	 * @param string $what prev, next or first
	 * @param bool $return_post_id (optional)
	 * @param bool $admin_mode (optional)
	 *
	 * @return mixed URL of requested post, or Post ID if $return_post_id is set to true
	 */
	static function get( $what = 'next', $return_post_id = false, $admin_mode = false ) {

		if ( 'first' === $what ) {
			return static::getFirst( $return_post_id, $admin_mode );
		}

		global $blog_id;

		global $post;

		$current_post_id = $post->ID;
		$book_structure = static::getBookStructure();
		$order = $book_structure['__order'];
		$pos = array_keys( $order );

		$what = ( 'next' === $what ? 'next' : 'prev' );

		// Move internal pointer to correct position
		reset( $pos );
		while ( $find_me = current( $pos ) ) { // @phpcs:ignore
			if ( (int) $find_me === (int) $current_post_id ) {
				break;
			} else {
				next( $pos );
			}
		}

		// Get next/previous
		$what( $pos );
		while ( $post_id = current( $pos ) ) { // @phpcs:ignore
			if ( $admin_mode ) {
				if ( current_user_can( 'edit_post', $post_id ) ) {
					break;
				} else {
					$what( $pos );
				}
			} else {
				if ( in_array( $order[ $post_id ]['post_status'], [ 'publish', 'web-only' ], true ) ) {
					break;
				} elseif ( current_user_can_for_blog( $blog_id, 'read_private_posts' ) ) {
					break;
				} elseif ( get_option( 'permissive_private_content' ) && current_user_can_for_blog( $blog_id, 'read' ) ) {
					break;
				} else {
					$what( $pos );
				}
			}
		}

		if ( $return_post_id ) {
			return (int) $post_id;
		} else {
			return ( empty( $post_id ) ) ? '/' : get_permalink( $post_id );
		}
	}

	/**
	 * Select the very first post in a book. May be a chapter or a front matter post
	 *
	 * @param bool $return_post_id (optional)
	 * @param bool $admin_mode (optional)
	 *
	 * @return mixed URL of first post, or Post ID if $return_post_id is set to true
	 */
	static function getFirst( $return_post_id = false, $admin_mode = false ) {

		global $blog_id;

		$book_structure = static::getBookStructure();
		$order = $book_structure['__order'];
		$pos = array_keys( $order );

		reset( $pos );
		while ( $first_id = current( $pos ) ) { // @phpcs:disable WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
			if ( $admin_mode ) {
				if ( current_user_can( 'edit_post', $first_id ) ) {
					break;
				} else {
					next( $pos );
				}
			} else {
				if ( in_array( $order[ $first_id ]['post_status'], [ 'publish', 'web-only' ], true ) ) {
					break;
				} elseif ( current_user_can_for_blog( $blog_id, 'read_private_posts' ) ) {
					break;
				} elseif ( get_option( 'permissive_private_content' ) && current_user_can_for_blog( $blog_id, 'read' ) ) {
					break;
				} else {
					next( $pos );
				}
			}
		}

		if ( $return_post_id ) {
			return (int) $first_id;
		} else {
			return ( empty( $first_id ) ) ? '/' : get_permalink( $first_id );
		}

	}

	/**
	 * @since 5.2.0
	 *
	 * @param $post_id
	 * @param string $type_of (optional) webbook, exports
	 *
	 * @return int
	 */
	static function getChapterNumber( $post_id, $type_of = 'webbook' ) {

		if ( empty( static::$__order ) ) {
			self::$__order = static::getBookStructure()['__order'];
		}
		$lookup = static::$__order;

		if ( $type_of === 'webbook' ) {
			$post_statii = [ 'web-only', 'publish' ];
		} else {
			$post_statii = [ 'private', 'publish' ];
		}

		// Sometimes the chapter number is zero, these are the reasons:
		if (
			empty( get_option( 'pressbooks_theme_options_global', [] )['chapter_numbers'] ) ||
			empty( $lookup[ $post_id ] ) ||
			$lookup[ $post_id ]['post_type'] !== 'chapter' ||
			! in_array( $lookup[ $post_id ]['post_status'], $post_statii, true )
		) {
			return 0;
		}

		// Calculate chapter number
		$i = 0;
		$type = 'standard';
		$found = array_merge( [ 'ID' => $post_id ], $lookup[ $post_id ] ); // @phpcs:ignore
		foreach ( $lookup as $post_id => $val ) {
			if (
				$val['post_type'] !== 'chapter' ||
				! in_array( $val['post_status'], $post_statii, true )
			) {
				continue; // Skip
			}
			$type = \Pressbooks\Taxonomy::init()->getChapterType( $post_id );
			if ( 'numberless' !== $type ) {
				++$i; // Increase real chapter number
			}
			if ( $post_id === $found['ID'] ) {
				break;
			}
		}

		return ( $type === 'numberless' ) ? 0 : $i;
	}

	/**
	 * Ensures this chapter/part/front matter has a "menu_order" when it is saved
	 *
	 * @param integer $pid Post ID
	 * @param \WP_Post $post Post
	 *
	 * @return bool
	 */
	static function consolidatePost( $pid, $post ) {

		if ( false === Book::isBook() || wp_is_post_revision( $pid ) || 'auto-draft' === get_post_status( $pid ) ) {
			return false;
		}

		/** @var $wpdb \wpdb */
		global $wpdb;
		$success = true;

		// if this is a new post, set its order
		if ( empty( $post->menu_order ) ) {
			if ( 'chapter' === $post->post_type ) {
				$new = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT max({$wpdb->posts}.menu_order) + 1
						FROM {$wpdb->posts}
						WHERE {$wpdb->posts}.post_type = %s
						AND NOT {$wpdb->posts}.post_status = 'trash'
						AND {$wpdb->posts}.post_parent = %s ",
						$post->post_type,
						$post->post_parent
					)
				);
			} else {
				$new = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT max({$wpdb->posts}.menu_order) + 1
						FROM {$wpdb->posts}
						WHERE {$wpdb->posts}.post_type = %s
						AND NOT {$wpdb->posts}.post_status = 'trash' ",
						$post->post_type
					)
				);
			}

			if ( empty( $new ) ) {
				$new = 1;
			} else {
				$new = absint( $new );
			}

			$success = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts}
					SET {$wpdb->posts}.menu_order = %d
					WHERE {$wpdb->posts}.ID = %d ",
					$new,
					$post->ID
				)
			);
			clean_post_cache( $post );

		}

		return $success ? true : false;
	}

	/**
	 * Put a Part/Chapter/Front Matter/Back Matter in the trash
	 *
	 * @param int $pid
	 *
	 * @return bool
	 */
	static function deletePost( $pid ) {

		if ( false === Book::isBook() || wp_is_post_revision( $pid ) || 'auto-draft' === get_post_status( $pid ) ) {
			return false;
		}

		/** @var $wpdb \wpdb */
		global $wpdb;

		// remove chapter/part/front matter
		// decrement order of everything with a higher order, and if chapter, only within that part

		$post_to_delete = get_post( $pid );
		$order = $post_to_delete->menu_order;
		$type = $post_to_delete->post_type;
		$parent = $post_to_delete->post_parent;

		if ( 'chapter' === $type ) {
			$success = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts} SET menu_order = menu_order - 1 WHERE menu_order > %d AND post_type = %s AND post_parent = %d ",
					$order,
					$type,
					$parent
				)
			);
		} else {
			$success = $wpdb->query(
				$wpdb->prepare(
					"UPDATE {$wpdb->posts} SET menu_order = menu_order - 1 WHERE menu_order > %d AND post_type = %s ",
					$order,
					$type
				)
			);
		}

		clean_post_cache( $post_to_delete );

		if ( 'part' === $type ) {

			// We're setting two things here - the new post_parent (to the first part)
			// And the new menu order for the chapters that were in the part being deleted.

			$new_parent_id = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'part' AND post_status = 'publish' AND NOT ID = %d ORDER BY menu_order LIMIT 1 ",
					$pid
				)
			);

			if ( $new_parent_id ) {
				$existing_numposts = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(1) AS numposts FROM {$wpdb->posts} WHERE post_type = 'chapter' AND post_parent = %d ",
						$new_parent_id
					)
				);
				$success = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->posts} SET post_parent = %d, menu_order = menu_order + %d WHERE post_parent = %d AND post_type = 'chapter' ",
						$new_parent_id,
						$existing_numposts,
						$pid
					)
				);
			} else {
				$success = $wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->posts} SET post_status = 'trash' WHERE post_parent = %d AND post_type = 'chapter' ",
						$pid
					)
				);
			}

			wp_cache_flush();
		}

		static::deleteBookObjectCache();

		return $success ? true : false;
	}

	/**
	 * Fetch all pb_export meta values for this book
	 *
	 * @return array
	 */
	static protected function getPostsIdsToExport() {

		$post_ids_to_export = [];

		global $wpdb;
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ID FROM {$wpdb->posts} WHERE post_status IN (%s, %s) AND post_type IN (%s, %s, %s, %s, %s)",
				[ 'private', 'publish', 'front-matter', 'part', 'chapter', 'back-matter', 'glossary' ]
			), ARRAY_A
		);
		foreach ( $results as $val ) {
			$post_ids_to_export[ $val['ID'] ] = 'on';
		}

		return $post_ids_to_export;
	}

	/**
	 * Use cache?
	 *
	 * @return bool
	 */
	static protected function useCache() {
		// Placeholder for a reason to skip cache. Example: a preview feature.
		return true;
	}

}
