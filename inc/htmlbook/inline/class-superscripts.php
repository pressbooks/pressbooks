<?php

namespace Pressbooks\HTMLBook\Inline;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <sup>
 *
 * Example:
 *
 *     <p>The area of a circle is πr<sup>2</sup></p>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_superscripts
 */
class Superscripts extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'sup';

}
