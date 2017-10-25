<?php

namespace Pressbooks\HTMLBook\Inline;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * HTML element: <strong>
 *
 * Example:
 *
 *     <p>I <strong>love</strong> HTML!</p>
 *
 * @see http://oreillymedia.github.io/HTMLBook/#_strong_generally_for_bold
 */
class Strong {

	/**
	 * @var string
	 */
	protected $tag = 'strong';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

}
