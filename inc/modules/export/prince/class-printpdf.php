<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version))
 */

namespace Pressbooks\Modules\Export\Prince;

class PrintPdf extends Pdf {

	/**
	 * @param array $args
	 */
	function __construct( array $args ) {
		parent::__construct( $args );
		$this->url .= '&optimize-for-print=1';

		// PDF size tends to shrink if you disable links
		$this->cssOverrides .= "\n" . ':link { prince-link: none !important }' . "\n";
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
