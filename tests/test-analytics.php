<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/analytics/namespace.php' );

class AnalyticsTest extends \WP_UnitTestCase {

	public function test_add_menu() {

		$this->expectOutputRegex( '/<\/p>/' );
		\Pressbooks\Admin\Analytics\analytics_settings_section_callback();
	}

	public function test_analytics_ga_mu_uaid_sanitize() {

		$this->assertInternalType( 'string', \Pressbooks\Admin\Analytics\analytics_ga_mu_uaid_sanitize( 'UA-123456-7890' ) );
	}

	public function test_analytics_ga_mu_site_specific_allowed_sanitize() {

		$this->assertInternalType( 'int', \Pressbooks\Admin\Analytics\analytics_ga_mu_site_specific_allowed_sanitize( 1 ) );
	}

	public function test_print_analytic() {

		ob_start();
		\Pressbooks\Analytics\print_analytics();
		$buffer = ob_get_clean();
		$this->assertContains( '<script>', $buffer );
		$this->assertContains( 'Google', $buffer );
		$this->assertContains( 'Analytics', $buffer );
	}

	public function test_print_admin_analytics() {

		ob_start();
		\Pressbooks\Admin\Analytics\print_admin_analytics();
		$buffer = ob_get_clean();
		$this->assertContains( '<script>', $buffer );
		$this->assertContains( 'Google', $buffer );
		$this->assertContains( 'Analytics', $buffer );
	}

}
