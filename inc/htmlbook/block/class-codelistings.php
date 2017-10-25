<?php

namespace Pressbooks\HTMLBook\Block;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * HTML element: <pre>
 *
 * Attribute requirements: `data-type="programlisting"`
 *
 * Optional HTMLBook-specific attribute: `data-code-language`, used to indicate language of code listing (e.g.,
 * `data-code-language="php"`)
 *
 * Example:
 *
 *     <pre data-type="programlisting">print "<em>Hello World</em>"</pre>
 *
 * @see http://oreillymedia.github.io/HTMLBook/#_code_listings
 */
class CodeListings {

	/**
	 * @var string
	 */
	protected $tag = 'pre';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = true;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'programlisting',
	];

}
