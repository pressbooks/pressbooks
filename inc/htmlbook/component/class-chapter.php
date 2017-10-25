<?php

namespace Pressbooks\HTMLBook\Component;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * HTML element: <section>
 *
 * Attribute requirements: `data-type="chapter"`
 *
 * Content model: First child must be either <h1> that contains chapter title, or Header block that contains chapter
 * title and optional subtitle content; then zero or more Block Elements; then zero or more Sect1 children (<section
 * data-type="sect1">)
 *
 * Example
 *
 *     <section data-type="chapter">
 *       <!-- h1 used for all chapter titles -->
 *       <h1>Chapter Title</h1>
 *       <p>Chapter content</p>
 *       <section data-type="sect1">
 *         <!-- Section content here... -->
 *       </section>
 *     </section>
 *
 * @see http://oreillymedia.github.io/HTMLBook/#_chapter
 */
class Chapter {

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
		'chapter',
	];

}
