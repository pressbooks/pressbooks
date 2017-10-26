<?php

namespace Pressbooks\HTMLBook\Inline;

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
class General {

	/**
	 * @var string
	 */
	protected $tag = 'span';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

}
