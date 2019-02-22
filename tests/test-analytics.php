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
		$args[0] = 'Hello World!';
		ob_start();
		\Pressbooks\Admin\Analytics\analytics_book_callback( $args );
		$buffer = ob_get_clean();

		$this->assertContains( 'ga_mu_uaid', $buffer );
		$this->assertContains( 'Hello World!', $buffer );
	}

	/**
	 * @group analytics
	 */
	public function test_analytics_network_callback() {
		$args[0] = 'Hello World!';
		ob_start();
		\Pressbooks\Admin\Analytics\analytics_network_callback( $args );
		$buffer = ob_get_clean();

		$this->assertContains( 'ga_mu_uaid', $buffer );
		$this->assertContains( 'Hello World!', $buffer );
	}

	/**
	 * @group analytics
	 */
	public function test_analytics_books_allowed_callback() {
		$args[0] = 'Hello World!';
		ob_start();
		\Pressbooks\Admin\Analytics\analytics_books_allowed_callback( $args );
		$buffer = ob_get_clean();

		$this->assertContains( 'ga_mu_site_specific_allowed', $buffer );
		$this->assertContains( 'Hello World!', $buffer );
	}

	/**
	 * @group analytics
	 */
	public function test_display_network_analytics_settings() {
		ob_start();
		\Pressbooks\Admin\Analytics\display_network_analytics_settings( );
		$buffer = ob_get_clean();
		$this->assertContains( '</form>', $buffer );
	}

	/**
	 * @group analytics
	 */
	public function test_display_book_analytics_settings() {
		ob_start();
		\Pressbooks\Admin\Analytics\display_book_analytics_settings( );
		$buffer = ob_get_clean();
		$this->assertContains( '</form>', $buffer );
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
