<?php

namespace Pressbooks\HTMLBook\Block;

/**
 * Based on HTMLBook (Unofficial Draft 16 February 2016)
 *
 * _This section is non-normative._
 *
 * Suggested HTML element: <div>
 *
 * Suggested semantics: `class="refentry"`
 *
 * Note: HTMLBook does not currently normatively specify structural semantics for reference entries as they are
 * conceived in DocBook ([DOCBOOK]) or DITA ([DITA]). Use of <div class="refentry"> is suggested for marking
 * up reference entries. The following example illustrates XHTML5 markup one might use for refentry content,
 * which captures DocBook-style semantics
 *
 * Example refentry paralleling [DOCBOOK]:
 *
 *     <div class="refentry">
 *       <header>
 *         <p class="refname">print</p>
 *         <p class="refpurpose">Output some text to stdout.</p>
 *       </header>
 *       <div class="refsynopsisdiv">
 *         <pre class="synopsis">print "<em>Hello World</em>"</pre>
 *       </div>
 *       <div class="refsect1">
 *         <h6>Description</h6>
 *         <p>More description would go here</p>
 *       </div>
 *     </div>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_refentry
 */
class ReferenceEntries {

	/**
	 * @var string
	 */
	protected $tag = 'div';

	/**
	 * @var bool
	 */
	protected $dataTypeRequired = false;

	/**
	 * @var array
	 */
	protected $dataTypes = [];

}
