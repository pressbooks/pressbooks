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

	public function test_syncSiteName_updates_network_site_name_on_main_site() {
		$network_id = get_current_network_id();
		update_network_option( $network_id, 'site_name', 'Old Network Name' );

		\Pressbooks\Admin\Network\NetworkSettings::syncSiteName( 'Old Blog Name', 'New Blog Name' );

		$this->assertEquals( 'New Blog Name', get_network_option( $network_id, 'site_name' ) );
	}

	public function test_overrideSiteName_returns_main_site_blogname() {
		update_blog_option( get_main_site_id(), 'blogname', 'Main Site Blog Name' );

		$result = \Pressbooks\Admin\Network\NetworkSettings::overrideSiteName( 'Some Other Value' );

		$this->assertEquals( 'Main Site Blog Name', $result );
	}

	public function test_overrideSiteName_falls_back_when_blogname_empty() {
		update_blog_option( get_main_site_id(), 'blogname', '' );

		$result = \Pressbooks\Admin\Network\NetworkSettings::overrideSiteName( 'Fallback Value' );

		$this->assertEquals( 'Fallback Value', $result );
	}

	public function test_hideSiteTitle_outputs_nothing_outside_network_admin() {
		ob_start();
		\Pressbooks\Admin\Network\NetworkSettings::hideSiteTitle();
		$buffer = ob_get_clean();

		$this->assertEmpty( $buffer );
	}

	public function test_hideSiteTitle_outputs_style_in_network_admin() {
		set_current_screen( 'dashboard-network' );

		ob_start();
		\Pressbooks\Admin\Network\NetworkSettings::hideSiteTitle();
		$buffer = ob_get_clean();

		set_current_screen( 'front' );

		$this->assertStringContainsString( 'label[for="site_name"]', $buffer );
	}
}
