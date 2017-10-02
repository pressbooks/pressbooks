<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Export\Xhtml;

use Masterminds\HTML5;
use Pressbooks\Sanitize;

/**
 * Service Injection
 *
 * @see https://laravel.com/docs/5.4/blade#service-injection
 */
class Blade extends \Pressbooks\Modules\Export\Blade {

	/**
	 * Endnotes storage container.
	 * Use when overriding the footnote shortcode.
	 *
	 * @var array
	 */
	public $endnotes = [];


	/**
	 * We forcefully reorder some of the front-matter types to respect the Chicago Manual of Style.
	 * Keep track of where we are using this variable.
	 *
	 * @var int
	 */
	public $frontMatterPos = 1;


	/**
	 * Sometimes the user will omit an introduction so we must inject the style in either the first
	 * part or the first chapter ourselves.
	 *
	 * @var bool
	 */
	public $hasIntroduction = false;


	/**
	 * echoMetaData
	 *
	 * @param string $name
	 *
	 * @return string
	 */
	public function sanitizeHtmlMetaKey( $name ) {
		$name = Sanitize\sanitize_xml_id( str_replace( '_', '-', $name ) );
		return $name;
	}

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

	/**
	 * @see \Pressbooks\Sanitize\decode
	 *
	 * @param $slug
	 *
	 * @return mixed
	 */
	public function decode( $slug ) {
		return \Pressbooks\Sanitize\decode( $slug );
	}

	/**
	 * Removes the CC attribution link.
	 *
	 * @since 4.1
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function removeAttributionLink( $content ) {
		$html5 = new HTML5();
		$dom = $html5->loadHTML( $content );

		$urls = $dom->getElementsByTagName( 'a' );
		foreach ( $urls as $url ) {
			/** @var \DOMElement $url */
			// Is this the the attributionUrl?
			if ( $url->getAttribute( 'rel' ) === 'cc:attributionURL' ) {
				$url->parentNode->replaceChild(
					$dom->createTextNode( $url->nodeValue ),
					$url
				);
			}
		}

		$content = $html5->saveHTML( $dom );
		$content = \Pressbooks\Sanitize\strip_container_tags( $content );

		return $content;
	}


	/**
	 * Tidy HTML
	 *
	 * @param string $html
	 *
	 * @return string
	 */
	public function tidy( $html ) {

		// Make XHTML 1.1 strict using htmlLawed

		$config = [
			'valid_xhtml' => 1,
			'no_deprecated_attr' => 2,
			'unique_ids' => 'fixme-',
			'hook' => '\Pressbooks\Sanitize\html5_to_xhtml11',
			'tidy' => -1,
		];

		return \Pressbooks\HtmLawed::filter( $html, $config );
	}


}