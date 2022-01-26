<?php

namespace Pressbooks\HTMLBook\Component;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <section>
 *
 * Attribute requirements: `data-type="appendix"` or `data-type="afterword"`, depending on content
 *
 * Content model: First child must be either <h1> that contains appendix title, or Header block that contains
 * appendix title and optional subtitle content; then zero or more Block Elements; then zero or more Sect1 children
 * (<section data-type="sect1">)
 *
 * Example
 *
 *     <section data-type="appendix">
 *       <h1>Appendix Title</h1>
 *       <p>Appendix content</p>
 *       <section data-type="sect1">
 *         <!-- Section content here... -->
 *       </section>
 *     </section>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_appendix
 */
class Appendix extends Element {

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
		'appendix',
		'afterword',
	];

}
