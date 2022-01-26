<?php

namespace Pressbooks\HTMLBook\Component;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <section>
 *
 * Attribute requirements: `data-type="sect1"`, `data-type="sect2"`, `data-type="sect3"`, `data-type="sect4"`, `data-
 * type="sect5"` (From [DOCBOOK] vocabulary), depending on hierarchy level. sect1 is used for <section> elements
 * nested directly in main Book Components ("chapter", "appendix", etc.). sect2 is used for <section> elements
 * nested in a sect1 <section>, sect3 is used for <section> elements nested in a sect2 <section>, and so on.
 *
 * Content model: The first child must either be a main heading element corresponding to the hierarchy level
 * indicated by data-type value, as follows:
 *
 *     "sect1" -> h1
 *     "sect2" -> h2
 *     "sect3" -> h3
 *     "sect4" -> h4
 *     "sect5" -> h5
 *
 * or a Header block that contains section title and optional subtitle content. This is followed by zero or more Block
 * Elements, followed by zero or more <section> elements with a data-type value one level lower in the hierarchy,
 * as long as the parent section is a "sect4" or higher (e.g., <section data-type="sect4"> nested in <section
 * data-type="sect3">)
 *
 * Example:
 *
 *     <section data-type="sect1">
 *       <h1>A-Head</h1>
 *       <p>If you httpparty, you must party hard</p>
 *       <!-- Some more paragraphs -->
 *       <section data-type="sect2">
 *         <h2>B-Head</h2>
 *         <p>What's the frequency, Kenneth?</p>
 *         <!-- And so on... -->
 *       </section>
 *     </section>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_sections
 */
class Sections extends Element {

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
		'sect1',
		'sect2',
		'sect3',
		'sect4',
		'sect5',
	];

}
