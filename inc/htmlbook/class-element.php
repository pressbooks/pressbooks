<?php

namespace Pressbooks\HTMLBook;

use Masterminds\HTML5;

/**
 * Based on HTMLBook
 *
 * + HTMLBook is a subset of XHTML5. All HTMLBook is XHTML5, but not all XHTML5 is HTMLBook.
 * + HTMLBook contains no additional elements or attributes outside of the XHTML5 specification.
 * + HTMLBook is semantically tailored to the structure of a book, including more complex content used in technical and reference documents.
 * + HTMLBook is defined with and can be validated against an XML schema.
 * + HTMLBook stylesheets are written in CSS.
 *
 * @see https://github.com/oreillymedia/HTMLBook
 * @see https://oreillymedia.github.io/HTMLBook
 */
class Element implements \Stringable {

	/**
	 * In HTMLBook, the majority of elements classified by the HTML5 specification
	 * as Flow Content (minus elements also categorized as Heading Content, Phrasing
	 * Content, and Sectioning Content) are considered to be Block Elements. Here is
	 * a complete list:
	 *
	 * @see https://oreillymedia.github.io/HTMLBook/#block_elements
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
	 * @see https://oreillymedia.github.io/HTMLBook/#inline_elements
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
	 * @var \Pressbooks\HtmLawed
	 */
	protected $tidy;

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
	protected $attributes = [];

	/**
	 * @var array
	 */
	protected $content = [];

	/**
	 * Element constructor.
	 */
	public function __construct() {
		if ( $this->dataTypeRequired === true && count( $this->dataTypes ) ) {
			$this->setDataType( $this->dataTypes[0] ); // Start with a default
		}
	}

	/**
	 * @param bool $tidy
	 */
	public function setTidy( bool $tidy, $obj = null ) {
		$this->tidy = $tidy;
	}

	/**
	 * @return string
	 */
	public function getTag() {
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
	public function getDataType() {
		return $this->dataType;
	}

	/**
	 * @return array
	 */
	public function getSupportedDataTypes() {
		return $this->dataTypes;
	}

	/**
	 * @param string $data_type
	 *
	 * @throws \LogicException
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
	public function getAttributes() {
		return $this->attributes;
	}

	/**
	 * @param array $attributes
	 */
	public function setAttributes( array $attributes ) {
		$this->attributes = $attributes;
	}

	/**
	 * @param mixed $attribute
	 */
	public function appendAttributes( $attribute ) {
		if ( is_array( $attribute ) ) {
			foreach ( $attribute as $k => $v ) {
				if ( isset( $this->attributes[ $k ] ) ) {
					$this->attributes[ $k ] .= " $v";
				} else {
					$this->attributes[ $k ] = $v;
				}
			}
		} else {
			$this->attributes[] = $attribute;
		}
	}

	/**
	 * @return array
	 */
	public function getContent() {
		return $this->content;
	}

	/**
	 * @param mixed $content
	 *
	 * @throws \LogicException
	 */
	public function setContent( $content ) {
		if ( ! is_array( $content ) ) {
			$content = [ $content ];
		}
		foreach ( $content as $v ) {
			if ( $this === $v ) {
				throw new \LogicException( 'Recursion problem: cannot set self as content to self' );
			}
		}
		$this->content = $content;
	}

	/**
	 * @param mixed $content
	 *
	 * @throws \LogicException
	 */
	public function appendContent( $content ) {
		if ( $this === $content ) {
			throw new \LogicException( 'Recursion problem: cannot set self as content to self' );
		}
		$this->content[] = $content;
	}

	/**
	 * @param mixed $var
	 *
	 * @return bool
	 */
	public function isInline( $var ) {

		if ( is_object( $var ) ) {
			if ( $var instanceof Element ) {
				return ( in_array( $var->getTag(), $this->inline, true ) );
			}
			return ( str_contains( $var::class, 'Pressbooks\HTMLBook\Inline\\' ) );
		}

		if ( is_string( $var ) ) {
			$html5 = new HTML5();
			$dom = $html5->loadHTMLFragment( $var );
			if ( $dom->childNodes->length !== 1 ) {
				return false;
			}
			return ( in_array( $dom->childNodes->item( 0 )->tagName, $this->inline, true ) );
		}

		return false;
	}

	/**
	 * @param mixed $var
	 *
	 * @return bool
	 */
	public function isBlock( $var ) {

		if ( is_object( $var ) ) {
			if ( $var instanceof Element ) {
				return ( in_array( $var->getTag(), $this->block, true ) );
			}
			return ( str_contains( $var::class, 'Pressbooks\HTMLBook\Block\\' ) );
		}

		if ( is_string( $var ) ) {
			$html5 = new HTML5();
			$dom = $html5->loadHTMLFragment( $var );
			if ( $dom->childNodes->length !== 1 ) {
				return false;
			}
			return ( in_array( $dom->childNodes->item( 0 )->tagName, $this->block, true ) );

		}

		return false;
	}

	/**
	 * @param mixed $var
	 *
	 * @return bool
	 */
	public function isHeading( $var ) {

		$headings = [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ];

		if ( is_object( $var ) ) {
			if ( $var instanceof Element ) {
				return ( in_array( $var->getTag(), $headings, true ) );
			}
			return is_subclass_of( $var, '\Pressbooks\HTMLBook\Heading\Headings' );
		}

		if ( is_string( $var ) ) {
			$html5 = new HTML5();
			$dom = $html5->loadHTMLFragment( $var );
			if ( $dom->childNodes->length !== 1 ) {
				return false;
			}
			return ( in_array( $dom->childNodes->item( 0 )->tagName, $headings, true ) );
		}

		return false;
	}

	/**
	 * @return string
	 */
	public function renderAttributes() {
		$att = '';
		foreach ( $this->attributes as $k => $v ) {
			if ( ! preg_match( '/\d+/', $k ) ) {
				$att .= "{$k}=\"{$v}\" ";
			} else {
				$att .= "{$v} ";
			}
		}
		return trim( $att );
	}

	/**
	 * @return string
	 */
	public function render() {
		return $this->__toString();
	}

	/**
	 * @return string
	 */
	public function __toString(): string {
		if ( empty( $this->tag ) ) {
			trigger_error( 'Tag is required but was not set.', E_USER_ERROR );
		}
		if ( $this->dataTypeRequired && empty( $this->dataType ) ) {
			trigger_error( 'DataType is required but was not set. Valid values are: ' . rtrim( implode( ',', $this->dataTypes ) ), E_USER_ERROR );
		}

		$html = "<{$this->tag}";
		$att = '';
		if ( ! empty( $this->dataType ) ) {
			$att .= "data-type=\"{$this->dataType}\" ";
		}
		$att .= $this->renderAttributes();
		if ( ! empty( $att ) ) {
			$att = trim( $att );
			$html .= " {$att}>";
		} else {
			$html .= '>';
		}

		$inner_html = '';
		foreach ( $this->content as $content ) {
			$inner_html .= (string) $content;
		}
		if ( $this->tidy ) {
			$inner_html = \Pressbooks\Sanitize\prettify( $inner_html );
		}
		$html .= $inner_html;

		$html .= "</{$this->tag}>";

		return $html;
	}

}
