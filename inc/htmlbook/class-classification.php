<?php

namespace Pressbooks\HTMLBook;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * @see http://oreillymedia.github.io/HTMLBook/#_element_classification
 */
class Classification {

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
}
