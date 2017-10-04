<?php
/**
 * Shortcuts for template designers who don't use real namespaces, and other helper functions.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

if ( ! function_exists( 'app' ) ) {
	// Fake Laravel app() so we can use Blade @inject directive
	function app( $var ) {
		return \Pressbooks\Container::get( $var );
	}
}

/**
 * Shortcut to \Pressbooks\Book::get( 'prev' );
 *
 * @return string URL of previous post
 */
function pb_get_prev() {

	return \Pressbooks\Book::get( 'prev' );
}


/**
 * Shortcut to \Pressbooks\Book::get( 'next' );
 *
 * @return string URL of next post
 */
function pb_get_next() {

	return \Pressbooks\Book::get( 'next' );
}


/**
 * Shortcut to \Pressbooks\Book::get( 'first' );
 *
 * @return string URL of first post
 */
function pb_get_first() {

	return \Pressbooks\Book::get( 'first' );
}


/**
 * Shortcut to \Pressbooks\Book::getBookInformation();
 *
 * @return array
 */
function pb_get_book_information() {

	return \Pressbooks\Book::getBookInformation();
}


/**
 * Shortcut to \Pressbooks\Book::getBookStructure();
 *
 * @return array
 */
function pb_get_book_structure() {

	return \Pressbooks\Book::getBookStructure();
}


/**
 * Shortcut to \Pressbooks\Sanitize\decode();
 *
 * @param $val
 *
 * @return mixed
 */
function pb_decode( $val ) {

	return \Pressbooks\Sanitize\decode( $val );
}

/**
 * Shortcut to \Pressbooks\Sanitize\strip_br();
 *
 * @param $val
 *
 * @return string
 */
function pb_strip_br( $val ) {

	return \Pressbooks\Sanitize\strip_br( $val );
}


/**
 * Shortcut to \Pressbooks\CustomCss::isCustomCss();
 *
 * @deprecated Leftover code from old Custom CSS Editor. Use Custom Styles instead.
 *
 * @return bool
 */
function pb_is_custom_theme() {

	return \Pressbooks\CustomCss::isCustomCss();
}

/**
 * Shortcut to \Pressbooks\Container::get('Styles')->isCurrentThemeCompatible( $version );
 *
 * @return bool
 */
function pb_is_scss( $version = 1 ) {

	if ( \Pressbooks\Container::get( 'Styles' )->isCurrentThemeCompatible( $version ) ) {
		return true;
	}

	return false;
}


/**
 * Shortcut to \Pressbooks\Metadata\get_seo_meta_elements();
 *
 * @return string
 */
function pb_get_seo_meta_elements() {

	return \Pressbooks\Metadata\get_seo_meta_elements();
}

/**
 * Shortcut to \Pressbooks\Metadata\get_microdata_elements();
 *
 * @return string
 */
function pb_get_microdata_elements() {

	return \Pressbooks\Metadata\get_microdata_elements();
}

/**
 * Get url to the custom stylesheet for web.
 *
 * @deprecated Leftover code from old Custom CSS Editor. Use Custom Styles instead.
 *
 * @see: \Pressbooks\CustomCss
 * @return string
 */
function pb_get_custom_stylesheet_url() {

	$current_blog_id = get_current_blog_id();

	if ( is_file( WP_CONTENT_DIR . "/blogs.dir/{$current_blog_id}/files/custom-css/web.css" ) ) {
		return \Pressbooks\Sanitize\maybe_https( content_url( "/blogs.dir/{$current_blog_id}/files/custom-css/web.css" ) );
	} elseif ( is_file( WP_CONTENT_DIR . "/uploads/sites/{$current_blog_id}/custom-css/web.css" ) ) {
		return \Pressbooks\Sanitize\maybe_https( content_url( "/uploads/sites/{$current_blog_id}/custom-css/web.css" ) );
	} else {
		return PB_PLUGIN_URL . 'themes-book/pressbooks-custom-css/style.css';
	}
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
	if ( ! @$options['chapter_numbers'] ) {
		return 0;
	}

	$lookup = \Pressbooks\Book::getBookStructure();
	$lookup = $lookup['__export_lookup'];

	if ( 'chapter' != @$lookup[ $post_name ] ) {
		return 0;
	}

	$i = 0;
	foreach ( $lookup as $key => $val ) {
		if ( 'chapter' == $val ) {
			$chapter = get_posts( array( 'name' => $key, 'post_type' => 'chapter', 'post_status' => 'publish', 'numberposts' => 1 ) );
			if ( isset( $chapter[0] ) ) {
				$type = pb_get_section_type( $chapter[0] );
				if ( 'numberless' !== $type ) {
					++$i;
				}
			} else {
				return 0;
			}
			if ( $key == $post_name ) {
				break;
			}
		}
	}

	if ( 'numberless' == $type ) {
		$i = 0;
	}
	return $i;
}

/**
 * Get chapter, front or back matter type
 *
 * @param $post
 *
 * @return string
 */
function pb_get_section_type( $post ) {
	$type = $post->post_type;
	switch ( $type ) {
		case 'chapter':
			$type = \Pressbooks\Taxonomy::getChapterType( $post->ID );
		break;
		case 'front-matter':
			$type = \Pressbooks\Taxonomy::getFrontMatterType( $post->ID );
		break;
		case 'back-matter':
			$type = \Pressbooks\Taxonomy::getBackMatterType( $post->ID );
		break;
	}

	return $type;
}

/**
 * Returns an array of subsections in front matter, back matter, or chapters.
 *
 * @param $id
 *
 * @return array
 */
function pb_get_subsections( $id ) {
	return \Pressbooks\Book::getSubsections( $id );
}

/**
 * Alias for pb_get_subsections.
 *
 * @param $id
 *
 * @return array
 */
function pb_get_sections( $id ) {
	return pb_get_subsections( $id );
}

/**
 * Is the parse sections option true?
 *
 * @return boolean
 */
function pb_should_parse_subsections() {
	return \Pressbooks\Modules\Export\Export::isParsingSubsections();
}

/**
 * Alias for pb_should_parse_subsections.
 *
 * @return boolean
 */
function pb_should_parse_sections() {
	return pb_should_parse_subsections();
}

/**
 * Tag the subsections
 *
 * @param $content string
 *
 * @return string
 */
function pb_tag_subsections( $content, $id ) {
	return \Pressbooks\Book::tagSubsections( $content, $id );
}

/**
 * Alias for pb_tag_subsections.
 *
 * @param $content string
 *
 * @return string
 */
function pb_tag_sections( $content, $id ) {
	return pb_tag_subsections( $content, $id );
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
