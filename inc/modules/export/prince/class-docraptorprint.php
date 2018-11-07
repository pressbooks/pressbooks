<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version))
 */

namespace Pressbooks\Modules\Export\Prince;

class DocraptorPrint extends Docraptor {

	/**
	 * @since 5.4.0
	 *
	 * Constructor.
	 *
	 * @param array $args
	 */
	public function __construct( array $args ) {
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
		return PB_PLUGIN_URL . 'assets/icc/USWebCoatedSWOP.icc';
	}


	/**
	 * Override based on Theme Options
	 */
	protected function themeOptionsOverrides() {

		parent::themeOptionsOverrides();

		// Output Intent
		$icc = $this->pdfOutputIntent;
		if ( ! empty( $icc ) ) {
			$this->cssOverrides .= "\n" . "@prince-pdf { prince-pdf-output-intent: url('$icc'); } \n";
		}

	}
}
