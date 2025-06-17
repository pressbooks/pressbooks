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
		$_GET['optimize-for-print'] = 'true';
		// PDF size tends to shrink if you disable links
		$this->cssOverrides .= "\n" . ':link { prince-link: none !important }' . "\n";
	}

	/**
	 * @return string
	 */
	protected function generateFileName(): string {
		return $this->timestampedFileName( '._print.pdf' );
	}

	/**
	 * Return the desired PDF profile.
	 *
	 * @return string
	 */
	protected function getPdfProfile(): string {
		return 'PDF/X-4';
	}

	/**
	 * Return the desired PDF output intent.
	 *
	 * @return string
	 */
	protected function getPdfOutputIntent(): string {
		return apply_filters( 'pb_prince_output_intent_path', '/usr/lib/prince/icc/USWebCoatedSWOP.icc' );
	}
}
