<?php

namespace Pressbooks\HTMLBook\Component;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <nav>
 *
 * Attribute requirements: `data-type="toc"`
 *
 * Content Model: The TOC must be conformant to the specs for the [EPUB3] Navigation document. First child
 * is zero or more Heading elements (<h1>-<h6>), followed by an <ol> (with <li> children that can contain only a
 * <span> element or an <a> element plus an optional <ol> child)
 *
 * Example
 *
 *     <nav data-type="toc">
 *       <h1>Table of Contents</h1>
 *       <ol>
 *         <li><a href="examples_page.html">A Note Regarding Supplemental Files</a></li>
 *         <li><a href="pr02.html">Foreword</a></li>
 *         <li><a href="pr03.html">Contributors</a>
 *           <ol>
 *             <li><a href="pr03.html#I_sect1_d1e154">Chapter Authors</a></li>
 *             <li><a href="pr03.html#I_sect1_d1e260">Tech Editors</a></li>
 *           </ol>
 *         </li>
 *       </ol>
 *     </nav>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_table_of_contents
 */
class TableOfContents extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'nav';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = true;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'toc',
	];

}
