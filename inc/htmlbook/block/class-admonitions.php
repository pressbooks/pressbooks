<?php

namespace Pressbooks\HTMLBook\Block;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <div>
 *
 * Attribute requirements: `data-type="note"`, `data-type="warning"`, `data-type="tip"`, `data-type="caution"`, or
 * `data-type="important"`, depending on the content within
 *
 * Content model: Either of the following content models is acceptable:
 *  + Text and/or zero or more Inline Elements
 *  + Zero or more <h1>–<h6> elements, followed by zero or more Block Elements
 *
 * Examples:
 *
 *     <div data-type="note">
 *       <h1>Helpful Info</h1>
 *       <p>Please take note of this important information</p>
 *     </div>
 *
 *     <div data-type="warning">Make sure to get your AsciiDoc markup right!</div>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_admonitions
 */
class Admonitions extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'div';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = true;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'note',
		'warning',
		'tip',
		'caution',
		'important',
	];

}
