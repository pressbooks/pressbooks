<?php
/**
 * Shortcuts to help template designers who don't use real namespaces.
 *
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */


/**
 * Shortcut to \PressBooks\Book::get( 'prev' );
 *
 * @return string URL of previous post
 */
function pb_get_prev() {

	return \PressBooks\Book::get( 'prev' );
}


/**
 * Shortcut to \PressBooks\Book::get( 'next' );
 *
 * @return string URL of next post
 */
function pb_get_next() {

	return \PressBooks\Book::get( 'next' );
}


/**
 * Shortcut to \PressBooks\Book::get( 'first' );
 *
 * @return string URL of first post
 */
function pb_get_first() {

	return \PressBooks\Book::get( 'first' );
}


/**
 * Shortcut to \PressBooks\Book::getBookInformation();
 *
 * @return array
 */
function pb_get_book_information() {

	return \PressBooks\Book::getBookInformation();
}


/**
 * Shortcut to \PressBooks\Book::getBookStructure();
 *
 * @return array
 */
function pb_get_book_structure() {

	return \PressBooks\Book::getBookStructure();
}


/**
 * Shortcut to \PressBooks\Sanitize\decode();
 *
 * @param $val
 *
 * @return mixed
 */
function pb_decode( $val ) {

	return \PressBooks\Sanitize\decode( $val );
}