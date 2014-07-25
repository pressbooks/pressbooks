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

	function __construct( $id = '', $variations = '' ) {

		$args = wp_parse_args( $variations, $this->default_variations );

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

		// if there is no id
		if ( ! isset( $args['id'] ) ) {
			$public_books = $this->getPublicBooks();

			if ( ! empty( $public_books ) ) {
				foreach ( $public_books as $book_id ) {
					$books_meta[$book_id] = \PressBooks\Book::getBookInformation( intval( $book_id ) );
					//$books_structure[] = \PressBooks\Book::getBookStructure( intval ( $book_id ) );
				}
			}
		}

		echo "<pre>";
		print_r( get_defined_vars() );
		echo "</pre>";
		die();
	}

	protected function getBookInformationById( $id ) {
		
	}

	/**
	 * Only interested in public books
	 * 
	 * @global global $wpdb
	 * @return array of blog_id for books that are public
	 */
	function getPublicBooks() {
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

//put your code here
}
