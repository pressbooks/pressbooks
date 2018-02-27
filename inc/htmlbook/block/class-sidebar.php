<?php

namespace Pressbooks\HTMLBook\Block;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <aside>
 *
 * Attribute requirements: `data-type="sidebar"`
 *
 * Content model: Zero or one <h5> element that contains the sidebar title); then zero or more Block Elements
 *
 * Example:
 *
 *     <aside data-type="sidebar">
 *       <h5>Amusing Digression</h5>
 *       <p>Did you know that in Boston, they call it "soda", and in Chicago, they call it "pop"?</p>
 *     </aside>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_sidebar
 */
class Sidebar extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'aside';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = true;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'sidebar',
	];

}
