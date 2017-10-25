<?php

namespace Pressbooks\HTMLBook\Inline;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * HTML element: <em>
 *
 * Example:
 *
 *     <p>I <em>love</em> HTML!</p>
 *
 * @see http://oreillymedia.github.io/HTMLBook/#_emphasis_generally_for_italic
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
