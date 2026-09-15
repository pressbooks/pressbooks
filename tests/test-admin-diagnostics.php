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

	/**
	 * @group diagnostics
	 */
	public function test_render_page_book_privacy() {
		$this->_book();
		$_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1.2 Safari/605.1.1';
		$_SERVER['SERVER_SOFTWARE'] = 'nginx/1.26.1';
		update_option( 'blog_public', 0 );
		ob_start();
		\Pressbooks\Admin\Diagnostics\render_page();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'Book Privacy: Private', $buffer );
		update_option( 'blog_public', 1 );
		ob_start();
		\Pressbooks\Admin\Diagnostics\render_page();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'Book Privacy: Public', $buffer );
	}
}
