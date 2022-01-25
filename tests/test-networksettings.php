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

	public function setUp() {
		parent::setUp();
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
		$option = \Pressbooks\Admin\Network\NetworkSettings::DEFAULT_THEME_OPTION;
		$this->assertStringContainsString( "<select id=\"$option\" name=\"$option\"", $buffer );
	}

	public function test_saveNetworkSettings() {
		$this->_book();
		$option = \Pressbooks\Admin\Network\NetworkSettings::DEFAULT_THEME_OPTION;
		update_site_option( $option, 'pressbooks-book' );
		$_POST[ $option ] = 'invalid-theme';
		$this->assertFalse( $this->networkSettings->saveNetworkSettings() );
	}

}