<?php

namespace Pressbooks\HTMLBook\Inline;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <code>
 *
 * Example:
 *
 *     <p>Enter <code>echo "Hello World"</code> on the command line</p>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_literal_for_inline_code_elements_variables_functions_etc
 */
class Literal extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'code';

}
