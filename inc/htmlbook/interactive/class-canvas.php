<?php

namespace Pressbooks\HTMLBook\Interactive;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <canvas>
 *
 * Notes: Should include fallbacks for environments that don’t support HTML5 or JavaScript (e.g., link or image).
 * You may include <script> elements in your HTMLBook document <head> elements to include/reference JS code
 * for Canvas handling.
 *
 * Examples:
 *
 *     <canvas id="canvas" width="400" height="400">
 *       Your browser does not support the HTML 5 Canvas. See the interactive example at http://example.com.
 *     </canvas>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_canvas
 */
class Canvas extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'canvas';

}
