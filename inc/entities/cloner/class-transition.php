<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Entities\Cloner;

/**
 * When cloning a post from one book to another, the IDs change, maybe other properties too.
 * Use this class to keep track of transitions.
 */
class Transition {

	/**
	 * @var string
	 */
	public $type = '';

	/**
	 * @var int
	 */
	public $oldId = 0;

	/**
	 * @var int
	 */
	public $newId = 0;
}
