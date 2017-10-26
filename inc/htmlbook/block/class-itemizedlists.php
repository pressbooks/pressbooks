<?php

namespace Pressbooks\HTMLBook\Block;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * HTML element: <ul>
 *
 * Content model: Zero or more <li> children for each list item
 *
 * Content model for <li> children: Either of the following is acceptable:
 *  + Text and/or zero or more Inline Elements
 *  + Zero or more Block Elements
 *
 * Example:
 *
 *     <ul>
 *       <li>Red</li>
 *       <li>Orange</li>
 *       <!-- And so on -->
 *     </ul>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_itemized_lists
 */
class ItemizedLists {

	/**
	 * @var string
	 */
	protected $tag = 'ul';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

}
