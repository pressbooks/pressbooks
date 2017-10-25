<?php

namespace Pressbooks\HTMLBook\Component;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * HTML element: <body>
 *
 * Attribute requirements: `data-type="book"` (Not in [EPUB3SSV]; from [DOCBOOK])
 *
 * Content model: Optional <h1> that contains book title, or Header block that contains book title and optional
 * subtitle content; then one or more Book Component elements as children (<div> for Part elements, <nav> for
 * Table of Contents, and <section> elements for all other book divisions)
 *
 * Example
 *
 *     <body data-type="book">
 *       <h1>PHP Cookbook</h1>
 *       <section data-type="chapter">
 *         <!-- Chapter content here -->
 *       </section>
 *     </body>
 *
 * Note: Just as in standard HTML5, <body> is a child of the root <html> element.
 *
 * @see http://oreillymedia.github.io/HTMLBook/#_book
 */
class Book {

	/**
	 * @var string
	 */
	protected $tag = 'body';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = true;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'book',
	];

}
