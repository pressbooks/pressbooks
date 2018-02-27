<?php

namespace Pressbooks\HTMLBook\Block;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <table>
 *
 * Content model: Zero or one <caption> elements (for titled/captioned tables); then zero or more <colgroup>
 * elements; then zero or more <thead> elements; then a choice between either zero or more <tbody> elements, or
 * zero or more <tr> elements; then zero or more <tfoot> elements
 *
 * Content model for <caption>: Either of the following is acceptable:
 *  + Zero or more <p> and/or <div> elements
 *  + Text and/or zero or more Inline Elements
 *
 * Content model for <colgroup>: Mirrors [HTML5] Specification
 *
 * Content models for <thead>, <tbody>, and <tfoot>: Mirror [HTML5] Specification
 *
 * Content model for <tr>: Mirrors [HTML5] Specification, but see content model below for rules for child <td> and
 * <th> elements
 *
 * Content model for <td> and <th> elements: Either of the following is acceptable:
 *  + Text and/or zero or more Inline Elements
 *  + Zero or more Block Elements
 *
 * Examples:
 *
 *     <table>
 *       <caption>State capitals</caption>
 *       <tr>
 *         <th>State</th>
 *         <th>Capital</th>
 *       </tr>
 *       <tr>
 *         <td>Massachusetts</td>
 *         <td>Boston</td>
 *       </tr>
 *       <!-- And so on -->
 *     </table>
 *
 *     <table>
 *       <thead>
 *         <tr>
 *           <th>First</th>
 *           <th>Middle Initial</th>
 *           <th>Last</th>
 *         </tr>
 *       </thead>
 *       <tbody>
 *         <tr>
 *           <td>Alfred</td>
 *           <td>E.</td>
 *           <td>Newman</td>
 *         </tr>
 *         <!-- And so on -->
 *       </tbody>
 *     </table>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_tables
 */
class Tables extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'table';

}
