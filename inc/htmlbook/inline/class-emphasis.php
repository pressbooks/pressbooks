<?php

namespace Pressbooks\HTMLBook\Inline;

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
class Emphasis {

	/**
	 * @var string
	 */
	protected $tag = 'em';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

}
