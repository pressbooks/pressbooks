<?php

namespace Pressbooks\HTMLBook\Heading;

/**
 * Based on HTMLBook
 *
 * HTML element: <header>
 *
 * Content Model: A Heading element at the proper level designated (h1–h5) for the parent Book Component, as
 * outlined in the previous Headings section (e.g., an <h1> for a chapter <header>); then zero or more <p> elements
 * for subtitles or author attributions, each of which must have a data-type of either subtitle or author
 *
 * Example:
 *
 *     <section data-type="chapter">
 *       <header>
 *         <h1>Chapter title</h1>
 *         <p data-type="subtitle">Chapter subtitle</p>
 *       </header>
 *       <!-- Chapter content here... -->
 *     </section>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#header_block
 */
class Header {

	/**
	 * @var string
	 */
	protected $tag = 'header';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

}
