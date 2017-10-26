<?php

namespace Pressbooks\HTMLBook\Inline;

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
class Superscripts {

	/**
	 * @var string
	 */
	protected $tag = 'sup';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

}
