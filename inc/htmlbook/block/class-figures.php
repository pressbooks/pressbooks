<?php

namespace Pressbooks\HTMLBook\Block;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
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
class Figures {

	/**
	 * @var string
	 */
	protected $tag = 'figure';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

}
