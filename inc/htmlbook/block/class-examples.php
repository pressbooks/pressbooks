<?php

namespace Pressbooks\HTMLBook\Block;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * HTML element: <div>
 *
 * Attribute requirements: `data-type="example"`
 *
 * Content model: Either of the following content models is acceptable:
 *  + Text and/or zero or more Inline Elements
 *  + Zero or more <h1>–<h6> elements (for title and subtitles), followed by zero or more Block Elements
 *
 * Example:
 *
 *     <div data-type="example">
 *       <h5>Hello World in Python</h5>
 *       <pre data-type="programlisting">print "Hello World"</pre>
 *     </div>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_examples
 */
class Examples {

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
		'example',
	];

}
