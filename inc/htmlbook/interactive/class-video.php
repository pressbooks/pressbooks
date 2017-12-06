<?php

namespace Pressbooks\HTMLBook\Interactive;

use Pressbooks\HTMLBook\Element;

/**
 * Based on HTMLBook
 *
 * HTML element: <video>
 *
 * Example:
 *
 * Note: Fallback content is strongly recommended for output formats that do not support HTML5 interactive
 * content
 *
 *     <video id="asteroids_video" width="480" height="270" controls="controls" poster="images/fallback_image.png">
 *       <source src="video/html5_asteroids.mp4" type="video/mp4"/>
 *       <source src="video/html5_asteroids.ogg" type="video/ogg"/>
 *       <em>Sorry, the &lt;video&gt; element not supported in your
 *       reading system. View the video online at http://example.com.</em>
 *     </video>
 *
 * @see https://oreillymedia.github.io/HTMLBook/#_video
 */
class Video extends Element {

	/**
	 * @var string
	 */
	protected $tag = 'video';

}
