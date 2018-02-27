<?php

namespace Pressbooks\HTMLBook\Inline;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <sub>
 *
 * Example:
 *
 *     <p>The formula for water is H<sub>2</sub>O</p>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_subscripts
 */
class Subscripts extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'sub';

}
