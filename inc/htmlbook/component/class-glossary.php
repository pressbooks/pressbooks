<?php

namespace Pressbooks\HTMLBook\Component;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <section>
 *
 * Attribute requirements: `data-type="glossary"`
 *
 * Content model: First child must be either <h1> that contains glossary title, or Header block that contains
 * glossary title and optional subtitle content; then zero or more Block Elements; then zero or more Sect1 children
 * (<section data-type="sect1">)
 *
 * Best practices: List of glossary terms should be marked up using <dl> elements with a data-type of "glossary",
 * with <dt> children with a data-type of "glossterm" and <dd> children with a data-type of "glossdef". Term text
 * should be wrapped in a <dfn>. However, none of this is formally required by the spec.
 *
 * Example
 *
 *     <section data-type="glossary">
 *       <h1>Glossary Title</h1>
 *       <dl data-type="glossary">
 *         <dt data-type="glossterm">
 *           <dfn>jQuery</dfn>
 *         </dt>
 *         <dd data-type="glossdef">
 *           Widely used JavaScript library
 *         </dd>
 *       </dl>
 *     </section>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#glossary
 */
class Glossary extends Element {

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
		'glossary',
	];

}
