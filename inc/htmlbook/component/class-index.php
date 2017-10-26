<?php

namespace Pressbooks\HTMLBook\Component;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <section>
 *
 * Attribute requirements: `data-type="index"`
 *
 * Content model: First child must be either <h1> that contains index title, or Header block that contains index title
 * and optional subtitle content; then zero or more Block Elements; then zero or more Sect1 children (<section
 * data-type="sect1">)
 *
 * Best practices: HTMLBook recommends following the [EPUBINDEX] specification and using <ol>/<li>
 * elements for marking up index entries, with data-type attributes used for semantic inflection as appropriate, but
 * none of this is a formal spec requirement
 *
 * Example
 *
 *     <section data-type="index">
 *       <h1>Index Title</h1>
 *       <div data-type="index-group">
 *         <h2>A</h2>
 *         <ol>
 *           <li data-type="index-term">AsciiDoc, <a href="ch01#asciidoc" data-type="index-locator">All about AsciiDoc</a>
 *             <ol>
 *               <li data-type="index-term">conversion to HTML,
 *                 <a href="ch01#asctohtml" data-type="index-locator">AsciiDoc Output Formats</a>
 *               </li>
 *             </ol>
 *           </li>
 *           <li data-type="index-term">azalea, <a href="ch01#azalea" data-type="index-locator">Shrubbery</a></li>
 *         </ol>
 *       </div>
 *     </section>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_index
 */
class Index extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'section';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = true;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'index',
	];

}
