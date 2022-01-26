<?php
/**
 * Guess we gotta do this ourselves.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @since 3.9.8.2
 */

namespace Pressbooks;

class HtmLawed {

	/**
	 * Wrapper for htmLawed() function.
	 *
	 * @param string $html
	 * @param int|array $config
	 * @param array|string $spec
	 *
	 * @return string
	 */
	static function filter( $html, array $config = null, $spec = null ) {
		return \Htmlawed::filter( $html, $config, $spec );
	}
}
