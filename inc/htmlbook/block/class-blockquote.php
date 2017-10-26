<?php

namespace Pressbooks\HTMLBook\Block;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <blockquote>
 *
 * Content model: Either of the following is acceptable:
 *  + Text and/or zero or more Inline Elements
 *  + Zero or more Block Elements
 *
 * Example:
 *
 *     <blockquote>
 *       <p>When in the course of human events...</p>
 *       <p data-type="attribution">U.S. Declaration of Independence</p>
 *     </blockquote>
 *
 * Note: If the blockquote is an epigraph, add `data-type="epigraph"`, e.g.:
 *
 *     <section data-type="chapter">
 *       <h1>Conclusion</h1>
 *       <blockquote data-type="epigraph">
 *         <p>It ain't over till it's over.</p>
 *         <p data-type="attribution">Yogi Berra</p>
 *       </blockquote>
 *       <p>In this final chapter of the book, we will…<p>
 *     </section>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_blockquote
 */
class Blockquote extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'blockquote';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'epigraph',
	];
}
