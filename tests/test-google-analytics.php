<?php

/**
 * @group google-analytics
 */
class GoogleAnalyticsTest extends \WP_UnitTestCase {
	use utilsTrait;

	private \Pressbooks\GoogleAnalytics $google_analytics;

	public function setUp(): void
	{
		parent::setUp();
		$this->google_analytics = new \Pressbooks\GoogleAnalytics();
	}

	/**
	 * @test
	 */
	public function network_settings_are_registered(): void {
		global $wp_registered_settings;
		$this->google_analytics->networkAnalyticsSettingsInit();
		$this->assertArrayHasKey( 'ga_mu_uaid', $wp_registered_settings );
		$this->assertArrayHasKey( 'ga_4_mu_uaid', $wp_registered_settings );
		$this->assertArrayHasKey( \Pressbooks\GoogleAnalytics::$is_allowed_option, $wp_registered_settings );
	}

	/**
	 * @test
	 */
	public function book_settings_are_registered(): void {
		global $wp_registered_settings;
		$this->google_analytics->bookAnalyticsSettingsInit();
		$this->assertArrayHasKey( 'ga_mu_uaid', $wp_registered_settings );
		$this->assertArrayHasKey( 'ga_4_mu_uaid', $wp_registered_settings );
	}

	/**
	 * @test
	 */
	public function menu_is_added(): void {
		$this->expectOutputRegex( '/<\/p>/' );
		$this->google_analytics->analyticsSettingsSectionCallback();
	}

	/**
	 * @test
	 */
	public function google_analytics_book_input_renders(): void {
		$args = [
			'legend' => 'Hello World!',
			'version' => 3,
			'for_book' => true,
		];
		ob_start();
		$this->google_analytics->analyticsInputCallback( $args );
		$buffer = ob_get_clean();

		$this->assertStringContainsString( 'ga_3', $buffer );
		$this->assertStringContainsString( 'Hello World!', $buffer );
	}

	/**
	 * @test
	 */
	public function google_analytics_network_input_renders(): void {
		$args = [
			'legend' => 'Hello World!',
			'version' => 4,
			'for_book' => false,
		];
		ob_start();
		$this->google_analytics->analyticsInputCallback( $args );
		$buffer = ob_get_clean();

		$this->assertStringContainsString( 'ga_4', $buffer );
		$this->assertStringContainsString( 'Hello World!', $buffer );
	}

	/**
	 * @test
	 */
	public function enable_checkbox_for_books_is_displayed(): void {
		$args[0] = 'Hello World!';
		ob_start();
		$this->google_analytics->analyticsBooksAllowedCallback( $args );
		$buffer = ob_get_clean();

		$this->assertStringContainsString( \Pressbooks\GoogleAnalytics::$is_allowed_option, $buffer );
		$this->assertStringContainsString( 'Hello World!', $buffer );
	}

	/**
	 * @test
	 */
	public function display_network_analytics_settings(): void {
		ob_start();
		$this->google_analytics->displayNetworkAnalyticsSettings();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '</form>', $buffer );
	}

	/**
	 * @test
	 */
	public function display_book_analytics_settings(): void {
		ob_start();
		$this->google_analytics->displayBookAnalyticsSettings();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '</form>', $buffer );
	}

	/**
	 * @test
	 */
	public function ga_scripts_are_printed(): void {
		switch_to_blog( get_network()->site_id );
		$_REQUEST['ga_3'] = 'TEST-v3';
		$_REQUEST['ga_4'] = 'TEST-v4';
		$this->google_analytics->saveNetworkIDOption( 'ga_3', 3 );
		$this->google_analytics->saveNetworkIDOption( 'ga_4', 4 );

		update_site_option( \Pressbooks\GoogleAnalytics::$is_allowed_option, true );

		update_option( 'ga_mu_uaid', 'TEST2-v3' );

		ob_start();
		$this->google_analytics->printScripts();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<script>', $buffer );
		$this->assertStringContainsString( 'Google', $buffer );
		$this->assertStringContainsString( 'Analytics', $buffer );
		$this->assertStringContainsString( 'TEST-v3', $buffer );
		$this->assertStringContainsString( 'TEST-v4', $buffer );
		$this->assertStringNotContainsString( 'TEST2-v3', $buffer );

		$this->_book();

		update_option( 'ga_4_mu_uaid', 'TEST2-v3' );

		ob_start();
		$this->google_analytics->printScripts();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'Google', $buffer );
		$this->assertStringContainsString( 'Analytics', $buffer );
		$this->assertStringContainsString( 'TEST-v4', $buffer );
		$this->assertStringContainsString( 'TEST2-v3', $buffer );

		delete_site_option( \Pressbooks\GoogleAnalytics::$is_allowed_option );

		ob_start();
		$this->google_analytics->printScripts();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'TEST-v3', $buffer );
		$this->assertStringContainsString( 'https://www.googletagmanager.com/gtag/js?id=TEST-v4', $buffer );
		$this->assertStringNotContainsString( 'TEST2-v3', $buffer );
	}
}
