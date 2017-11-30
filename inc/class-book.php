<?php
/**
 * A book is a discrete, collection of text (and other media), that is designed by an author(s) as an internally
 * complete representation of an idea, or set of ideas; emotion or set of emotions; and transmitted to readers in
 * various formats.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks;

use Masterminds\HTML5;

class Book {

	/**
	 * @var Book
	 */
	protected static $instance;

	/**
	 * Array of preview ids
	 *
	 * Note: If you set this property, but also set $_REQUEST['preview'], then $_REQUEST['preview'] will override.
	 * Request format: ?preview[0]=111&preview[1]=222&preview[2]=333...
	 *
	 * @var array
	 */
	static $preview = [];

	/**
	 * Fix duplicate slugs.
	 * This can happen if a post is 'draft', 'pending', or 'auto-draft'
	 *
	 * @see wp_unique_post_slug()
	 * @var array
	 */
	static $fixDupeSlugs = [];

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
	private function __wakeup() {
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
	 *
	 * @return array
	 */
	static function getBookInformation( $id = null ) {

		if ( ! empty( $id ) && is_int( $id ) ) {
			$blog_id = $id;
			switch_to_blog( $blog_id );
		} else {
			global $blog_id;
		}

		// -----------------------------------------------------------------------------
		// Is cached?
		// -----------------------------------------------------------------------------

		$cache_id = "book-inf-$blog_id";
		if ( static::useCache() ) {
			$book_information = wp_cache_get( $cache_id, 'pb' );
			if ( $book_information ) {
				return $book_information;
			}
		}

		// ----------------------------------------------------------------------------
		// Book Information
		// ----------------------------------------------------------------------------

		$expected_array = [ 'pb_keywords_tags', 'pb_additional_subjects', 'pb_bisac_subject', 'pb_contributing_authors', 'pb_editor', 'pb_translator' ];
		$expected_the_content = [ 'pb_custom_copyright', 'pb_about_unlimited' ];
		$expected_url = [ 'pb_cover_image' ];

		$book_information = [];
		$meta = new Metadata();
		$data = $meta->getMetaPostMetadata();

		foreach ( $data as $key => $val ) {

			// Skip anything not prefixed with pb_
			if ( ! preg_match( '/^pb_/', $key ) ) {
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

		// Return our best guess if no book information has been entered.
		if ( empty( $book_information ) ) {
			$book_information['pb_title'] = get_bloginfo( 'name' );
			if ( ! function_exists( 'get_user_by' ) ) {
				include( ABSPATH . 'wp-includes/pluggable.php' );
			}
			$author = get_user_by( 'email', get_bloginfo( 'admin_email' ) );
			$book_information['pb_author'] = isset( $author->display_name ) ? $author->display_name : '';
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
				'posts_per_page' => -1, // @codingStandardsIgnoreLine
				'post_status' => 'any',
				'orderby' => 'menu_order',
				'order' => 'ASC',
				'no_found_rows' => true,
				'cache_results' => true,
			]
		);

		/** @var \WP_Post $post */
		foreach ( $results as $post ) {
			$book_structure[ $post->post_type ][] = [
				'ID' => $post->ID,
				'post_title' => $post->post_title,
				'post_name' => static::fixSlug( $post->post_name ),
				'post_author' => (int) $post->post_author,
				'comment_count' => (int) $post->comment_count,
				'menu_order' => $post->menu_order,
				'post_status' => $post->post_status,
				'export' => ( isset( $post_ids_to_export[ $post->ID ] ) && 'on' === $post_ids_to_export[ $post->ID ] ) ? true : false,
				'has_post_content' => ! empty( trim( $post->post_content ) ),
				'word_count' => \Pressbooks\Utility\word_count( $post->post_content ),
				'post_parent' => $post->post_parent,
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
		// Create __order and __lookup arrays, remove post_parent
		// -----------------------------------------------------------------------------

		$book_structure['__order'] = [];
		$book_structure['__export_lookup'] = [];

		foreach ( $custom_types as $type ) {
			foreach ( $book_structure[ $type ] as $i => $struct ) {
				unset( $book_structure[ $type ][ $i ]['post_parent'] );
				if ( 'part' !== $type ) {
					$book_structure['__order'][ $struct['ID'] ] = [ 'export' => $struct['export'], 'post_status' => $struct['post_status'] ];
					if ( $struct['export'] ) {
						$book_structure['__export_lookup'][ $struct['post_name'] ] = $type;
					}
				} else {
					foreach ( $struct['chapters'] as $j => $chapter ) {
						unset( $book_structure[ $type ][ $i ]['chapters'][ $j ]['post_parent'] );
						if ( $struct['has_post_content'] && get_post_meta( $struct['ID'], 'pb_part_invisible', true ) !== 'on' ) {
							$book_structure['__order'][ $struct['ID'] ] = [ 'export' => $struct['export'], 'post_status' => $struct['post_status'] ];
						}
						$book_structure['__order'][ $chapter['ID'] ] = [ 'export' => $chapter['export'], 'post_status' => $chapter['post_status'] ];
						if ( $chapter['export'] ) {
							$book_structure['__export_lookup'][ $chapter['post_name'] ] = 'chapter';
						}
					}
				}
			}
		}

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
	 * @return int
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

		wp_cache_delete( "book-inf-$blog_id", 'pb' ); // Delete the cached value for getBookInfo()
		wp_cache_delete( "book-str-$blog_id", 'pb' ); // Delete the cached value for getBookStructure()
		wp_cache_delete( "book-cnt-$blog_id", 'pb' ); // Delete the cached value for getBookContents()
		( new Catalog() )->deleteCacheByBookId( $blog_id );
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
		$type = $parent->post_type;
		$output = [];
		$s = 1;

		$content = wptexturize( $parent->post_content );
		$content = wpautop( $content );
		$content = mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' );

		if ( stripos( $content, '<h1' ) === false ) {
			return false;
		}

		$doc = new HTML5();
		$dom = $doc->loadHTML( strip_tags( $content, '<h1>' ) ); // Strip everything except h1 to speed up load time
		$sections = $dom->getElementsByTagName( 'h1' );
		foreach ( $sections as $section ) {
			$output[ $type . '-' . $id . '-section-' . $s ] = $section->textContent;
			$s++;
		}

		if ( empty( $output ) ) {
			return false;
		}

		return $output;
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

		$s = 1;
		$parent = get_post( $id );
		if ( empty( $parent ) ) {
			return false;
		}
		$type = $parent->post_type;

		// Fix unusual HTML that tends to break our DOM transform (issues/228)
		$content = mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' );
		$content = str_ireplace( [ '<b></b>', '<i></i>', '<strong></strong>', '<em></em>' ], '', $content );

		if ( stripos( $content, '<h1' ) === false ) {
			return false;
		}

		$doc = new HTML5( [ 'disable_html_ns' => true ] ); // Disable default namespace for \DOMXPath compatibility
		$dom = $doc->loadHTML( $content );
		$sections = $dom->getElementsByTagName( 'h1' );
		foreach ( $sections as $section ) {
			/** @var $section \DOMElement */
			$section->setAttribute( 'id', $type . '-' . $id . '-section-' . $s++ );
			$section->setAttribute( 'class', 'section-header' );
		}
		$xpath = new \DOMXPath( $dom );
		while ( ( $nodes = $xpath->query( '//*[not(text() or node() or self::br or self::hr or self::img)]' ) ) && $nodes->length > 0 ) {
			foreach ( $nodes as $node ) {
				/** @var $node \DOMElement */
				$node->appendChild( new \DOMText( '' ) );
			}
		}
		$html = $dom->saveXML( $dom->documentElement );

		return \Pressbooks\Sanitize\strip_container_tags( $html );
	}

	/**
	 * WP_Ajax hook. Updates the menu_order field associated with a chapter post after reordering it
	 * and update its associated part, if necessary
	 */
	static function updateChapter() {

		/** @var $wpdb \wpdb */
		global $wpdb;
		$id = absint( $_POST['id'] );
		if ( current_user_can( 'edit_post', $id ) && check_ajax_referer( 'pb-update-book-order' ) ) {

			parse_str( $_POST['new_part_order'], $new_part_order );
			parse_str( $_POST['old_part_order'], $old_part_order );
			$new_part = (int) $_POST['new_part'];
			$old_part = (int) $_POST['old_part'];

			// if the part for this chapter changed, set new part for chapter
			// and new order for this part
			if ( $new_part !== $old_part ) {

				$my_post = [];
				$my_post['ID'] = $id;
				$my_post['post_parent'] = $new_part;
				wp_update_post( $my_post );

				if ( is_array( $new_part_order ) ) {
					foreach ( $new_part_order as $key => $values ) {
						if ( 'chapter' === $key ) {
							foreach ( $values as $position => $id ) {
								$position += 1; // array is 0-indexed, but we want it to start from 1
								$wpdb->update( $wpdb->posts, [ 'menu_order' => $position ], [ 'ID' => $id ] );
								clean_post_cache( $id );
							}
						}
					}
				}
			}

			// always update the order of the part this chapter was originally in
			if ( is_array( $old_part_order ) ) {
				foreach ( $old_part_order as $key => $values ) {
					if ( 'chapter' === $key ) {
						foreach ( $values as $position => $id ) {
							$position += 1; // array is 0-indexed, but we want it to start from 1
							$wpdb->update( $wpdb->posts, [ 'menu_order' => $position ], [ 'ID' => $id ] );
							clean_post_cache( $id );
						}
					}
				}
			}
			static::deleteBookObjectCache();
		}

		// @see http://codex.wordpress.org/AJAX_in_Plugins#Error_Return_Values
		// Will append 0 to returned json string if we don't die()
		die();
	}


	/**
	 * WP_Ajax hook. Updates the menu_order field associated with a front matter post after reordering it
	 */
	static function updateFrontMatter() {

		/** @var $wpdb \wpdb */
		global $wpdb;

		if ( current_user_can( 'edit_posts' ) && check_ajax_referer( 'pb-update-book-order' ) ) {

			parse_str( $_POST['front_matter_order'], $front_matter_order );

			if ( is_array( $front_matter_order ) ) {
				foreach ( $front_matter_order as $key => $values ) {
					if ( 'front-matter' === $key ) {
						foreach ( $values as $position => $id ) {
							$position += 1;
							$wpdb->update( $wpdb->posts, [ 'menu_order' => $position ], [ 'ID' => $id ] );
							clean_post_cache( $id );
						}
					}
				}
			}
			static::deleteBookObjectCache();
		}
	}


	/**
	 * WP_Ajax hook. Updates the menu_order field associated with a back matter post after reordering it
	 */
	static function updateBackMatter() {

		/** @var $wpdb \wpdb */
		global $wpdb;

		if ( current_user_can( 'edit_posts' ) && check_ajax_referer( 'pb-update-book-order' ) ) {

			parse_str( $_POST['back_matter_order'], $back_matter_order );

			if ( is_array( $back_matter_order ) ) {
				foreach ( $back_matter_order as $key => $values ) {
					if ( 'back-matter' === $key ) {
						foreach ( $values as $position => $id ) {
							$position += 1;
							$wpdb->update( $wpdb->posts, [ 'menu_order' => $position ], [ 'ID' => $id ] );
							clean_post_cache( $id );
						}
					}
				}
			}
			static::deleteBookObjectCache();
		}
	}


	/**
	 * WP_Ajax hook. Updates a post's "export" setting (export post into book or not)
	 */
	static function updateExportOptions() {
		if ( check_ajax_referer( 'pb-update-book-export' ) ) {
			$post_id = absint( $_POST['post_id'] );
			if ( current_user_can( 'edit_post', $post_id ) ) {
				$valid_meta_keys = [
					'pb_export',
				];

				$meta_key = in_array( $_POST['type'], $valid_meta_keys, true ) ? $_POST['type'] : false;
				if ( $meta_key ) {
					$meta_value = ( $_POST['chapter_export'] ) ? 'on' : 0;
					update_post_meta( $post_id, $meta_key, $meta_value );
					static::deleteBookObjectCache();
				}
			}
		}
	}

	/**
	 * WP_Ajax hook. Updates a post's "show title" setting (show title in exports or not)
	 */
	static function updateShowTitleOptions() {
		if ( check_ajax_referer( 'pb-update-book-show-title' ) ) {
			$post_id = absint( $_POST['post_id'] );
			if ( current_user_can( 'edit_post', $post_id ) ) {
				$valid_meta_keys = [
					'pb_show_title',
				];

				$meta_key = in_array( $_POST['type'], $valid_meta_keys, true ) ? $_POST['type'] : false;
				if ( $meta_key ) {
					$meta_value = ( $_POST['chapter_show_title'] ) ? 'on' : 0;
					update_post_meta( $post_id, $meta_key, $meta_value );
					static::deleteBookObjectCache();
				}
			}
		}
	}

	/**
	 * WP_Ajax hook. Updates a post's privacy setting (whether the post is published or privately published)
	 */
	static function updatePrivacyOptions() {
		if ( check_ajax_referer( 'pb-update-book-privacy' ) ) {
			$post_id = absint( $_POST['post_id'] );
			if ( current_user_can( 'edit_post', $post_id ) ) {
				$post_status = $_POST['post_status'];

				$my_post = [];
				$my_post['ID'] = $post_id;
				$my_post['post_status'] = $post_status;

				wp_update_post( $my_post );
				static::deleteBookObjectCache();
			}
		}
	}

	/**
	 * WP_Ajax hook. Updates a post's privacy setting (whether the post is published or privately published)
	 */
	static function updateGlobalPrivacyOptions() {
		if ( check_ajax_referer( 'pb-update-book-privacy' ) ) {
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
	 *
	 * @return string URL of requested post
	 */
	static function get( $what = 'next' ) {

		if ( 'first' === $what ) {
			return static::getFirst();
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
		while ( $find_me = current( $pos ) ) {
			if ( $find_me === $current_post_id ) {
				break;
			} else {
				next( $pos );
			}
		}

		// Get next/previous
		$what( $pos );
		while ( $post_id = current( $pos ) ) {
			if ( 'publish' === $order[ $post_id ]['post_status'] ) {
				break;
			} elseif ( current_user_can_for_blog( $blog_id, 'read_private_posts' ) ) {
				break;
			} elseif ( get_option( 'permissive_private_content' ) && current_user_can_for_blog( $blog_id, 'read' ) ) {
				break;
			} else {
				$what( $pos );
			}
		}

		return ( empty( $post_id ) ) ? '/' : get_permalink( $post_id );
	}


	/**
	 * Select the very first post in a book. May be a chapter or a front matter post
	 *
	 * @return string permalink of the first post
	 */
	static function getFirst() {

		global $blog_id;

		$book_structure = static::getBookStructure();
		$order = $book_structure['__order'];
		$pos = array_keys( $order );

		reset( $pos );
		while ( $first_id = current( $pos ) ) {
			if ( 'publish' === $order[ $first_id ]['post_status'] ) {
				break;
			} elseif ( current_user_can_for_blog( $blog_id, 'read_private_posts' ) ) {
				break;
			} elseif ( get_option( 'permissive_private_content' ) && current_user_can_for_blog( $blog_id, 'read' ) ) {
				break;
			} else {
				next( $pos );
			}
		}

		return ( empty( $first_id ) ) ? '/' : get_permalink( $first_id );

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
	 * Fix empty slugs and Fix duplicate slugs.
	 * This can happen if a post is 'draft', 'pending', or 'auto-draft'
	 *
	 * @param string $old_post_name
	 *
	 * @return string
	 */
	static protected function fixSlug( $old_post_name ) {

		if ( ! trim( $old_post_name ) ) {
			$old_post_name = uniqid( 'slug-' );
		}

		if ( isset( static::$fixDupeSlugs[ $old_post_name ] ) ) {
			$new_post_name = $old_post_name . '-' . static::$fixDupeSlugs[ $old_post_name ];
			$i = 0;
			while ( isset( static::$fixDupeSlugs[ $new_post_name ] ) ) {
				++static::$fixDupeSlugs[ $new_post_name ];
				++static::$fixDupeSlugs[ $old_post_name ];
				$new_post_name = $old_post_name . '-' . static::$fixDupeSlugs[ $old_post_name ];
				if ( $i > 999 ) {
					break; // Safety
				}
				++$i;
			}
			$post_name = $new_post_name;
			static::$fixDupeSlugs[ $new_post_name ] = 1;
			++static::$fixDupeSlugs[ $old_post_name ];
		} else {

			$post_name = $old_post_name;
			static::$fixDupeSlugs[ $old_post_name ] = 1;
		}

		return $post_name;
	}

	/**
	 * Fetch all pb_export meta values for this book
	 *
	 * @return array
	 */
	static protected function getPostsIdsToExport() {

		$post_ids_to_export = [];

		if ( ! empty( $_REQUEST['preview'] ) ) {
			static::$preview = $_REQUEST['preview'];
		}

		if ( ! empty( static::$preview ) ) {
			// Preview mode
			$preview = is_array( static::$preview ) ? static::$preview : (array) static::$preview;
			foreach ( $preview as $id ) {
				$post_ids_to_export[ (int) $id ] = 'on';
			}
		} else {
			// Export mode
			global $wpdb;
			foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s", 'pb_export' ), ARRAY_A ) as $val ) {
				$post_ids_to_export[ $val['post_id'] ] = $val['meta_value'];
			}
		}

		return $post_ids_to_export;
	}

	/**
	 * Use cache?
	 *
	 * @return bool
	 */
	static protected function useCache() {

		if ( ! empty( static::$preview ) ) {
			return false;
		}

		if ( ! empty( $_REQUEST['preview'] ) ) {
			return false;
		}

		return true;
	}

}

/* --------------------------------------------------------------------------------------------------------------------

getBookStructure() and getBookContents() will return a multidimensional array or an (air quotes) "book object" that
contains everything Pressbooks considers a book. This book object is returned in the correct order so that, with
straightforward foreach() loops, a programmer or template designer can render a book however they see fit.

 * getBookStructure() returns a minimal subset of get_post( $post->ID, ARRAY_A ) plus our own custom key/values
 * getBookContents() returns the entirety of get_post( $post->ID, ARRAY_A ) plus our own custom key/values

getBookStructure() and getBookContents() will cache results using wp_cache_* functions. If you change the book, make
sure to call static::deleteBookObjectCache() for a sane user experience.

The book object looks something like this:

	$book_structure = [
		'front-matter' => [
			0 => [
				'export' => true,
				// key/values from: get_post( $post->ID, ARRAY_A ),
			],
			1 => [
				'export' => false,
				// key/values from: get_post( $post->ID, ARRAY_A ),
			],
			// ...
		],
		'part' => [
			0 => [
				'export' => true,
				// key/values from: get_post( $post->ID, ARRAY_A ),
				'chapters' => [
					0 => [
						'export' => true,
						// key/values from: get_post( $post->ID, ARRAY_A ),
					],
					1 => [
						'export' => false,
						// key/values from: get_post( $post->ID, ARRAY_A ),
					],
					// ...
				],
			],
			1 => [
				'export' => true,
				// key/values from: get_post( $post->ID, ARRAY_A ),
				'chapters' => [
					0 => [
						'export' => true,
						// key/values from: get_post( $post->ID, ARRAY_A ),
					],
					1 => [
						'export' => false,
						// key/values from: get_post( $post->ID, ARRAY_A ),
					],
				],
				// ...
			],
			// ...
		],
		'back-matter' => [
			0 => [
				'export' => true,
				// key/values from: get_post( $post->ID, ARRAY_A ),
			],
			1 => [
				'export' => false,
				// key/values from: get_post( $post->ID, ARRAY_A ),
			],
			// ...
		],
		'__order' => [
			$post->ID => [
				'export' => true,
				'post_status' => 'publish',
			],
			$post->ID => [
				'export' => false,
				'post_status' => 'publish',
			],
			// ...
		],
		'__export_lookup' => [
			'introduction' => 'front-matter',
			'chapter-1' => 'chapter',
			'foo-bar' => 'chapter',
			'appendix' => 'back-matter',
			// ...
		],
	];

*/
