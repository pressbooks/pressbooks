<?php

/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

namespace PressBooks\Import\Epub;


class Chapter {

	/**
	 * DOM object
	 *
	 * @var \SimpleXMLElement
	 */
	private $xml;

	private $title = null;

	private $content = null;

	private $parent_id = null;

	private $slug = null;

	private $imagefiles = array();

	private $pb_type;


	/**
	 *
	 * @param type $id_file
	 * @param \SimpleXMLElement $xml
	 * @param $pb_type string - one of either front-matter, back-matter, chapter
	 */
	function __construct( $id_file, \SimpleXMLElement $xml, $pb_type = 'chapter' ) {
		$this->xml = $xml;
		$this->xml->registerXPathNamespace( "n", "http://www.w3.org/1999/xhtml" );
		$this->pb_type = $pb_type;
		//$this->getContent();
		//echo "<pre>" . $this->getContent() . "</pre><br />";die();
	}


	/**
	 *
	 * @param array $imageFiles
	 */
	function setImageFiles( array $imageFiles ) {
		$this->imagefiles = $imageFiles;
	}


	/**
	 *
	 * @return type
	 */
	function getContent() {

		if ( is_null( $this->content ) ) {

			// remove first h1 headline
			$headlines = $this->xml->xpath( '//n:h1' );
			if ( empty ( $headlines ) ) {
				$this->title = 'title-missing';
			} else {
				$headline = $headlines[0];
				$this->title = (string) $headline;
			}
			unset ( $headlines[0][0] );

			$bodies = $this->xml->xpath( '//n:body' );
			$body = $bodies[0];

			$this->parseImages( $body );

			$this->content = str_replace( "\n", ' ', $body->asXML() );
		}

		return $this->content;
	}


	/**
	 *
	 * @param \SimpleXMLElement $body
	 */
	private function parseImages( \SimpleXMLElement $body ) {
		$body->registerXPathNamespace( "n", "http://www.w3.org/1999/xhtml" );
		$imageTags = $body->xpath( '//n:img' );
		foreach ( $imageTags AS $imageTag ) {
			$imageTag['src'] = $this->getImageUrl( (string) $imageTag['src'] );
		}
		//\var_dump($this->imagefiles);
	}


	/**
	 *
	 * @param type $path
	 *
	 * @return type
	 * @throws \Exception
	 */
	private function getImageUrl( $path ) {
		//borks on relative paths
		$path = str_replace( '../', '', $path );

		if ( ! array_key_exists( $path, $this->imagefiles ) ) {
			throw new \Exception ( 'missing image: ' . $path );
		}
		$post_id = $this->imagefiles[$path];
		$post = \get_post( $post_id );

		return $post->guid;
	}


	/**
	 *
	 * @return type
	 */
	function getTitle() {
		if ( is_null( $this->title ) ) {
			$this->getContent();
		}

		return $this->title;
	}


	/**
	 *
	 * @return string
	 */
	function getSlug() {
		// return 'chapter';
		$slug = 'chapter';
		if ( ! empty ( $this->title ) ) {
			$slug = strtolower( $this->title );
			$slug = str_replace( ' ', '-', $slug );
		}

		return $slug;
	}


	/**
	 *
	 * @global type $wpdb
	 * @return type
	 * @throws Exception
	 */
	function getParent() {
		if ( is_null( $this->parent_id ) ) {
			GLOBAL $wpdb;
			$query = "SELECT ID FROM " . $wpdb->posts . " WHERE post_type = 'part' AND post_name = 'main-body'";
			$parents = $wpdb->get_results( $query );
			if ( empty ( $parents ) ) {
				throw new Exception ( 'missing main body' );
			}
			$this->parent_id = $parents[0]->ID;
		}

		return $this->parent_id;
	}


	/**
	 *
	 * @return string
	 */
	function getExcerpt() {
		return '';
	}


	/**
	 *
	 * @return type
	 */
	function getPbType() {
		return $this->pb_type;
	}

}
