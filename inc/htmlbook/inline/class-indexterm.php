<?php

namespace Pressbooks\HTMLBook\Inline;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <a>
 *
 * Attribute requirements: `data-type="indexterm"`; for primary index entry value, use `data-primary`; for secondary
 * index entry value, use `data-secondary`; for tertiary index entry value, use `data-tertiary`; for a "see" index
 * reference, use `data-see`; for a "see also" index reference, use `data-seealso`; for a "sort" value to indicate
 * alphabetization, use `data-primary-sortas`, `data-secondary-sortas`, or `data-tertiary-sortas`; for an "end-of-
 * range" tag that marks the end of an index range, use `data-startref="id_of_opening_index_marker"` (Semantics
 * from [DOCBOOK])
 *
 * Content model: Empty
 *
 * Example:
 *
 *     <p>The Atlas build system<a data-type="indexterm" data-primary="Atlas" data-secondary="build system"/> lets
 *     you build EPUB, Mobi, PDF, and HTML content</p>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_index_term
 */
class IndexTerm extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'a';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = true;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'indexterm',
	];

}
