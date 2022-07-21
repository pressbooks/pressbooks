<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/analytics/namespace.php' );

class AnalyticsTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @group analytics
	 */
	public function test_network_analytics_settings_init() {
		global $wp_registered_settings;
		\Pressbooks\Admin\Analytics\network_analytics_settings_init();
		$this->assertArrayHasKey( 'ga_mu_uaid', $wp_registered_settings );
		$this->assertArrayHasKey( 'ga_mu_site_specific_allowed', $wp_registered_settings );
	}

	/**
	 * @group analytics
	 */
	public function test_book_analytics_settings_init() {
		global $wp_registered_settings;
		\Pressbooks\Admin\Analytics\book_analytics_settings_init();
		$this->assertArrayHasKey( 'ga_mu_uaid', $wp_registered_settings );
	}

	/**
	 * @group analytics
	 */
	public function test_add_menu() {
		$this->expectOutputRegex( '/<\/p>/' );
		\Pressbooks\Admin\Analytics\analytics_settings_section_callback();
	}

	/**
	 * @group analytics
	 */
	public function test_analytics_book_callback() {
		$args = [];
		$args[0] = 'Hello World!';
		ob_start();
		\Pressbooks\Admin\Analytics\analytics_book_callback( $args );
		$buffer = ob_get_clean();

		$this->assertStringContainsString( 'ga_mu_uaid', $buffer );
		$this->assertStringContainsString( 'Hello World!', $buffer );
	}

	/**
	 * @group analytics
	 */
	public function test_analytics_network_callback() {
		$args = [];
		$args[0] = 'Hello World!';
		ob_start();
		\Pressbooks\Admin\Analytics\analytics_network_callback( $args );
		$buffer = ob_get_clean();

		$this->assertStringContainsString( 'ga_mu_uaid', $buffer );
		$this->assertStringContainsString( 'Hello World!', $buffer );
	}

	/**
	 * @group analytics
	 */
	public function test_analytics_books_allowed_callback() {
		$args = [];
		$args[0] = 'Hello World!';
		ob_start();
		\Pressbooks\Admin\Analytics\analytics_books_allowed_callback( $args );
		$buffer = ob_get_clean();

		$this->assertStringContainsString( 'ga_mu_site_specific_allowed', $buffer );
		$this->assertStringContainsString( 'Hello World!', $buffer );
	}

	/**
	 * @group analytics
	 */
	public function test_display_network_analytics_settings() {
		ob_start();
		\Pressbooks\Admin\Analytics\display_network_analytics_settings( );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '</form>', $buffer );
	}

	/**
	 * @group analytics
	 */
	public function test_display_book_analytics_settings() {
		ob_start();
		\Pressbooks\Admin\Analytics\display_book_analytics_settings( );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '</form>', $buffer );
	}

	/**
	 * @group analytics
	 */
	public function test_print_analytics() {

		switch_to_blog( get_network()->site_id );
		update_site_option( 'ga_mu_uaid', 'TEST1' );
		update_site_option( 'ga_mu_site_specific_allowed', true );
		update_option( 'ga_mu_uaid', 'TEST2' );

		ob_start();
		\Pressbooks\Analytics\print_analytics();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<script>', $buffer );
		$this->assertStringContainsString( 'Google', $buffer );
		$this->assertStringContainsString( 'Analytics', $buffer );
		$this->assertStringContainsString( 'TEST1', $buffer );
		$this->assertStringNotContainsString( 'TEST2', $buffer );

		$this->_book();
		update_option( 'ga_mu_uaid', 'TEST2' );

		ob_start();
		\Pressbooks\Analytics\print_analytics();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'Google', $buffer );
		$this->assertStringContainsString( 'Analytics', $buffer );
		$this->assertStringContainsString( 'TEST1', $buffer );
		$this->assertStringContainsString( 'TEST2', $buffer );

		delete_site_option( 'ga_mu_site_specific_allowed' );

		ob_start();
		\Pressbooks\Admin\Analytics\print_admin_analytics();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'TEST1', $buffer );
		$this->assertStringNotContainsString( 'TEST2', $buffer );
	}

}
