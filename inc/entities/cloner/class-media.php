<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Entities\Cloner;

/**
 * The media terminology we see in the user interface, or in the REST API, rarely matches where we get it in the database.
 * Use this class to map so that your IDE auto-fills when things seem hopeless.
 */
class Media {

	/**
	 * @var int
	 */
	public $id = 0;

	/**
	 * @var string
	 */
	public $title = '';

	/**
	 * @var array
	 */
	public $meta = [];

	/**
	 * @var string
	 */
	public $description = '';

	/**
	 * @var string
	 */
	public $caption = '';

	/**
	 * @var string
	 */
	public $altText = '';

	/**
	 * @var string
	 */
	public $sourceUrl = '';
}
