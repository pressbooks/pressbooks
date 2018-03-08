<?php
/**
 * @author  Book Oven Inc. <code@pressbooks.com>
 * @license GPLv3+
 */

namespace Pressbooks\Modules\SearchAndReplace;

class Result {

	/** @var string */
	public $title;

	/** @var string */
	public $search;

	/** @var string */
	public $replace;

	/** @var mixed */
	public $content;

	/** @var int */
	public $id;

	/** @var mixed */
	public $offset;

	/** @var int */
	public $length;

	/** @var string */
	public $replace_string;

	/** @var string */
	public $search_plain;

	/** @var int */
	public $left;

	/** @var int */
	public $left_length;

	/** @var int */
	public $left_length_replace;

	/** @var string */
	public $replace_plain;

	/**
	 * @return bool
	 */
	function singleLine() {
		if ( strpos( $this->search_plain, "\r" ) !== false || strpos( $this->search_plain, "\n" ) !== false ) {
			return false;
		}
		return true;
	}
}
