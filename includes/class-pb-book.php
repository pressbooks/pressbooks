<?php
/**
 * A book is a discrete, collection of text (and other media), that is designed by an author(s) as an internally
 * complete representation of an idea, or set of ideas; emotion or set of emotions; and transmitted to readers in
 * various formats.
 *
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks;


class Book {

	/**
	 * Fix duplicate slugs.
	 * This can happen if a post is 'draft', 'pending', or 'auto-draft'
	 *
	 * @see wp_unique_post_slug()
	 * @var array
	 */
	static $fixDupeSlugs = array();


	function __construct() {

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
	 * @return array
	 */
	static function getBookInformation() {

		// -----------------------------------------------------------------------------
		// Is cached?
		// -----------------------------------------------------------------------------

		global $blog_id;
		$cache_id = "pb-book-information-$blog_id";
		$book_information = wp_cache_get( $cache_id );
		if ( $book_information ) {
			return $book_information;
		}

		// ----------------------------------------------------------------------------
		// Book Information
		// ----------------------------------------------------------------------------

		$expected_array = array( 'pb_keywords_tags', 'pb_bisac_subject' );
		$expected_the_content = array( 'pb_custom_copyright', 'pb_about_unlimited' );

		$book_information = array();
		$meta = new \PressBooks\Metadata();
		$data = $meta->getMetaPostMetadata();

		foreach ( $data as $key => $val ) {

			// Skip anything not prefixed with pb_
			if ( ! preg_match( '/^pb_/', $key ) )
				continue;

			// We only care about strings
			if ( is_array( $val ) ) {
				if ( false !== in_array( $key, $expected_array ) ) {
					$val = implode( ', ', $val );
				} else {
					$val = array_pop( array_values( $val ) );
				}
			}

			// Skip empty values
			if ( ! trim( $val ) )
				continue;

			if ( false !== in_array( $key, $expected_the_content ) ) {
				$val = apply_filters( 'the_content', $val );
			} else {
				$val = htmlspecialchars( $val, ENT_NOQUOTES | ENT_XHTML, 'UTF-8', false );
			}

			// Remove invisible control characters that break XML
			$val = \PressBooks\Sanitize\remove_control_characters( $val );

			$book_information[$key] = $val;
		}

		// -----------------------------------------------------------------------------
		// Cache & Return
		// -----------------------------------------------------------------------------

		wp_cache_set( $cache_id, $book_information );

		return $book_information;
	}


	/**
	 * Returns an array representing the entire structure of a book, in correct order,
	 * with a minimum amount of fields. Data is raw and must be post-processed.
	 *
	 * @see bottom of this file for more info
	 * @return array
	 */
	static function getBookStructure() {

		// -----------------------------------------------------------------------------
		// Is cached?
		// -----------------------------------------------------------------------------

		global $blog_id;
		$cache_id = "pb-book-structure-$blog_id";
		$book_structure = wp_cache_get( $cache_id );
		if ( $book_structure ) {
			return $book_structure;
		}

		// -----------------------------------------------------------------------------
		// Query our custom post types, keep minimal data in $book_structure
		// -----------------------------------------------------------------------------

		$book_structure = array();

		$custom_types = array(
			'front-matter',
			'part',
			'chapter',
			'back-matter',
		);

		$q = new \WP_Query();

		foreach ( $custom_types as $type ) {

			$book_structure[$type] = array();

			$args = array(
				'post_type' => $type,
				'posts_per_page' => - 1,
				'post_status' => 'any',
				'orderby' => 'menu_order',
				'order' => 'ASC',
				'no_found_rows' => true,
				'cache_results' => true,
			);

			$results = $q->query( $args );

			foreach ( $results as $post ) {

				$post_name = static::fixSlug( $post->post_name );

				$book_structure[$type][] = array(
					'ID' => $post->ID,
					'post_title' => $post->post_title,
					'post_name' => $post_name,
					'post_author' => $post->post_author,
					'comment_count' => $post->comment_count,
					'menu_order' => $post->menu_order,
					'post_status' => $post->post_status,
					'export' => ( get_post_meta( $post->ID, 'pb_export', true ) ? true : false ),
					'post_parent' => $post->post_parent,
				);
			}

		}

		// -----------------------------------------------------------------------------
		// Add Chapters to Parts
		// -----------------------------------------------------------------------------

		foreach ( $book_structure['part'] as $i => $part ) {
			$book_structure['part'][$i]['chapters'] = array();
		}

		foreach ( $book_structure['chapter'] as $i => $chapter ) {
			foreach ( $book_structure['part'] as $j => $part ) {
				if ( $part['ID'] == $chapter['post_parent'] ) {
					$book_structure['part'][$j]['chapters'][] = $chapter;
					unset( $book_structure['chapter'][$i] );
					continue 2;
				}
			}
		}

		/* Track unexpected orphans, unset() chapter from $book_structure and $types */

		if ( count( $book_structure['chapter'] ) ) {
			$book_structure['__orphans'] = $book_structure['chapter'];
		}

		unset( $book_structure['chapter'] );
		$custom_types = array_diff( $custom_types, array( 'chapter' ) );

		// -----------------------------------------------------------------------------
		// Create __order and __lookup arrays, remove post_parent
		// -----------------------------------------------------------------------------

		$book_structure['__order'] = array();
		$book_structure['__export_lookup'] = array();

		foreach ( $custom_types as $type ) {
			foreach ( $book_structure[$type] as $i => $struct ) {
				unset( $book_structure[$type][$i]['post_parent'] );
				if ( $type != 'part' ) {
					$book_structure['__order'][$struct['ID']] = array( 'export' => $struct['export'], 'post_status' => $struct['post_status'] );
					if ( $struct['export'] ) {
						$book_structure['__export_lookup'][$struct['post_name']] = $type;
					}
				} else {
					foreach ( $struct['chapters'] as $j => $chapter ) {
						unset( $book_structure[$type][$i]['chapters'][$j]['post_parent'] );
						$book_structure['__order'][$chapter['ID']] = array( 'export' => $chapter['export'], 'post_status' => $chapter['post_status'] );
						if ( $chapter['export'] ) {
							$book_structure['__export_lookup'][$chapter['post_name']] = 'chapter';
						}
					}
				}
			}
		}

		// -----------------------------------------------------------------------------
		// Cache & Return
		// -----------------------------------------------------------------------------

		wp_cache_set( $cache_id, $book_structure );

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

		// -----------------------------------------------------------------------------
		// Is cached?
		// -----------------------------------------------------------------------------

		global $blog_id;
		$cache_id = "pb-book-contents-$blog_id";
		$book_contents = wp_cache_get( $cache_id );
		if ( $book_contents ) {
			return $book_contents;
		}

		// -----------------------------------------------------------------------------
		// Using + to merge arrays...
		// Precedence when using the + operator to merge arrays is from left to right
		// -----------------------------------------------------------------------------

		$book_contents = static::getBookStructure();

		foreach ( $book_contents as $type => $struct ) {

			if ( preg_match( '/^__/', $type ) )
				continue; // Skip __magic keys

			if ( 'part' == $type ) {
				foreach ( $struct as $i => $part ) {
					$book_contents[$type][$i] = $part + get_post( $part['ID'], ARRAY_A );
					foreach ( $part['chapters'] as $j => $chapter ) {
						$book_contents[$type][$i]['chapters'][$j] = $chapter + get_post( $chapter['ID'], ARRAY_A );
					}
				}
			} else {
				foreach ( $struct as $i => $val ) {
					$book_contents[$type][$i] = $val + get_post( $val['ID'], ARRAY_A );
				}
			}
		}

		// -----------------------------------------------------------------------------
		// Cache & Return
		// -----------------------------------------------------------------------------

		wp_cache_set( $cache_id, $book_contents );

		return $book_contents;
	}


	/**
	 * Delete the Book Object cache(s)
	 */
	static function deleteBookObjectCache() {

		global $blog_id;

		wp_cache_delete( "pb-book-information-$blog_id" );
		wp_cache_delete( "pb-book-structure-$blog_id" );
		wp_cache_delete( "pb-book-contents-$blog_id" );
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

			parse_str( $_POST['new_part_order'], $newPartOrder );
			parse_str( $_POST['old_part_order'], $oldPartOrder );
			$newPart = (int) $_POST['new_part'];
			$oldPart = (int) $_POST['old_part'];

			// if the part for this chapter changed, set new part for chapter
			// and new order for this part
			if ( $newPart != $oldPart ) {
				$my_post = array();
				$my_post['ID'] = $id;
				$my_post['post_parent'] = $newPart;
				wp_update_post( $my_post );
				do_action( 'pb_part_updated', $id, $newPart, $oldPart );

				if ( is_array( $newPartOrder ) ) {
					foreach ( $newPartOrder as $key => $values ) {
						if ( $key == 'chapter' ) {
							foreach ( $values as $position => $id ) {
								$position += 1; // array is 0-indexed, but we want it to start from 1
								$wpdb->update( $wpdb->posts, array( 'menu_order' => $position ), array( 'ID' => $id ) );
							}
						}
					}
				}
			}

			// always update the order of the part this chapter was originally in
			if ( is_array( $oldPartOrder ) ) {
				foreach ( $oldPartOrder as $key => $values ) {
					if ( $key == 'chapter' ) {
						foreach ( $values as $position => $id ) {
							$position += 1; // array is 0-indexed, but we want it to start from 1
							$wpdb->update( $wpdb->posts, array( 'menu_order' => $position ), array( 'ID' => $id ) );
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

			parse_str( $_POST['front_matter_order'], $frontMatterOrder );

			if ( is_array( $frontMatterOrder ) ) {
				foreach ( $frontMatterOrder as $key => $values ) {
					if ( $key == 'front-matter' ) {
						foreach ( $values as $position => $id ) {
							$position += 1;
							$wpdb->update( $wpdb->posts, array( 'menu_order' => $position ), array( 'ID' => $id ) );
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

			parse_str( $_POST['back_matter_order'], $backMatterOrder );

			if ( is_array( $backMatterOrder ) ) {
				foreach ( $backMatterOrder as $key => $values ) {
					if ( $key == 'back-matter' ) {
						foreach ( $values as $position => $id ) {
							$position += 1;
							$wpdb->update( $wpdb->posts, array( 'menu_order' => $position ), array( 'ID' => $id ) );
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

		$valid_meta_keys = array(
			'pb_export',
		);

		$post_id = absint( $_POST['post_id'] );
		$meta_key = in_array( $_POST['type'], $valid_meta_keys ) ? $_POST['type'] : false;
		$meta_value = ( $_POST['chapter_export'] ) ? 'on' : 0;

		if ( current_user_can( 'edit_post', $post_id ) && $meta_key && check_ajax_referer( 'pb-update-book-export' ) ) {
			update_post_meta( $post_id, $meta_key, $meta_value );
			static::deleteBookObjectCache();
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

		if ( 'first' == $what )
			return static::getFirst();

		global $post;

		$current_post_id = $post->ID;
		$book_structure = static::getBookStructure();
		$order = $book_structure['__order'];
		$pos = array_keys( $order );

		$what = ( $what == 'next' ? 'next' : 'prev' );

		// Move internal pointer to correct position
		reset( $pos );
		while ( $find_me = current( $pos ) ) {
			if ( $find_me == $current_post_id ) {
				break;
			} else {
				next( $pos );
			}
		}

		// Get next/previous
		$what( $pos );
		while ( $post_id = current( $pos ) ) {
			if ( $order[$post_id]['post_status'] == 'publish' ) {
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

		$book_structure = static::getBookStructure();
		$order = $book_structure['__order'];
		$pos = array_keys( $order );

		reset( $pos );
		while ( $first_id = current( $pos ) ) {
			if ( $order[$first_id]['post_status'] == 'publish' ) {
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
	 * @param integer $pid  Post ID
	 * @param object  $post Post
	 *
	 * @return bool
	 */
	static function consolidatePost( $pid, $post ) {

		if ( ( is_main_site() ) || wp_is_post_revision( $pid ) || $post->post_status != 'publish' )
			return false;

		/** @var $wpdb \wpdb */
		global $wpdb;

		// if this is a new post, set its order
		if ( empty( $post->menu_order ) ) {
			$query = "SELECT max($wpdb->posts.menu_order)+1
					FROM $wpdb->posts
				   WHERE $wpdb->posts.post_type = '{$post->post_type}'
				 AND NOT $wpdb->posts.post_status = 'trash'";

			if ( $post->post_type == "chapter" ) {
				$query .= " AND $wpdb->posts.post_parent=$post->post_parent";
			}
			$new = $wpdb->get_var( $query );

			if ( empty( $new ) ) {
				$new = 1;
			}

			$query = "UPDATE $wpdb->posts
					 SET $wpdb->posts.menu_order=$new
				   WHERE $wpdb->posts.ID = {$post->ID}";

			// will return false on failure
			return $wpdb->query( $query );
		}

		return true;

	}


	/**
	 * Put a Part/Chapter/Front Matter/Back Matter in the trash
	 *
	 * @param int $pid
	 *
	 * @return mixed
	 */
	static function deletePost( $pid ) {

		if ( ( is_main_site() ) || wp_is_post_revision( $pid ) ) return false;

		/** @var $wpdb \wpdb */
		global $wpdb;

		// remove chapter/part/front matter
		// decrement order of everything with a higher order, and if chapter, only within that part

		$post = get_post( $pid );
		$order = $post->menu_order;
		$type = $post->post_type;
		$parent = $post->post_parent;

		$query = "UPDATE $wpdb->posts SET menu_order = menu_order - 1 WHERE menu_order > $order AND post_type='$type'";

		if ( $type == 'chapter' ) {
			$query .= " AND post_parent = $parent";
		}

		$wpdb->query( $query );


		if ( $type == 'part' ) {
			// We're setting two things here - the new post_parent (to the first part)
			// And the new menu order for the chapters that were in the part being deleted.
			$new_parent_id = $wpdb->get_var( "SELECT ID
										 FROM $wpdb->posts
										WHERE post_type='part'
										  AND post_status='publish'
										  AND NOT ID=$pid
									 ORDER BY menu_order
										LIMIT 1" );

			if ( $new_parent_id ) {

				$existing_numposts = $wpdb->get_var( "SELECT count(1) AS numposts FROM $wpdb->posts WHERE post_type='chapter' AND post_parent=$new_parent_id" );
				$query = "UPDATE $wpdb->posts SET post_parent=$new_parent_id, menu_order=menu_order+$existing_numposts WHERE post_parent=$pid AND post_type='chapter'";

				return $wpdb->query( $query );

			} else {

				$query = "UPDATE $wpdb->posts SET post_status='trash' WHERE post_parent=$pid AND post_type='chapter'";

				return $wpdb->query( $query );
			}
		}

		return true;
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

		if ( isset( static::$fixDupeSlugs[$old_post_name] ) ) {
			$new_post_name = $old_post_name . '-' . static::$fixDupeSlugs[$old_post_name];
			$i = 0;
			while ( isset( static::$fixDupeSlugs[$new_post_name] ) ) {
				++static::$fixDupeSlugs[$new_post_name];
				++static::$fixDupeSlugs[$old_post_name];
				$new_post_name = $old_post_name . '-' . static::$fixDupeSlugs[$old_post_name];
				if ( $i > 999 ) break; // Safety
				++$i;
			}
			$post_name = $new_post_name;
			static::$fixDupeSlugs[$new_post_name] = 1;
			++static::$fixDupeSlugs[$old_post_name];
		} else {

			$post_name = $old_post_name;
			static::$fixDupeSlugs[$old_post_name] = 1;
		}

		return $post_name;
	}

}

/* --------------------------------------------------------------------------------------------------------------------

getBookStructure() and getBookContents() will return a "super array" or a "book object" that contains everything
PressBooks considers a book. This "book object" is returned in the correct order so that, with straightforward foreach()
logic, a programmer or template designer can render a book however they see fit.

 * getBookStructure() returns a minimal subset of get_post( $post->ID, ARRAY_A ) plus our own custom key/values
 * getBookContents() returns the entirety of get_post( $post->ID, ARRAY_A ) plus our own custom key/values

getBookStructure() and getBookContents() will cache results using wp_cache_* functions. If you change the book, make
sure to call static::deleteBookObjectCache() for a sane user experience.

The "book object" looks something like this:

	$book_structure = array(
		'front-matter' => array(
			0 => array(
				'export' => true,
				// key/values from: get_post( $post->ID, ARRAY_A ),
			),
			1 => array(
				'export' => false,
				// key/values from: get_post( $post->ID, ARRAY_A ),
			),
			// ...
		),
		'part' => array(
			0 => array(
				'export' => true,
				// key/values from: get_post( $post->ID, ARRAY_A ),
				'chapters' => array(
					0 => array(
						'export' => true,
						// key/values from: get_post( $post->ID, ARRAY_A ),
					),
					1 => array(
						'export' => false,
						// key/values from: get_post( $post->ID, ARRAY_A ),
					),
					// ...
				),
			),
			1 => array(
				'export' => true,
				// key/values from: get_post( $post->ID, ARRAY_A ),
				'chapters' => array(
					0 => array(
						'export' => true,
						// key/values from: get_post( $post->ID, ARRAY_A ),
					),
					1 => array(
						'export' => false,
						// key/values from: get_post( $post->ID, ARRAY_A ),
					),
				),
				// ...
			),
			// ...
		),
		'back-matter' => array(
			0 => array(
				'export' => true,
				// key/values from: get_post( $post->ID, ARRAY_A ),
			),
			1 => array(
				'export' => false,
				// key/values from: get_post( $post->ID, ARRAY_A ),
			),
			// ...
		),
		'__order' => array(
			$post->ID => array(
				'export' => true,
				'post_status' => 'publish',
			),
			$post->ID => array(
				'export' => false,
				'post_status' => 'publish',
			),
			// ...
		),
		'__export_lookup' => array(
            'introduction' => 'front-matter',
            'chapter-1' => 'chapter',
            'foo-bar' => 'chapter',
            'appendix' => 'back-matter',
			// ...
		),
	);

*/
