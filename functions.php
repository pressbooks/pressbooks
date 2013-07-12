<?php
/**
 * Shortcuts for template designers who don't use real namespaces, and other helper functions.
 *
 * @author  PressBooks <code@pressbooks.com>
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


/**
 * Shortcut to \PressBooks\CustomCss::isCustomCss();
 *
 * @return bool
 */
function pb_is_custom_theme() {

	return \PressBooks\CustomCss::isCustomCss();
}


/**
 * Get url to the custom stylesheet for web.
 *
 * @see: \PressBooks\CustomCss
 * @return string
 */
function pb_get_custom_stylesheet_url() {

	$current_blog_id = get_current_blog_id();

	if ( is_file( WP_CONTENT_DIR . "/blogs.dir/{$current_blog_id}/files/custom-css/web.css" ) ) {
		return WP_CONTENT_URL . "/blogs.dir/{$current_blog_id}/files/custom-css/web.css";
	} elseif ( is_file( WP_CONTENT_DIR . "/uploads/sites/{$current_blog_id}/custom-css/web.css" ) ) {
		return WP_CONTENT_URL . "/uploads/sites/{$current_blog_id}/custom-css/web.css";
	} else {
		return PB_PLUGIN_URL . "themes-book/pressbooks-custom-css/style.css";
	}
}


/**
 * Get path to hyphenation dictionary in a book's language.
 *
 * @return bool|string
 */
function pb_get_hyphens_path() {

	$loc = false;
	$compare_with = scandir( PB_PLUGIN_DIR . '/symbionts/dictionaries/' );

	$book_lang = \PressBooks\Book::getBookInformation();
	$book_lang = @$book_lang['pb_language'];

	foreach ( $compare_with as $compare ) {

		if ( strpos( $compare, 'hyph_' ) !== 0 ) continue; // Skip

		$c = str_replace( 'hyph_', '', $compare );
		list( $check_me ) = explode( '_', $c );

		// We only care about the first two letters
		if ( strpos( $book_lang, $check_me ) === 0 ) {
			$loc = $compare;
			break;
		}
	}

	if ( $loc ) {
		$loc = PB_PLUGIN_DIR . "symbionts/dictionaries/$loc";
	}

	return $loc;
}


/**
 * Get "real" chapter number
 *
 * @param $post_name
 *
 * @return int
 */
function pb_get_chapter_number( $post_name ) {

	$options = get_option( 'pressbooks_theme_options_global' );
	if ( ! @$options['chapter_numbers'] )
		return 0;

	$lookup = \PressBooks\Book::getBookStructure();
	$lookup = $lookup['__export_lookup'];

	if ( 'chapter' != @$lookup[$post_name] )
		return 0;

	$i = 0;
	foreach ( $lookup as $key => $val ) {
		if ( 'chapter' == $val ) {
			++$i;
			if ( $key == $post_name ) break;
		}
	}

	return $i;
}


/**
 * Rename image with arbitrary suffix (before extension)
 *
 * @param $thumb
 * @param $path
 *
 * @return string
 */
function pb_thumbify( $thumb, $path ) {

	return \PressBooks\Image\thumbify( $thumb, $path );
}
