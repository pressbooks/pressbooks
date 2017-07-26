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
		return apply_filters( 'pb_prince_output_intent_path', '/usr/lib/prince/icc/USWebCoatedSWOP.icc' );
	}
}
