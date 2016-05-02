<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Export\WordPress;

/**
 * This class will export wxr that can be consumed by a vanilla installation of WP
 */
class VanillaWxr extends Wxr {

	function convert() {
		// Get WXR
		$output = $this->queryWxr();

		if ( ! $output ) {
			return false;
		}
		
		// use error handling to fetch error information as needed
		libxml_use_internal_errors( true );

		$dom = new \DOMDocument();
		$dom->preserveWhiteSpace = false;
		$dom->recover = true; // Try to parse non-well formed documents
		$success = $dom->loadXML( $output, LIBXML_NOBLANKS | LIBXML_NOENT | LIBXML_NONET | LIBXML_XINCLUDE | LIBXML_NOERROR | LIBXML_NOWARNING );
		
		// replace custom post_type
		// attempting to import custom post types such as 'chapter',
		// 'part', 'front-matter', 'back-matter' fails in a vanilla WP installation
		$post_type = $dom->getElementsByTagName( 'post_type' );

		// check for errors
		if ( ! $success ) {
			throw new \Exception( print_r( libxml_get_errors(), true ) );
		}

		for ( $i = 0; $i < $post_type->length; $i ++ ) {

			switch ( $post_type->item( $i )->nodeValue ) {
				case 'chapter':
					$post_type->item( $i )->nodeValue = 'post';
					break;
				case 'front-matter':
					$post_type->item( $i )->nodeValue = 'post';
					break;
				case 'back-matter':
					$post_type->item( $i )->nodeValue = 'post';
					break;
				case 'part':
					$post_type->item( $i )->nodeValue = 'post';
					break;
				default:
					break;
			}
		}

		// git rid of wp:term declaratation
		// PB generated taxonomy terms don't make it into a vanilla WP installation
		$term = $dom->getElementsByTagName( 'term' );

		// when you remove a child node, the next node becomes the first one,
		// hence '$term->item(0)' and NOT '$term->item($i)'
		$length = $term->length;
		for ( $i = 0; $i < $length; $i ++ ) {
			$this->deleteNode( $term->item( 0 ) );
		}
		
		//clean up whitespace
		$dom->formatOutput = true;
		
		// replace category domain, and nicename attributes
		// easier to manipulate the value of attributes with SimpleXML
		$xml = simplexml_import_dom( $dom );
		unset( $dom );

		// sanity
		if ( ! $xml ) {
			throw new \Exception( print_r( libxml_get_errors(), true ) );
		}

		$category = $xml->xpath( '/rss/channel/item/category' );

		foreach ( $category as $uncategorize ) {

			switch ( ( string ) $uncategorize->attributes()->domain ) {
				case 'front-matter-type':
					$uncategorize->attributes()->domain = 'category';
					$uncategorize->attributes()->nicename = 'uncategorized';
					break;
				case 'back-matter-type':
					$uncategorize->attributes()->domain = 'category';
					$uncategorize->attributes()->nicename = 'uncategorized';
					break;
				case 'chapter-type':
					$uncategorize->attributes()->domain = 'category';
					$uncategorize->attributes()->nicename = 'uncategorized';
					break;

				default:
					break;
			}
		}

		// convert back to xml string
		$output = $xml->asXML();
		
		// save wxr as file in exports folder
		$filename = $this->timestampedFileName( '._vanilla.xml' );
		file_put_contents( $filename, $output );
		$this->outputPath = $filename;

		return true;
	}

	/**
	 * deletes a node and all of its children
	 * 
	 * @param \DOMNode $node
	 */
	private function deleteNode( $node ) {
		$this->deleteChildren( $node );
		$parent = $node->parentNode;
		$oldnode = $parent->removeChild( $node );
	}

	/**
	 * recursive function to delete all children of a node
	 * 
	 * @param \DOMNode $node
	 */
	private function deleteChildren( $node ) {
		while ( isset( $node->firstChild ) ) {
			$this->deleteChildren( $node->firstChild );
			$node->removeChild( $node->firstChild );
		}
	}

}
