<?php

namespace Pressbooks\HTMLBook\Inline;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * HTML element: <sub>
 *
 * Example:
 *
 *     <p>The formula for water is H<sub>2</sub>O</p>
 *
 * @see http://oreillymedia.github.io/HTMLBook/#_subscripts
 */
class Subscripts {

	/**
	 * @var string
	 */
	protected $tag = 'sub';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

}
