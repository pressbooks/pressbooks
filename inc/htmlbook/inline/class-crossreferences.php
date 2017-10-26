<?php

namespace Pressbooks\HTMLBook\Inline;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <a>
 *
 * Attribute requirements: data-type="xref" (From [DOCBOOK]); an href attribute that should point to the id of a
 * local HTMLBook resource referenced; data-xrefstyle (optional) for specifying the style of XREF
 *
 * Example:
 *
 *     <section id="html5" data-type="chapter">
 *       <h1>Intro to HTML5<h1>
 *       <p>As I said at the beginning of <a data-type="xref" href="#html5">Chapter 1</a>, HTML5 is great...</p>
 *       <!-- Blah blah blah -->
 *     </section>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_cross_references
 */
class CrossReferences extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'a';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = true;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'xref',
	];

}
