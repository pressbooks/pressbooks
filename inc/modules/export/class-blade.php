<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Modules\Export;

/**
 * Service Injection
 *
 * @see https://laravel.com/docs/5.4/blade#service-injection
 */
class Blade {

	/**
	 * Will create an html blob of copyright, returns empty string if something goes wrong
	 *
	 * @param array $metadata
	 * @param string $title (optional)
	 * @param int $id (optional)
	 * @param bool $suppress_exception (optional, default is true)
	 *
	 * @return string $html blob
	 * @throws \Exception
	 */
	public function doCopyrightLicense( $metadata, $title = '', $id = 0, $suppress_exception = true ) {

		try {
			$licensing = new \Pressbooks\Licensing();
			return $licensing->doLicense( $metadata, $id, $title );
		} catch ( \Exception $e ) {
			if ( ! $suppress_exception ) {
				throw $e;
			}
		}
		return '';
	}

}