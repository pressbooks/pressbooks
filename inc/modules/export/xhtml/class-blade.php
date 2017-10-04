<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Export\Xhtml;

use Pressbooks\Sanitize;

/**
 * Service Injection
 *
 * @see https://laravel.com/docs/5.4/blade#service-injection
 */
class Blade {

	// Fun fact: A data member that is commonly available to all objects of a class is called a static
	// member. Unlike regular data members, static members share the memory space between all objects of the
	// same class.

	/**
	 * Endnotes storage container.
	 * Use when overriding the footnote shortcode.
	 *
	 * @var array
	 */
	public static $endnotes = [];


	/**
	 * We forcefully reorder some of the front-matter types to respect the Chicago Manual of Style.
	 * Keep track of where we are using this variable.
	 *
	 * @var int
	 */
	public static $frontMatterPos = 1;


	/**
	 * Sometimes the user will omit an introduction so we must inject the style in either the first
	 * part or the first chapter ourselves.
	 *
	 * @var bool
	 */
	public static $hasIntroduction = false;


	/**
	 * echoMetaData
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function sanitizeHtmlMetaVal( $content ) {
		$content = trim( strip_tags( html_entity_decode( $content ) ) ); // Plain text
		$content = preg_replace( '/\s+/', ' ', preg_replace( '/\n+/', ' ', $content ) ); // Normalize whitespaces
		$content = Sanitize\sanitize_xml_attribute( $content );
		return $content;
	}


	/**
	 * @see \Pressbooks\Taxonomy::getFrontMatterType
	 *
	 * @param string $id
	 *
	 * @return string
	 */
	public function getFrontMatterType( $id ) {
		return \Pressbooks\Taxonomy::getFrontMatterType( $id );
	}

	/**
	 * @param $id
	 *
	 * @return bool
	 */
	public function showTitle( $id ) {
		return (bool) get_post_meta( $id, 'pb_show_title', true );
	}

}
