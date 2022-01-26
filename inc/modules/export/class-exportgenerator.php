<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Export;

abstract class ExportGenerator extends Export {

	/**
	 * Mandatory convert method, create $this->outputPath
	 *
	 * @return \Generator
	 */
	abstract function convertGenerator() : \Generator;

	/**
	 * Mandatory validate method, check the sanity of $this->outputPath
	 *
	 * @return \Generator
	 */
	abstract function validateGenerator() : \Generator;
}
