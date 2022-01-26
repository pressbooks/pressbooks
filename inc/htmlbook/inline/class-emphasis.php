<?php

namespace Pressbooks\HTMLBook\Inline;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <em>
 *
 * Example:
 *
 *     <p>I <em>love</em> HTML!</p>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_emphasis_generally_for_italic
 */
class Emphasis extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'em';

}
