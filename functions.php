<?php
/**
 * Shortcuts for template designers who don't use real namespaces, and other helper functions.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

if ( ! function_exists( 'app' ) ) {
	/**
	 * Fake Laravel app() so we can use Blade @inject directive
	 *
	 * @see https://github.com/laravel/framework/blob/5.4/src/Illuminate/Foundation/helpers.php#L96
	 *
	 * @param  string $abstract
	 * @param  array $parameters
	 *
	 * @return mixed
	 */
	function app( $abstract = null, array $parameters = [] ) {
		if ( is_null( $abstract ) ) {
			return \Pressbooks\Container::getInstance();
		}
		return empty( $parameters )
			? \Pressbooks\Container::getInstance()->make( $abstract )
			: \Pressbooks\Container::getInstance()->makeWith( $abstract, $parameters );
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
 * Shortcut to \Pressbooks\Book::get( 'prev', true );
 *
 * @return int|false
 */
function pb_get_prev_post_id() {

	return \Pressbooks\Book::get( 'prev', true );
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
 * Shortcut to \Pressbooks\Book::get( 'next', true );
 *
 * @return int|false
 */
function pb_get_next_post_id() {

	return \Pressbooks\Book::get( 'next', true );
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
 * Shortcut to \Pressbooks\Book::get( 'first', true );
 *
 * @return int|false
 */
function pb_get_first_post_id() {

	return \Pressbooks\Book::get( 'first', true );
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
 * @deprecated 5.7.0
 *
 * @return string
 */
function pb_get_seo_meta_elements() {
	_deprecated_function( 'pb_get_seo_meta_elements', '5.7.0' );
}

/**
 * Shortcut to \Pressbooks\Metadata\get_microdata_elements();
 *
 * @deprecated 5.7.0
 *
 * @return string
 */
function pb_get_microdata_elements() {
	_deprecated_function( 'pb_get_microdata_elements', '5.7.0' );
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
 * Get "real" chapter number for Web + REST API
 *
 * @param int $post_id
 *
 * @return int
 */
function pb_get_chapter_number( $post_id ) {

	return \Pressbooks\Book::getChapterNumber( $post_id );
}

/**
 * Get formatted chapter number.
 *
 * @param int    $post_id
 * @param string $type_of
 *
 * @return string
 */
function pb_get_section_number( $post_id, $type_of = 'webbook' ) {
	$options = get_option( 'pressbooks_theme_options_global', [] );

	if ( empty( $options['chapter_numbers'] ) ) {
		return '';
	}

	$number = ! empty( $options['chapter_numbering_restart_per_part'] )
		? pb_get_chapter_number_per_part( $post_id, $type_of )
		: pb_get_chapter_number( $post_id );

	return pb_convert_section_number(
		$number,
		$options['chapter_numbering_style'] ?? 'arabic'
	);
}

/**
 * Get formatted part number.
 *
 * @param int $n
 *
 * @return string
 */
function pb_get_part_number( $n ) {
	$options = get_option( 'pressbooks_theme_options_global', [] );

	if ( empty( $options['chapter_numbers'] ) ) {
		return '';
	}

	return pb_convert_section_number(
		$n,
		$options['part_numbering_style'] ?? 'roman_upper'
	);
}

/**
 * Convert a number to the configured numbering style.
 *
 * @param int    $n
 * @param string $style
 *
 * @return string
 */
function pb_convert_section_number( int $n, string $style ): string {
	if ( $n === 0 ) {
		return '';
	}

	switch ( $style ) {
		case 'roman_upper':
			return \Pressbooks\L10n\romanize( $n );
		case 'roman_lower':
			return strtolower( \Pressbooks\L10n\romanize( $n ) );
		case 'alphabetical_upper':
			return pb_number_to_letters( $n, true );
		case 'alphabetical_lower':
			return pb_number_to_letters( $n, false );
		case 'arabic':
			return (string) $n;
		default:
			return '';
	}
}

/**
 * Convert a number to alphabetical notation.
 *
 * @param int  $n
 * @param bool $uppercase
 *
 * @return string
 */
function pb_number_to_letters( int $n, bool $uppercase = true ): string {
	$letters = '';

	while ( $n > 0 ) {
		$remainder = ( $n - 1 ) % 26;
		$letters = chr( ( $uppercase ? 65 : 97 ) + $remainder ) . $letters;
		$n = intdiv( $n - 1, 26 );
	}

	return $letters;
}

/**
 * Get chapter number, restarting from 1 within each part.
 *
 * @param int    $post_id
 * @param string $type_of
 *
 * @return int
 */
function pb_get_chapter_number_per_part( $post_id, $type_of = 'webbook' ) {

	$structure = \Pressbooks\Book::getBookStructure();
	$lookup = $structure['__order'];

	$post_statii = ( $type_of === 'webbook' )
		? [ 'web-only', 'publish' ]
		: [ 'private', 'publish' ];

	if (
		empty( get_option( 'pressbooks_theme_options_global', [] )['chapter_numbers'] ) ||
		empty( $lookup[ $post_id ] ) ||
		$lookup[ $post_id ]['post_type'] !== 'chapter' ||
		! in_array( $lookup[ $post_id ]['post_status'], $post_statii, true )
	) {
		return 0;
	}

	$current_part = null;

	if ( ! empty( $structure['part'] ) ) {
		foreach ( $structure['part'] as $part ) {
			if ( empty( $part['chapters'] ) ) {
				continue;
			}

			foreach ( $part['chapters'] as $chapter ) {
				if ( (int) $chapter['ID'] === (int) $post_id ) {
					$current_part = $part;
					break 2;
				}
			}
		}
	}

	if ( ! $current_part ) {
		return 0;
	}

	$i = 0;
	$type = 'standard';
	$found = array_merge( [ 'ID' => $post_id ], $lookup[ $post_id ] );

	foreach ( $current_part['chapters'] as $chapter ) {
		$cid = $chapter['ID'];

		if ( empty( $lookup[ $cid ] ) ) {
			continue;
		}

		$val = $lookup[ $cid ];

		if (
			$val['post_type'] !== 'chapter' ||
			! in_array( $val['post_status'], $post_statii, true )
		) {
			continue;
		}

		$type = \Pressbooks\Taxonomy::init()->getChapterType( $cid );

		if ( 'numberless' !== $type ) {
			++$i;
		}

		if ( (int) $cid === (int) $found['ID'] ) {
			break;
		}
	}

	return ( $type === 'numberless' ) ? 0 : $i;
}

/**
 * Get chapter, front or back matter type
 *
 * @param WP_Post $post
 *
 * @return string
 */
function pb_get_section_type( $post ) {
	$type = $post->post_type;
	$taxonomy = \Pressbooks\Taxonomy::init();
	switch ( $type ) {
		case 'chapter':
			$type = $taxonomy->getChapterType( $post->ID );
			break;
		case 'front-matter':
			$type = $taxonomy->getFrontMatterType( $post->ID );
			break;
		case 'back-matter':
			$type = $taxonomy->getBackMatterType( $post->ID );
			break;
		case 'glossary':
			$type = $taxonomy->getGlossaryType( $post->ID );
			break;  }

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
 * Returns an array of all subsections in the book, grouped by content type.
 *
 * @param array $book_structure The book structure from getBookStructure()
 * @return array The subsections, grouped by parent post type
 */
function pb_get_all_subsections( $book_structure ) {
	return \Pressbooks\Book::getAllSubsections( $book_structure );
}

/**
 * Is the parse sections option true?
 *
 * @return boolean
 */
function pb_should_parse_subsections() {
	return \Pressbooks\Modules\Export\Export::shouldParseSubsections();
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
	$tagged_content = \Pressbooks\Book::tagSubsections( $content, $id );
	return ( $tagged_content === false ) ? $content : $tagged_content;
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
	return \Pressbooks\Image\thumbify( $thumb, $path );
}
