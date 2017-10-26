<?php

namespace Pressbooks\HTMLBook\Inline;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <span>
 *
 * Example:
 *
 *     <p>Use your own class attributes for custom styling for formatting
 *     like <span class="underline">underlined text</span></p>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_general_purpose_phrase_markup_for_other_styling_underline_strikethrough_etc
 */
class General extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'span';

}
