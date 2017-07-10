<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */

namespace Pressbooks\Modules\Export\Prince;

class PrintPdf extends Pdf {

	/**
	 * @param array $args
	 */
	function __construct( array $args ) {
		parent::__construct( $args );
	}

	/**
	 * @return string
	 */
	protected function generateFileName() {
		return $this->timestampedFileName( '._print.pdf' );
	}

	/**
	 * Return the desired PDF profile.
	 *
	 * @return string
	 */
	protected function getPdfProfile() {
		return 'PDF/X-1a:2003';
	}

	/**
	 * Return the desired PDF output intent.
	 *
	 * @return string
	 */
	protected function getPdfOutputIntent() {
		if ( PB_PRINCE_COMMAND === '/usr/bin/prince' ) {
			return '/usr/lib/prince/icc/USWebCoatedSWOP.icc';
		} else { // Attempt to extrapolate */lib/prince directory based on */bin/prince
			return str_replace( '/bin/prince/', '/lib/prince/', PB_PRINCE_COMMAND ) . 'icc/USWebCoatedSWOP.icc';
		}
	}
}
