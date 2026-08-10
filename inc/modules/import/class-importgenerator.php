<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import;

abstract class ImportGenerator extends Import {

	/**
	 * @param array $current_import WP option 'pressbooks_current_import'
	 *
	 * @return \Generator
	 */
	abstract public function importGenerator( array $current_import ): \Generator;
}
