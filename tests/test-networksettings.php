<?php

/**
 * @group networksettings
 */
class NetworkSettingsTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @var \Pressbooks\Admin\Network\NetworkSettings
	 */
	private $networkSettings;

	public function set_up() {
		parent::set_up();
		$this->networkSettings = new \Pressbooks\Admin\Network\NetworkSettings();
	}

	public function test_hook() {
		$this->networkSettings->hooks( $this->networkSettings );
		$this->assertEquals( 10, has_filter( 'wpmu_options', [ $this->networkSettings, 'renderCustomOptions' ] ) );
		$this->assertEquals( 10, has_filter( 'update_wpmu_options', [ $this->networkSettings, 'saveNetworkSettings' ] ) );
	}

	public function test_renderCustomOptions() {
		ob_start();
		$this->networkSettings->renderCustomOptions();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<h3>' . __( 'Theme Settings', 'pressbooks' ) . '</h3>', $buffer );
		$theme_option = \Pressbooks\Admin\Network\NetworkSettings::DEFAULT_THEME_OPTION;
		$this->assertStringContainsString( "<select id=\"$theme_option\" name=\"$theme_option\"", $buffer );
		$cta_option = \Pressbooks\Admin\Network\NetworkSettings::DISPLAY_CTA_BANNER_OPTION;
		$this->assertStringContainsString( "<input type=\"checkbox\" id=\"$cta_option\"", $buffer );
	}

	public function test_saveDefaultThemeNetworkSettings() {
		$this->_book();
		$option = \Pressbooks\Admin\Network\NetworkSettings::DEFAULT_THEME_OPTION;
		update_site_option( $option, 'pressbooks-book' );
		$_POST[ $option ] = 'invalid-theme';
		$this->networkSettings->saveNetworkSettings();
		$this->assertEquals( 'pressbooks-book', get_site_option( $option ) );
	}

	/**
	 * @test
	 */
	public function it_saves_cta_banner_displaying_option(): void {
		$this->_book();
		$option = \Pressbooks\Admin\Network\NetworkSettings::DISPLAY_CTA_BANNER_OPTION;

		$this->networkSettings->saveNetworkSettings();
		$this->assertEquals( '0', get_site_option( $option ) );

		$_POST[ $option ] = '1';
		$this->networkSettings->saveNetworkSettings();
		$this->assertEquals( '1', get_site_option( $option ) );
	}

	public function test_getDefaultTheme() {
		$this->assertEquals( 'pressbooks-book', \Pressbooks\Admin\Network\NetworkSettings::getDefaultTheme() );
	}
}
