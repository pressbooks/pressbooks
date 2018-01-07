<?php

namespace Pressbooks\HTMLBook\Block;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <p>
 *
 * Example:
 *
 *     <p>This is a standard paragraph with some <em>emphasized text</em></p>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_paragraph
 */
class Paragraph extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'p';

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'subtitle',
		'author',
	];
}
