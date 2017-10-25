<?php

namespace Pressbooks\HTMLBook\Inline;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
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
 * @see http://oreillymedia.github.io/HTMLBook/#_cross_references
 */
class CrossReferences {

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
