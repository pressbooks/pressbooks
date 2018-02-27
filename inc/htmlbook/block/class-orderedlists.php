<?php

namespace Pressbooks\HTMLBook\Block;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <ol>
 *
 * Content model: Zero or more <li> children for each list item
 *
 * Content model for <li> children: Either of the following is acceptable:
 *  + Text and/or zero or more Inline Elements
 *  + Zero or more Block Elements
 *
 * Example:
 *
 *     <ol>
 *       <li>Step 1</li>
 *       <li>
 *         <p>Step 2</p>
 *         <p>Step 2 continued</p>
 *       </li>
 *       <!-- And so on -->
 *     </ol>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_ordered_lists
 */
class OrderedLists extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'ol';

}
