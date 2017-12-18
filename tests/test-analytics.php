<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/analytics/namespace.php' );

class AnalyticsTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function test_add_menu() {

		$this->expectOutputRegex( '/<\/p>/' );
		\Pressbooks\Admin\Analytics\analytics_settings_section_callback();
	}

	public function test_print_analytics() {

		switch_to_blog( get_network()->site_id );
		update_site_option( 'ga_mu_uaid', 'TEST1' );
		update_site_option( 'ga_mu_site_specific_allowed', true );
		update_option( 'ga_mu_uaid', 'TEST2' );

		ob_start();
		\Pressbooks\Analytics\print_analytics();
		$buffer = ob_get_clean();
		$this->assertContains( '<script>', $buffer );
		$this->assertContains( 'Google', $buffer );
		$this->assertContains( 'Analytics', $buffer );
		$this->assertContains( 'TEST1', $buffer );
		$this->assertNotContains( 'TEST2', $buffer );

		$this->_book();
		update_option( 'ga_mu_uaid', 'TEST2' );

		ob_start();
		\Pressbooks\Analytics\print_analytics();
		$buffer = ob_get_clean();
		$this->assertContains( 'Google', $buffer );
		$this->assertContains( 'Analytics', $buffer );
		$this->assertContains( 'TEST1', $buffer );
		$this->assertContains( 'TEST2', $buffer );

		delete_site_option( 'ga_mu_site_specific_allowed' );

		ob_start();
		\Pressbooks\Admin\Analytics\print_admin_analytics();
		$buffer = ob_get_clean();
		$this->assertContains( 'TEST1', $buffer );
		$this->assertNotContains( 'TEST2', $buffer );
	}

}
