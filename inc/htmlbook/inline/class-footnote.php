<?php

namespace Pressbooks\HTMLBook\Inline;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <span>
 *
 * Attribute requirements: `data-type="footnote"`
 *
 * Content model: text and/or zero or more Inline Elements
 *
 * Example:
 *
 *     <p>Five out of every six people who try AsciiDoc prefer it to
 *     Markdown<span data-type="footnote">Totally made-up statistic</span></p>
 *
 * Notes:
 *  + The <span> element does not accept block element children (and as of April 2014, nor does any other
 *    HTML5 element that can be used in an inline context and is an acceptable semantic fit for footnotes). If you
 *    need to include multiple blocks of content in a footnote, use <br/> elements to delimit them, e.g.:
 *
 *        <p>This is a really short paragraph.<span data-type="footnote">Largely because I like
 *        to put lots and lots of content in footnotes.<br/><br/>
 *        For example, let me tell you a story about my dog...</span></p>
 *
 *  + Desired rendering of footnote content (i.e., floating/moving footnotes to the bottom of a page or end of a
 *    section, adding appropriate marker symbols/numeration) should be handled by XSL/CSS stylesheet
 *    processing.
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_footnote_endnote
 */
class Footnote extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'span';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = true;

	/**
	 * @var array
	 */
	protected $dataTypes = [
		'footnote',
	];

}
