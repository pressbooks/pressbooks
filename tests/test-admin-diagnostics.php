<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/diagnostics/namespace.php' );

use Pressbooks\Container;
use function Pressbooks\Admin\Diagnostics\render_page;

class Admin_DiagnosticsTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @group diagnostics
	 */
	public function test_render_page() {
		$this->_book();
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1.2 Safari/605.1.1';
		ob_start();
		render_page();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<h1>Diagnostics</h1>', $buffer );
		$this->assertStringContainsString( 'Book created with Pressbooks ' . PB_PLUGIN_VERSION, $buffer );
		$this->assertStringContainsString( 'Book created with Buckram ' . Container::get( 'Styles' )->getBuckramVersion(), $buffer );
	}
}
