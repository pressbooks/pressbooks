<?php

use Pressbooks\Book;
use Pressbooks\Container;
use Pressbooks\CustomCss;

use function Pressbooks\Sanitize\decode;
use function Pressbooks\Sanitize\strip_br;

/**
 * Shortcut to \Pressbooks\Book::get( 'prev' );
 *
 * @return string URL of previous post
 */
function pb_get_prev()
{

    return Book::get('prev');
}

/**
 * Shortcut to \Pressbooks\Book::get( 'prev', true );
 *
 * @return int|false
 */
function pb_get_prev_post_id()
{

    return Book::get('prev', true);
}

/**
 * Shortcut to \Pressbooks\Book::get( 'next' );
 *
 * @return string URL of next post
 */
function pb_get_next()
{

    return Book::get('next');
}

/**
 * Shortcut to \Pressbooks\Book::get( 'next', true );
 *
 * @return int|false
 */
function pb_get_next_post_id()
{

    return Book::get('next', true);
}

/**
 * Shortcut to \Pressbooks\Book::get( 'first' );
 *
 * @return string URL of first post
 */
function pb_get_first()
{

    return Book::get('first');
}

/**
 * Shortcut to \Pressbooks\Book::get( 'first', true );
 *
 * @return int|false
 */
function pb_get_first_post_id()
{

    return Book::get('first', true);
}

/**
 * Shortcut to \Pressbooks\Book::getBookInformation();
 *
 * @return array
 */
function pb_get_book_information()
{

    return Book::getBookInformation();
}

/**
 * Shortcut to \Pressbooks\Book::getBookStructure();
 *
 * @return array
 */
function pb_get_book_structure()
{

    return Book::getBookStructure();
}

/**
 * Shortcut to \Pressbooks\Sanitize\decode();
 *
 * @param $val
 *
 * @return mixed
 */
function pb_decode($val)
{

    return decode($val);
}

/**
 * Shortcut to \Pressbooks\Sanitize\strip_br();
 *
 * @param $val
 *
 * @return string
 */
function pb_strip_br($val)
{

    return strip_br($val);
}

/**
 * Shortcut to \Pressbooks\CustomCss::isCustomCss();
 *
 * @deprecated Leftover code from old Custom CSS Editor. Use Custom Styles instead.
 *
 * @return bool
 */
function pb_is_custom_theme()
{

    return CustomCss::isCustomCss();
}

/**
 * Shortcut to \Pressbooks\Container::get('Styles')->isCurrentThemeCompatible( $version );
 *
 * @return bool
 */
function pb_is_scss($version = 1)
{

    if (Container::get('Styles')->isCurrentThemeCompatible($version)) {
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
function pb_get_seo_meta_elements()
{
    _deprecated_function('pb_get_seo_meta_elements', '5.7.0');
}

/**
 * Shortcut to \Pressbooks\Metadata\get_microdata_elements();
 *
 * @deprecated 5.7.0
 *
 * @return string
 */
function pb_get_microdata_elements()
{
    _deprecated_function('pb_get_microdata_elements', '5.7.0');
}

/**
 * Get url to the custom stylesheet for web.
 *
 * @deprecated Leftover code from old Custom CSS Editor. Use Custom Styles instead.
 *
 * @see: \Pressbooks\CustomCss
 * @return string
 */
function pb_get_custom_stylesheet_url()
{

    $current_blog_id = get_current_blog_id();

    if (is_file(WP_CONTENT_DIR . "/blogs.dir/{$current_blog_id}/files/custom-css/web.css")) {
        return \Pressbooks\Sanitize\maybe_https(content_url("/blogs.dir/{$current_blog_id}/files/custom-css/web.css"));
    } elseif (is_file(WP_CONTENT_DIR . "/uploads/sites/{$current_blog_id}/custom-css/web.css")) {
        return \Pressbooks\Sanitize\maybe_https(content_url("/uploads/sites/{$current_blog_id}/custom-css/web.css"));
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
function pb_get_chapter_number($post_id)
{

    return Book::getChapterNumber($post_id);
}

/**
 * Get chapter, front or back matter type
 *
 * @param WP_Post $post
 *
 * @return string
 */
function pb_get_section_type($post)
{
    $type = $post->post_type;
    $taxonomy = \Pressbooks\Taxonomy::init();
    switch ($type) {
        case 'chapter':
            $type = $taxonomy->getChapterType($post->ID);
            break;
        case 'front-matter':
            $type = $taxonomy->getFrontMatterType($post->ID);
            break;
        case 'back-matter':
            $type = $taxonomy->getBackMatterType($post->ID);
            break;
        case 'glossary':
            $type = $taxonomy->getGlossaryType($post->ID);
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
function pb_get_subsections($id)
{
    return Book::getSubsections($id);
}

/**
 * Alias for pb_get_subsections.
 *
 * @param $id
 *
 * @return array
 */
function pb_get_sections($id)
{
    return pb_get_subsections($id);
}

/**
 * Returns an array of all subsections in the book, grouped by content type.
 *
 * @param array $book_structure The book structure from getBookStructure()
 * @return array The subsections, grouped by parent post type
 */
function pb_get_all_subsections($book_structure)
{
    return Book::getAllSubsections($book_structure);
}

/**
 * Is the parse sections option true?
 *
 * @return boolean
 */
function pb_should_parse_subsections()
{
    return \Pressbooks\Modules\Export\Export::shouldParseSubsections();
}

/**
 * Alias for pb_should_parse_subsections.
 *
 * @return boolean
 */
function pb_should_parse_sections()
{
    return pb_should_parse_subsections();
}

/**
 * Tag the subsections
 *
 * @param $content string
 *
 * @return string
 */
function pb_tag_subsections($content, $id)
{
    $tagged_content = Book::tagSubsections($content, $id);
    return ($tagged_content === false) ? $content : $tagged_content;
}

/**
 * Alias for pb_tag_subsections.
 *
 * @param $content string
 *
 * @return string
 */
function pb_tag_sections($content, $id)
{
    return pb_tag_subsections($content, $id);
}

/**
 * Rename image with arbitrary suffix (before extension)
 *
 * @param $thumb
 * @param $path
 *
 * @return string
 */
function pb_thumbify($thumb, $path)
{
    return \Pressbooks\Image\thumbify($thumb, $path);
}
