<?php

/**
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks\Api_v1\Books;

use PressBooks\Api_v1\Api;

/**
 * Description of class-pb-books
 *
 * @author bpayne
 */
class BooksApi extends Api {

	protected $default_variations = array(
	    'fields' => 'all',
	    'offset' => 0,
	    'limit' => 100,
	    'subjects' => 'all',
	    'authors' => 'all',
	    'licenses' => 'all',
	    'keywords' => 'all',
	);
	protected $public_books;

	function __construct( $id = '', $variations = '' ) {

		// only serve info about public books
		$this->public_books = $this->getPublicBlogIds();

		if ( empty( $this->public_books ) ) {
			return $this->apiErrors( 'empty' );
		}

		// Merge args with default args
		$args = wp_parse_args( $variations, $this->default_variations );

		// add id to the args
		if ( $id ) {
			$args['id'] = $id;
		}

		$this->controller( $args );
	}

	/**
	 * Gets resources based on what is passed to it
	 * 
	 * @param type $args
	 */
	public function controller( $args ) {

		$books = $this->getBooksById( $args );
		$books = $this->filterArgs( $books, $args );

		// just in case the filter, filters out everything
		if ( ! empty( $books ) ) {
			return wp_send_json_success( $books );
		} else {
			return $this->apiErrors( 'empty' );
		}
	}

	/**
	 * If arguments are passed, this filters the results based on that
	 * 
	 * @param array $books
	 * @param array $args
	 * @return array of books with arguments applied
	 */
	protected function filterArgs( $books, $args ) {
		$match = array();

		// if everything is default
		$diff = array_diff_assoc( $args, $this->default_variations );
		if ( empty( $diff ) ) {
			// no further processing required
			return $books;
		}

		// keywords, subjects, offset and limit do not apply to single records
		if ( ! isset( $diff['id'] ) ) {

			$args_length = count( $diff );

			if ( isset( $diff['subjects'] ) ) {

				// bring all subjects into one array
				$subjects = $this->getMetaElement( $books, 'pb_bisac_subject' );
				$match['subjects'] = $this->naiveStringSearch( $diff['subjects'], $subjects );
			}

			if ( isset( $diff['keywords'] ) ) {

				// bring all keywords into one array
				$keywords = $this->getMetaElement( $books, 'pb_keywords_tags' );
				$match['keywords'] = $this->naiveStringSearch( $diff['keywords'], $keywords );
			}

			if ( isset( $diff['licenses'] ) ) {

				// bring all keywords into one array
				$licenses = $this->getMetaElement( $books, 'pb_book_license' );
				$match['licenses'] = $this->exactStringSearch( $diff['licenses'], $licenses );
			}

			if ( isset( $diff['authors'] ) ) {

				// bring all authors into one array
				$authors = $this->getMetaElement( $books, 'pb_author' );
				$match['authors'] = $this->naiveStringSearch( $diff['authors'], $authors );
			}

			// evaluate matches 
			$matches = $this->intersectArrays( $match );

			if ( ! empty( $matches ) ) {
				$filtered_books = array_flip( $matches );

				// preserve only the blog_ids that made it through each of the filters
				$books = array_intersect_key( $books, $filtered_books );
			}

			// if the offset is bigger than the book collection
			if ( isset( $diff['offset'] ) && $diff['offset'] > count( $books ) ) {
				return $this->apiErrors( 'offset' );
			}

			// set the limit, look for unlimited requests
			$limit = ( 0 == $args['limit'] ) ? NULL : $args['limit'];
			$books = array_slice( $books, $args['offset'], $limit, true );

			// safety check
			if ( empty( $books ) ) {
				return $this->apiErrors( 'empty' );
			}
		}

		return $books;
	}

	/**
	 * 
	 * @param array $match
	 * @return array
	 */
	protected function intersectArrays( array $match ) {
		// needs to be at least two arrays to intersect
		$keys = array_keys( $match );
		$minimum = count( $keys );
		$result = array();

		if ( $minimum < 2 ) {
			return $match[$keys[0]];
		} else {

			$result = call_user_func_array( 'array_intersect', $match );
		}

		return $result;
	}

	/**
	 * Give this the name of any PB meta element and it will return just those 
	 * elements with the blog_id as array( '13' => 'Math, Science, Tech)
	 * 
	 * @param array $books
	 * @param string $element
	 * @return array 
	 */
	protected function getMetaElement( array $books, $element ) {
		$result = array();

		foreach ( $books as $blog_id => $val ) {
			if ( isset( $val['book_meta'][$element] ) ) {
				$result[$blog_id] = $val['book_meta'][$element];
			}
		}

		return $result;
	}

	/**
	 * Checks for the existence of a substring pattern within an array and returns
	 * an array of keys (blog_id) if found
	 * 
	 * @param string|array $search_words
	 * @param array $haystack
	 * @return array of key values 
	 */
	protected function naiveStringSearch( $search_words, array $haystack ) {
		$matches = array();

		// look for more than one search word
		if ( false !== strpos( $search_words, ',' ) ) {
			$search_words = explode( ',', $search_words );

			// prevent excessive requests ?subjects=cat,bird,dog,bat,eggs,fox,greed,hell,icarus, etc
			$limit = 5;
			for ( $i = 0; $i < $limit; $i ++ ) {
				foreach ( $haystack as $key => $val ) {
					if ( false !== stripos( $val, $search_words[$i] ) ) {
						$matches[] = $key;
					}
				}
			}

			// get rid of duplicates
			$matches = array_unique( $matches );
		} else {
			foreach ( $haystack as $key => $val ) {
				if ( false !== stripos( $val, $search_words ) ) {
					$matches[] = $key;
				}
			}
		}

		return $matches;
	}

	protected function exactStringSearch( $search_words, array $haystack ) {
		$matches = array();

		// look for more than one search word
		if ( false !== strpos( $search_words, ',' ) ) {
			$search_words = explode( ',', $search_words );

			$limit = 5;
			for ( $i = 0; $i < $limit; $i ++ ) {
				foreach ( $haystack as $key => $val ) {
					if ( preg_match( "/^$search_words[$i]$/i", $val ) ) {
						$matches[] = $key;
					};
				}
			}

			// get rid of duplicates
			$matches = array_unique( $matches );
		} else {
			foreach ( $haystack as $key => $val ) {
				if ( 1 === preg_match( "/^$search_words$/i", $val ) ) {
					$matches[] = $key;
				};
			}
		}

		return $matches;
	}

	/**
	 * Expose public information about a book 
	 * 
	 * @param array $args
	 * @return array of book information
	 */
	protected function getBooksById( array $args ) {
		$book = array();

		if ( empty( $args['id'] ) ) {

			foreach ( $this->public_books as $book_id ) {
				@$book[$book_id];
				$book[$book_id]['book_id'] = $book_id;
				$book[$book_id]['book_meta'] = \PressBooks\Book::getBookInformation( intval( $book_id ) );
				$book_structure = \PressBooks\Book::getBookStructure( intval( $book_id ) );
				$book[$book_id]['book_toc'] = $this->getToc( $book_structure );
			}
		} else {
			// check if blog_id is in the collection
			if ( ! in_array( $args['id'], $this->public_books ) ) {
				return $this->apiErrors( 'empty' );
			}
			$book[$args['id']];
			$book[$args['id']]['book_id'] = $id;
			$book[$args['id']]['book_meta'] = \PressBooks\Book::getBookInformation( intval( $args['id'] ) );
			$book_structure = \PressBooks\Book::getBookStructure( intval( $id ) );
			$book[$args['id']]['book_toc'] = $this->getToc( $book_structure );
		}

		return $book;
	}

	/**
	 * Only interested in public books
	 * 
	 * @global global $wpdb
	 * @return array of blog_id for books that are public
	 */
	function getPublicBlogIds() {
		$transient = get_transient( 'pb-api-public-bookids' );

		if ( false === $transient ) {
			global $wpdb;
			$result = array();

			$result = $wpdb->get_col( 'SELECT blog_id FROM wp_blogs WHERE `public` = 1' );

			// blog id = 1 is not a book
			if ( ! empty( $result ) && 1 == $result[0] ) {
				unset( $result[0] );
			}

			// expires in 24 hours
			set_transient( 'pb-api-public-bookids', $result, 86400 );
		} else {
			$result = $transient;
		}

		return $result;
	}

	/**
	 * Gets the Table of Contents for a book
	 * 
	 * @param array $book
	 * @return array Table of Contents
	 */
	function getToc( array $book ) {
		$toc = array();

		// front matter
		foreach ( $book['front-matter'] as $fm ) {
			if ( 'publish' != $fm['post_status'] ) {
				continue;
			}
			$toc['front-matter'] = array(
			    'post_id' => $fm['ID'],
			    'post_title' => $fm['post_title'],
			    'link' => get_permalink( $fm['ID'] ),
			);
		}
		// parts
		foreach ( $book['part'] as $part ) {
			if ( count( $book['part'] ) > 1 && get_post_meta( $part['ID'], 'pb_part_invisible', true ) !== 'on' ) {
				foreach ( $part['chapters'] as $chapter ) {
					if ( 'publish' != $chapter['post_status'] ) {
						continue;
					}
					$toc['part'][$part['post_title']] = array(
					    'post_id' => $chapter['ID'],
					    'title' => $chapter['post_title'],
					    'link' => get_permalink( $chapter['ID'] )
					);
				}
			}
		}
		// back-matter
		foreach ( $book['back-matter'] as $bm ) {
			if ( 'publish' != $bm['post_status'] ) {
				continue;
			}
			$toc['back-matter'] = array(
			    'post_id' => $bm['ID'],
			    'post_title' => $bm['post_title'],
			    'link' => get_permalink( $bm['ID'] ),
			);
		}

		return $toc;
	}

}
