<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */
namespace Pressbooks\PostType;

/**
 * This is the contract for back-matter post types to be able to create customized views like glossary or contributors DRY
 */
interface BackMatter {
	/**
	 * @param string $content WordPress the_content
	 * @return mixed
	 */
	public function overrideDisplay( $content );
}
