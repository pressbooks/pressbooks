<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export\WordPress;

use DOMDocument;
use Exception;
use function Pressbooks\Utility\put_contents;
use Generator;

/**
 * This class will export wxr that can be consumed by a vanilla installation of WP
 */
class VanillaWxr extends Wxr {

	/**
	 * @throws Exception
	 */
	public function convertGenerator(): Generator {
		// Get WXR
		yield 30 => __( 'Transforming WXR.', 'pressbooks' );
		$output = $this->transform( true );

		if ( ! $output ) {
			return false;
		}

		// use error handling to fetch error information as needed
		libxml_use_internal_errors( true );

		$dom = new DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->recover = true; // Try to parse non-well formed documents
		$success = $dom->loadXML( $output, LIBXML_NOBLANKS | LIBXML_NOENT | LIBXML_NONET | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING );
		yield 40 => __( 'Transforming WXR.', 'pressbooks' );

		// replace custom post_type
		// attempting to import custom post types such as 'chapter',
		// 'part', 'front-matter', 'back-matter' fails in a vanilla WP installation
		$post_type = $dom->getElementsByTagName( 'post_type' );

		// check for errors
		if ( ! $success ) {
			throw new Exception( print_r( libxml_get_errors(), true ) ); // @codingStandardsIgnoreLine
		}

		yield 50 => __( 'Processing content.', 'pressbooks' );
		$processed = 0;
		$total = $post_type->length;
		for ( $i = 0; $i < $post_type->length; $i++ ) {
			switch ( $post_type->item( $i )->nodeValue ) {
				case 'chapter':
				case 'front-matter':
				case 'back-matter':
				case 'part':
					$post_type->item( $i )->nodeValue = 'post';
					break;
				default:
					break;
			}
			$processed++;
		}

		// git rid of wp:term declaratation
		// PB generated taxonomy terms don't make it into a vanilla WP installation
		$term = $dom->getElementsByTagName( 'term' );

		// when you remove a child node, the next node becomes the first one,
		// hence '$term->item(0)' and NOT '$term->item($i)'
		$length = $term->length;
		$processed = 0;
		for ( $i = 0; $i < $length; $i++ ) {
			$this->deleteNode( $term->item( 0 ) );
			$processed++;
			if ( $processed % 100 === 0 ) {
				yield "Removing terms: {$processed}/{$length}";
			}
		}
		yield 60 => __( 'Cleaning content.', 'pressbooks' );

		//clean up whitespace
		$dom->formatOutput = true;

		// replace category domain, and nicename attributes
		// easier to manipulate the value of attributes with SimpleXML
		$xml = simplexml_import_dom( $dom );
		unset( $dom );
		yield 65 => __( 'Parsing SimpleXML.', 'pressbooks' );

		// sanity
		if ( ! $xml ) {
			throw new Exception( print_r( libxml_get_errors(), true ) ); // @codingStandardsIgnoreLine
		}

		$category = $xml->xpath( '/rss/channel/item/category' );
		$processed = 0;
		$total = count( $category );

		yield 67 => __( 'Processing categories.', 'pressbooks' );

		foreach ( $category as $uncategorize ) {
			switch ( (string) $uncategorize->attributes()->domain ) {
				case 'front-matter-type':
				case 'back-matter-type':
				case 'chapter-type':
					$uncategorize->attributes()->domain = 'category';
					$uncategorize->attributes()->nicename = 'uncategorized';
					break;
				default:
					break;
			}
			$processed++;
		}

		// convert back to xml string
		$output = $xml->asXML();

		// save wxr as file in exports folder
		$filename = $this->timestampedFileName( '._vanilla.xml' );
		put_contents( $filename, $output );
		$this->outputPath = $filename;
		yield 80 => __( 'Saving WXR file.', 'pressbooks' );

		return $this->outputPath;
	}

	/**
	 * deletes a node and all of its children
	 *
	 * @param \DOMNode $node
	 */
	private function deleteNode( $node ): void {
		$this->deleteChildren( $node );
		$parent = $node->parentNode;
		$parent->removeChild( $node );
	}

	/**
	 * recursive function to delete all children of a node
	 *
	 * @param \DOMNode $node
	 */
	private function deleteChildren( $node ): void {
		while ( isset( $node->firstChild ) ) {
			$this->deleteChildren( $node->firstChild );
			$node->removeChild( $node->firstChild );
		}
	}

}
