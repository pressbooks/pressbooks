<?php

class L10nTest extends \WP_UnitTestCase {

	/**
	 * @covers \PressBooks\L10n\supported_languages
	 */
	public function test_supported_languages() {

		$supported_languages = \PressBooks\L10n\supported_languages();
		$this->assertTrue( is_array( $supported_languages ) );
	
	}

	/**
	 * @covers \PressBooks\L10n\wplang_codes
	 */
	public function test_wplang_codes() {

		$wplang_codes = \PressBooks\L10n\wplang_codes();
		$this->assertTrue( is_array( $wplang_codes ) );
	
	}

	/**
	 * @covers \PressBooks\L10n\get_dashboard_languages
	 */
	public function test_get_dashboard_languages() {

		$get_dashboard_languages = \PressBooks\L10n\get_dashboard_languages();
		$this->assertTrue( is_array( $get_dashboard_languages ) );
	
	}

}