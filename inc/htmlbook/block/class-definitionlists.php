<?php

namespace Pressbooks\HTMLBook\Block;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <dl>
 *
 * Content model: Mirrors [HTML5] Specification
 *
 * Content model for <dt> children: text and/or zero or more Inline Elements
 *
 * Content model for <dd> children: Either of the following is acceptable:
 *  + Text and/or zero or more Inline Elements
 *  + Zero or more Block Elements
 *
 * Example:
 *
 *     <dl>
 *       <dt>Constant Width Bold font</dt>
 *       <dd>Used to indicate user input</dd>
 *     </dl>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_definition_lists
 */
class DefinitionLists extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'dl';

}
