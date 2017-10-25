<?php

namespace Pressbooks\HTMLBook;

use \Masterminds\HTML5;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * @see http://oreillymedia.github.io/HTMLBook
 */
class Element {

	/**
	 * In HTMLBook, the majority of elements classified by the HTML5 specification
	 * as Flow Content (minus elements also categorized as Heading Content, Phrasing
	 * Content, and Sectioning Content) are considered to be Block Elements. Here is
	 * a complete list:
	 *
	 * @see http://oreillymedia.github.io/HTMLBook/#block_elements
	 *
	 * @var array
	 */
	protected $block = [
		'address',
		'aside',
		'audio',
		'blockquote',
		'canvas',
		'dd',
		'details',
		'div',
		'dl',
		'embed',
		'fieldset',
		'figure',
		'form',
		'hr',
		'iframe',
		'map',
		'math', // In MathML vocabulary; must be namespaced under http://www.w3.org/1998/Math/MathML
		'menu',
		'object',
		'ol',
		'p',
		'pre',
		'svg', // In SVG vocabulary; must be namespaced under http://www.w3.org/2000/svg
		'table',
		'ul',
		'video',
	];

	/**
	 * In HTMLBook, the majority of elements classified by the HTML5 specification as
	 * Phrasing Content are considered to be Inline Elements. Here is a complete list:
	 *
	 * @see http://oreillymedia.github.io/HTMLBook/#inline_elements
	 *
	 * @var array
	 */
	protected $inline = [
		'a',
		'abbr',
		'b',
		'bdi',
		'bdo',
		'br',
		'button',
		'command',
		'cite',
		'code',
		'datalist',
		'del',
		'dfn',
		'dt',
		'em',
		'i',
		'input',
		'img',
		'ins',
		'kbd',
		'keygen',
		'label',
		'mark',
		'meter',
		'output',
		'progress',
		'q',
		'ruby',
		's',
		'samp',
		'select',
		'small',
		'span',
		'strong',
		'sub',
		'sup',
		'textarea',
		'time',
		'u',
		'var',
		'wbr',
	];

	/**
	 * @var string
	 */
	protected $tag;

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

	/**
	 * @var string
	 */
	protected $dataType;

	/**
	 * @var array
	 */
	protected $content = [];

	/**
	 * Element constructor.
	 */
	public function __construct() {
		if ( $this->dataTypeRequired === true && count( $this->dataTypes ) === 1 ) {
			$this->setDataType( $this->dataTypes[0] );
		}
	}

	/**
	 * @return string
	 */
	public function getTag(): string {
		return $this->tag;
	}

	/**
	 * @param string $tag
	 */
	public function setTag( string $tag ) {
		$this->tag = $tag;
	}

	/**
	 * @return string
	 */
	public function getDataType(): string {
		return $this->dataType;
	}

	/**
	 * @param string $data_type
	 */
	public function setDataType( string $data_type ) {
		if ( ! in_array( $data_type, $this->dataTypes, true ) ) {
			throw new \LogicException( "Unsupported DataType: {$data_type}, valid values are: " . rtrim( implode( ',', $this->dataTypes ), ',' ) );
		}
		$this->dataType = $data_type;
	}

	/**
	 * @return array
	 */
	public function getContent(): array {
		return $this->content;
	}

	/**
	 * @param array $content
	 */
	public function setContent( array $content ) {
		$this->content = $content;
	}

	/**
	 * @param mixed $content
	 */
	public function appendContent( $content ) {
		$this->content[] = $content;
	}

	/**
	 * @return string
	 */
	public function attributes(): string {
		return '';
	}

	/**
	 * @param mixed $var
	 *
	 * @return bool
	 */
	public function isInline( $var ): bool {

		if ( is_object( $var ) ) {
			$class = get_class( $var );
			return ( strpos( $class, 'Pressbooks\HTMLBook\Inline\\' ) !== false );
		}

		if ( is_string( $var ) ) {
			$html5 = new HTML5();
			$dom = $html5->loadHTML( $var );
			$tags = $dom->getElementsByTagName( '*' );
			if ( $tags->length !== 2 ) {
				return false;
			}
			return ( in_array( $tags->item( 1 )->nodeName, $this->inline, true ) );
		}

		return false;
	}

	/**
	 * @param mixed $var
	 *
	 * @return bool
	 */
	public function isBlock( $var ): bool {

		if ( is_object( $var ) ) {
			$class = get_class( $var );
			return ( strpos( $class, 'Pressbooks\HTMLBook\Block\\' ) !== false );
		}

		if ( is_string( $var ) ) {
			$html5 = new HTML5();
			$dom = $html5->loadHTML( $var );
			$tags = $dom->getElementsByTagName( '*' );
			if ( $tags->length !== 2 ) {
				return false;
			}
			return ( in_array( $tags->item( 1 )->nodeName, $this->block, true ) );
		}

		return false;
	}

	/**
	 * @param mixed $var
	 *
	 * @return bool
	 */
	public function isHeading( $var ): bool {

		if ( is_object( $var ) ) {
			return is_subclass_of( $var, '\Pressbooks\HTMLBook\Heading\Headings' );
		}

		if ( is_string( $var ) ) {
			$headings = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];
			$html5 = new HTML5();
			$dom = $html5->loadHTML( $var );
			$tags = $dom->getElementsByTagName( '*' );
			if ( $tags->length !== 2 ) {
				return false;
			}
			return ( in_array( $tags->item( 1 )->nodeName, $headings, true ) );
		}

		return false;
	}


	/**
	 * @throws \LogicException
	 * @return string
	 */
	public function __toString() {

		if ( empty( $this->tag ) ) {
			throw new \LogicException( 'Tag is required but was not set.' );
		}
		if ( $this->dataTypeRequired && empty( $this->dataType ) ) {
			throw new \LogicException( 'DataType is required but was not set. Valid values are: ' . rtrim( implode( ',', $this->dataTypes ), ',' ) );
		}
		$this->validateContentModel();

		$html = "<{$this->tag}";
		$att = '';
		if ( ! empty( $this->dataType ) ) {
			$att .= 'data-type="' . $this->dataType . '" ';
		}
		$att .= trim( $this->attributes() );
		if ( ! empty( $att ) ) {
			$html .= " {$att}>";
		} else {
			$html .= '>';
		}
		foreach ( $this->content as $content ) {
			$html .= (string) $content;
		}
		$html .= "</{$this->tag}}>";

		return $html;
	}

}
