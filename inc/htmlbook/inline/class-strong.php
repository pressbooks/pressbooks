<?php

namespace Pressbooks\HTMLBook\Inline;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <strong>
 *
 * Example:
 *
 *     <p>I <strong>love</strong> HTML!</p>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_strong_generally_for_bold
 */
class Strong extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'strong';

}
