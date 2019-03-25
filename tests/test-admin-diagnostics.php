<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/diagnostics/namespace.php' );

class Admin_DiagnosticsTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @group diagnostics
	 */
	public function test_render_page() {
		ob_start();
		\Pressbooks\Admin\Diagnostics\render_page();
		$buffer = ob_get_clean();
		$this->assertContains( '<h1>Diagnostics</h1>', $buffer );
	}
}
