<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/diagnostics/namespace.php' );

class Admin_DiagnosticsTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @group diagnostics
	 */
	public function test_render_page() {
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1.2 Safari/605.1.1';
		ob_start();
		\Pressbooks\Admin\Diagnostics\render_page();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<h1>Diagnostics</h1>', $buffer );
	}
}
