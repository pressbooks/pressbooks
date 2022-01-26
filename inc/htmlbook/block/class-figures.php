<?php

namespace Pressbooks\HTMLBook\Block;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <figure>
 *
 * Content model: Either of the following is acceptable:
 *  + A <figcaption> element followed by zero or more Block Elements and/or <img> elements
 *  + Zero or more Block Elements and/or <img> elements, followed by a <figcaption> element
 *
 * Example:
 *
 *     <figure>
 *       <figcaption>Adorable cat</figcaption>
 *       <img src="cute_kitty.gif" alt="Photo of an adorable cat"/>
 *     </figure>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_figures
 */
class Figures extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'figure';

}
