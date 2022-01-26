<?php

namespace Pressbooks\HTMLBook\Component;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <section>
 *
 * Attribute requirements: `data-type="halftitlepage"`, `data-type="titlepage"`, `data-type="copyright-page"`, or
 * `data-type="dedication"`, depending on content
 *
 * Content model: First child must be either <h1> that contains frontmatter section title, or Header block that
 * contains frontmatter title and optional subtitle content; then zero or more Block Elements; then zero or more
 * Sect1 children (<section data-type="sect1">)
 *
 * Example
 *
 *     <section data-type="titlepage">
 *       <h1>PHP in a Nutshell</h1>
 *       <p>By Paul Hudson</p>
 *     </section>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_frontmatter
 */
class Frontmatter extends Element {

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
		'halftitlepage',
		'titlepage',
		'copyright-page',
		'dedication',
	];

}
