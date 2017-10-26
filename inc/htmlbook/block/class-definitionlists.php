<?php

namespace Pressbooks\HTMLBook\Block;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
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
class DefinitionLists {

	/**
	 * @var string
	 */
	protected $tag = 'dl';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

}
