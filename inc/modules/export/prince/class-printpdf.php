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

		// Override
		$this->pdfProfile = 'PDF/X-1a';
		$this->pdfOutputIntent = '/usr/lib/prince/icc/USWebCoatedSWOP.icc';
	}

	/**
	 * @return string
	 */
	protected function generateFileName() {
		return $this->timestampedFileName( '._print.pdf' );
	}
}
