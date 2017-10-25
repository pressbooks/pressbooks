<?php

namespace Pressbooks\HTMLBook\Block;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
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
 * @see http://oreillymedia.github.io/HTMLBook/#_sidebar
 */
class Sidebar {

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
