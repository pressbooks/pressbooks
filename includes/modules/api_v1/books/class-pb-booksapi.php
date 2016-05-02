<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Api_v1\Books;

use Pressbooks\Modules\Api_v1\Api;

/**
 * Processes public information about collections of books and individual books
 *
 * The format it expects is:
 * http://somedomain/api/v1/books
 * http://somedomain/api/v1/books/12
 *
 * Arguments can be passed:
 * ?subjects=biology&license=cc-by&limit=3
 */
class BooksApi extends Api {

	/**
	 * Control the arguments that can be passed to the API
	 *
	 * @var array
	 */
	protected $default_variations = array(
		'titles' => 'all',
		'offset' => 0,
		'limit' => 100,
		'subjects' => 'all',
		'authors' => 'all',
		'licenses' => 'all',
		'keywords' => 'all',
	);

	/**
	 * Default format of the response
	 *
	 * @var string
	 */
	protected $format = 'json';

	/**
	 * List of publically available books
	 *
	 * @var array
	 */
	protected $public_books = array();

	/**
	 * Parse arguments and send the response to the controller
	 *
	 * @param int $id
	 * @param array $variations
	 *
	 * @throws \Exception
	 */
	function __construct( $id = null, $variations = array() ) {

		// only serve info about public books
		$this->public_books = $this->getPublicBlogIds();

		if ( empty( $this->public_books ) ) {
			throw new \Exception( 'There are no public facing books in this instance of Pressbooks' );
		}

		// get the format, set it as instance variable
		if ( isset( $variations['json'] ) && 1 == $variations['json'] ) {
			$this->format = 'json';
			unset( $variations['json'] );
		}
		elseif ( isset( $variations['xml'] ) && 1 == $variations['xml'] ) {
			$this->format = 'xml';
			unset( $variations['xml'] );
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
	 * Controls which book resources are retrieved based on what is passed to it
	 *
	 * @param array $args
	 */
	public function controller( $args ) {

		$books = $this->getBooksById( $args );
		$books = $this->filterArgs( $books, $args );

		$this->response( $books, $this->format );
	}

	/**
	 * Filters the results based on what is passed to it
	 *
	 * @param array $results
	 * @param array $args
	 *
	 * @return array of books with arguments applied
	 */
	protected function filterArgs( $results, $args ) {
		$match = array();

		// if everything is default
		$diff = array_diff_assoc( $args, $this->default_variations );
		if ( empty( $diff ) ) {
			// no further processing required
			return $results;
		}

		// this logic does not apply to single records
		if ( ! isset( $diff['id'] ) ) {

			// $args_length = count( $diff );

			if ( isset( $diff['titles'] ) ) {

				// bring all subjects into one array
				$titles = $this->getMetaElement( $results, 'pb_title' );
				$match['titles'] = $this->naiveStringSearch( $diff['titles'], $titles );

				if ( empty( $match['titles'] ) ) {
					$titles = $this->getMetaElement( $results, 'pb_subtitle' );
					$match['titles'] = $this->naiveStringSearch( $diff['titles'], $titles );
				}
			}

			if ( isset( $diff['subjects'] ) ) {

				// bring all subjects into one array
				$subjects = $this->getMetaElement( $results, 'pb_bisac_subject' );
				$match['subjects'] = $this->naiveStringSearch( $diff['subjects'], $subjects );
			}

			if ( isset( $diff['keywords'] ) ) {

				// bring all keywords into one array
				$keywords = $this->getMetaElement( $results, 'pb_keywords_tags' );
				$match['keywords'] = $this->naiveStringSearch( $diff['keywords'], $keywords );
			}

			if ( isset( $diff['licenses'] ) ) {

				// bring all licenses into one array
				$licenses = $this->getMetaElement( $results, 'pb_book_license' );
				$match['licenses'] = $this->exactStringSearch( $diff['licenses'], $licenses );
			}

			if ( isset( $diff['authors'] ) ) {

				// bring all authors into one array
				$authors = $this->getMetaElement( $results, 'pb_author' );
				$match['authors'] = $this->naiveStringSearch( $diff['authors'], $authors );
			}

			// evaluate matches 
			$matches = $this->intersectArrays( $match );

			if ( ! empty( $matches ) ) {
				$filtered_books = array_flip( $matches );

				// preserve only the blog_ids that made it through each of the filters
				$results = array_intersect_key( $results, $filtered_books );

				// return empty if there is nothing else to process	
			}
			elseif ( empty( $matches ) && ( ! isset( $diff['limit'] ) && ! isset( $diff['offset'] ) ) ) {
				// bail if no matches
				$this->apiErrors( 'empty' );
				// ^ print and die... ^
			}

			// if the offset is bigger than the book collection
			if ( isset( $diff['offset'] ) && $diff['offset'] > count( $results ) ) {
				$this->apiErrors( 'offset' );
				// ^ print and die... ^
			}

			// set the limit, look for unlimited requests
			$limit = ( 0 == $diff['limit'] ) ? null : $diff['limit'];
			$results = array_slice( $results, $diff['offset'], $limit, true );

			// safety check
			if ( empty( $results ) ) {
				$this->apiErrors( 'empty' );
				// ^ print and die... ^
			}

			// for single records
		}
		elseif ( isset( $diff['id'] ) ) {

			if ( count( $diff ) == 1 ) {
				// no further processing required
				return $results;
			}

			// get all chapters into one array
			$chapters = $this->getBookChapters( $results[$diff['id']]['book_toc'] );

			if ( isset( $diff['titles'] ) ) {

				$chapter_titles = array();
				foreach ( $chapters as $chap ) {
					$chapter_titles[$chap['post_id']] = $chap['post_title'];
				}
				$match['titles'] = $this->naiveStringSearch( $diff['titles'], $chapter_titles );
			}

			if ( isset( $diff['licenses'] ) ) {

				$chapter_license = array();
				foreach ( $chapters as $chap ) {
					$chapter_license[$chap['post_id']] = $chap['post_license'];
				}
				$match['licenses'] = $this->exactStringSearch( $diff['licenses'], $chapter_license );
			}

			if ( isset( $diff['authors'] ) ) {

				$chapter_authors = array();
				foreach ( $chapters as $chap ) {
					$chapter_authors[$chap['post_id']] = $chap['post_authors'];
				}
				$match['authors'] = $this->naiveStringSearch( $diff['authors'], $chapter_authors );
			}

			// evaluate matches 
			$matches = $this->intersectArrays( $match );

			if ( ! empty( $matches ) ) {
				$filtered_chapters = array_flip( $matches );

				// preserve only the blog_ids that made it through each of the filters
				$chapter_results = array_intersect_key( $chapters, $filtered_chapters );
			}
			elseif ( empty( $matches ) && ( ! isset( $diff['limit'] ) && ! isset( $diff['offset'] ) ) ) {
				// bail if no matches
				$this->apiErrors( 'empty' );
				// ^ print and die... ^
			}

			// change the value of $results depending on whether the logic
			// above returned anything
			if ( isset( $chapter_results ) ) {
				$results = $chapter_results;
			}
			else {
				$results = $chapters;
			}

			// if the offset is bigger than the book collection
			if ( isset( $diff['offset'] ) && $diff['offset'] > count( $results ) ) {
				$this->apiErrors( 'offset' );
				// ^ print and die... ^
			}

			// set the limit, look for unlimited requests
			$limit = ( 0 == $args['limit'] ) ? null : $args['limit'];
			$results = array_slice( $results, $args['offset'], $limit, true );

			// safety check
			if ( empty( $results ) ) {
				$this->apiErrors( 'empty' );
				// ^ print and die... ^
			}
		}

		return $results;
	}

	/**
	 * Returns a flat array of chapters
	 *
	 * @param array $book
	 *
	 * @return array $chapters
	 */
	protected function getBookChapters( array $book ) {
		if ( empty( $book ) ) return array();

		$chapters = array();
		$parts_count = count( $book['part'] );

		// front matter
		foreach ( $book['front-matter'] as $fm ) {
			$chapters[$fm['post_id']] = $fm;
		}

		// parts
		for ( $i = 0; $i < $parts_count; $i ++ ) {
			// chapters
			foreach ( $book['part'][$i]['chapters'] as $chap ) {
				$chapters[$chap['post_id']] = $chap;
			};
		}
		// back matter
		foreach ( $book['back-matter'] as $bm ) {
			$chapters[$bm['post_id']] = $bm;
		}

		return $chapters;
	}

	/**
	 * Keeps only arrays that are the same, used for filtering book ids based on
	 * arguments
	 *
	 * @param array $match
	 *
	 * @return array
	 */
	protected function intersectArrays( array $match ) {
		// needs to be at least two arrays to intersect
		$keys = array_keys( $match );
		$minimum = count( $keys );

		if ( $minimum < 2 ) {
			return $match[$keys[0]];
		}
		else {
			$result = call_user_func_array( 'array_intersect', $match );
		}

		return $result;
	}

	/**
	 * Give this the name of any PB meta element and it will return just those
	 * elements with the blog_id as array( '13' => 'Math, Science, Tech)
	 *
	 * @see getBookInformation() for which meta elements are available
	 *
	 * @param array $books
	 * @param string $element
	 *
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
	 * @param array $haystack in the form of blog_id => value
	 *
	 * @return array of key values
	 */
	protected function naiveStringSearch( $search_words, array $haystack ) {
		$matches = array();

		// look for more than one search word
		if ( false !== strpos( $search_words, ',' ) ) {
			$search_words = explode( ',', $search_words );

			// prevent excessive requests ?subjects=cat,bird,dog,bat,eggs,fox,greed,hell,etc
			$search_words = array_slice( $search_words, 0, 5 );
			$count = count( $search_words );

			for ( $i = 0; $i < $count; $i ++ ) {
				foreach ( $haystack as $key => $val ) {
					if ( false !== stripos( $val, $search_words[$i] ) ) {
						$matches[] = $key;
					}
				}
			}

			// get rid of duplicates
			$matches = array_unique( $matches );
		}
		else {
			foreach ( $haystack as $key => $val ) {
				if ( false !== stripos( $val, $search_words ) ) {
					$matches[] = $key;
				}
			}
		}

		return $matches;
	}

	/**
	 * Looks for an exact pattern match
	 *
	 * @param string $search_words
	 * @param array $haystack
	 *
	 * @return array
	 */
	protected function exactStringSearch( $search_words, array $haystack ) {
		$matches = array();

		// look for more than one search word
		if ( false !== strpos( $search_words, ',' ) ) {
			$search_words = explode( ',', $search_words );

			// limit to 5
			$search_words = array_slice( $search_words, 0, 5 );
			$count = count( $search_words );

			for ( $i = 0; $i < $count; $i ++ ) {
				foreach ( $haystack as $key => $val ) {
					if ( preg_match( "/^$search_words[$i]$/i", $val ) ) {
						$matches[] = $key;
					};
				}
			}

			// get rid of duplicates
			$matches = array_unique( $matches );
		}
		else {
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
	 *
	 * @return array of book information
	 */
	protected function getBooksById( array $args ) {
		$book = array();

		if ( empty( $args['id'] ) ) {

			foreach ( $this->public_books as $book_id ) {
				@$book[$book_id];
				$book[$book_id]['book_id'] = $book_id;
				$book[$book_id]['book_url'] = get_blogaddress_by_id( $book_id );
				$book[$book_id]['book_meta'] = \Pressbooks\Book::getBookInformation( intval( $book_id ) );
				$book_structure = \Pressbooks\Book::getBookStructure( intval( $book_id ) );
				$book[$book_id]['book_toc'] = $this->getToc( $book_structure, $book_id );
			}
		}
		else {
			// check if blog_id is in the collection
			if ( ! in_array( $args['id'], $this->public_books ) ) {
				$this->apiErrors( 'empty' );
				// ^ print and die... ^
			}
			$book[$args['id']]['book_id'] = $args['id'];
			$book[$args['id']]['book_url'] = get_blogaddress_by_id( $args['id'] );
			$book[$args['id']]['book_meta'] = \Pressbooks\Book::getBookInformation( intval( $args['id'] ) );
			$book_structure = \Pressbooks\Book::getBookStructure( intval( $args['id'] ) );
			$book[$args['id']]['book_toc'] = $this->getToc( $book_structure, $args['id'] );
		}

		return $book;
	}

	/**
	 * Only interested in public books
	 *
	 * @global \wpdb $wpdb
	 * @return array of blog_id for books that are public
	 */
	function getPublicBlogIds() {
		$transient = get_transient( 'pb-api-public-bookids' );

		if ( false === $transient ) {
			global $wpdb;
			$table_name = $wpdb->prefix . "blogs";

			$result = $wpdb->get_col( $wpdb->prepare( "SELECT blog_id FROM $table_name WHERE public = %d", '1' ) );

			// blog id = 1 is not a book
			if ( ! empty( $result ) && 1 == $result[0] ) {
				unset( $result[0] );
			}

			// expires in 24 hours
			set_transient( 'pb-api-public-bookids', $result, 86400 );
		}
		else {
			$result = $transient;
		}

		return $result;
	}

	/**
	 * Gets the Table of Contents for a book. Looks for published content, not
	 * whether the user has identified post content for 'export'
	 *
	 * @param array $book
	 * @param int $book_id
	 *
	 * @return array Table of Contents
	 */
	function getToc( array $book, $book_id ) {
		$toc = array();
		switch_to_blog( intval( $book_id ) );

		// front matter
		$front_matter = array();
		foreach ( $book['front-matter'] as $fm ) {
			if ( 'publish' != $fm['post_status'] ) continue;

			$front_matter[$fm['ID']] = array(
				'post_id' => $fm['ID'],
				'post_title' => $fm['post_title'],
				'post_link' => get_permalink( $fm['ID'] ),
				'post_license' => get_post_meta( $fm['ID'], 'pb_section_license', true ),
				'post_authors' => get_post_meta( $fm['ID'], 'pb_section_author', true )
			);
		}
		$toc['front-matter'] = $front_matter;

		// parts
		$parts_count = count( $book['part'] );

		for ( $i = 0; $i < $parts_count; $i ++ ) {

			$chapters = array();
			foreach ( $book['part'][$i]['chapters'] as $chapter ) {
				if ( 'publish' != $chapter['post_status'] ) continue;

				// chapters within parts
				$chapters[$chapter['ID']] = array(
					'post_id' => $chapter['ID'],
					'post_title' => $chapter['post_title'],
					'post_link' => get_permalink( $chapter['ID'] ),
					'post_license' => get_post_meta( $chapter['ID'], 'pb_section_license', true ),
					'post_authors' => get_post_meta( $chapter['ID'], 'pb_section_author', true )
				);
			}

			$toc['part'][$i] = array(
				'post_id' => $book['part'][$i]['ID'],
				'post_title' => $book['part'][$i]['post_title'],
				'post_link' => get_permalink( $book['part'][$i]['ID'] ),
				'chapters' => $chapters,
			);
			unset( $chapters );
		}

		// back-matter
		$back_matter = array();
		foreach ( $book['back-matter'] as $bm ) {
			if ( 'publish' != $bm['post_status'] ) continue;

			$back_matter[$bm['ID']] = array(
				'post_id' => $bm['ID'],
				'post_title' => $bm['post_title'],
				'post_link' => get_permalink( $bm['ID'] ),
				'post_license' => get_post_meta( $bm['ID'], 'pb_section_license', true ),
				'post_authors' => get_post_meta( $bm['ID'], 'pb_section_author', true )
			);
		}
		$toc['back-matter'] = $back_matter;

		restore_current_blog();

		return $toc;
	}

}
