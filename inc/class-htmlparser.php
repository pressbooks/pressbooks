<?php

namespace Pressbooks;

use Masterminds\HTML5;

/**
 * This class wraps all our weird HTML parser hacks in one place
 *
 *  Use this parser when we:
 *   + loadHTML
 *   + do not saveXML
 */
class HtmlParser {

	/**
	 * @var array
	 */
	public $errors = [];

	/**
	 * @var \DOMDocument|\Masterminds\HTML5
	 */
	public $parser;

	/**
	 * @param mixed $internal_parser either
	 */
	public function __construct( $internal_parser = false ) {
		if ( $internal_parser === true ) {
			$this->parser = new \DOMDocument();
		} else {
			$this->parser = new HTML5();
		}
	}

	/**
	 * @param $html
	 * @param array $options
	 *
	 * @return \DOMDocument
	 */
	public function loadHTML( $html, $options = [] ) {
		$html = '<div><!-- pb_fixme -->' . $html . '<!-- pb_fixme --></div>';
		if ( $this->parser instanceof \DOMDocument ) {
			libxml_use_internal_errors( true );
			$this->parser->loadHTML( mb_convert_encoding( $html, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
			$this->errors = libxml_get_errors();
			libxml_clear_errors();
			return $this->parser;
		} else {
			return $this->parser->loadHTML( $html, $options );
		}
	}


	/**
	 * @param \DOMDocument $dom
	 *
	 * @return string
	 */
	public function saveHTML( $dom ) {
		if ( $this->parser instanceof \DOMDocument ) {
			libxml_use_internal_errors( true );
			$html = $dom->saveHTML();
			$this->errors = libxml_get_errors();
			libxml_clear_errors();
		} else {
			$html = $this->parser->saveHTML( $dom );
		}

		$html = \Pressbooks\Sanitize\strip_container_tags( $html );
		$html = str_replace( [ '<div><!-- pb_fixme -->', '<!-- pb_fixme --></div>' ], '', $html );

		return $html;
	}


}
