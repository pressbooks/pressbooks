<?php

namespace Pressbooks\HTMLBook\Inline;

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
class Literal {

	/**
	 * @var string
	 */
	protected $tag = 'code';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

}
