<?php

require_once './includes/admin/pb-analytics.php';

class AnalyticsTest extends \WP_UnitTestCase {


	/**
	 * @covers \PressBooks\Admin\Analytics\analytics_settings_section_callback
	 */
	public function test_add_menu() {

		$this->expectOutputRegex( '/<\/p>/' );
		\PressBooks\Admin\Analytics\analytics_settings_section_callback();
	}


	/**
	 * @covers \PressBooks\Admin\Analytics\analytics_ga_mu_uaid_sanitize
	 */
	public function test_analytics_ga_mu_uaid_sanitize() {

		$this->assertInternalType( 'string', \PressBooks\Admin\Analytics\analytics_ga_mu_uaid_sanitize( 'UA-123456-7890' ) );
	}


	/**
	 * @covers \PressBooks\Admin\Analytics\analytics_ga_mu_site_specific_allowed_sanitize
	 */
	public function test_analytics_ga_mu_site_specific_allowed_sanitize() {

		$this->assertInternalType( 'int', \PressBooks\Admin\Analytics\analytics_ga_mu_site_specific_allowed_sanitize( 1 ) );
	}
	

}
