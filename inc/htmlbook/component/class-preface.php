<?php

namespace Pressbooks\HTMLBook\Component;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <section>
 *
 * Attribute requirements: `data-type="preface"`, `data-type="foreword"`, or `data-type="introduction"`, depending on content
 *
 * Content model: First child must be either <h1> that contains preface title, or Header block that contains preface
 * title and optional subtitle content; then zero or more Block Elements; then zero or more Sect1 children (<section
 * data-type="sect1">)
 *
 * Example
 *
 *     <section data-type="preface">
 *       <h1>Preface Title</h1>
 *       <p>Preface content</p>
 *       <section data-type="sect1">
 *         <!-- Section content here... -->
 *       </section>
 *     </section>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_preface
 */
class Preface extends Element {

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
		'preface',
		'foreword',
		'introduction',
	];

}
