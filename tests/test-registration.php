<?php

class Registration extends \WP_UnitTestCase {

	function setUp() {
		parent::setUp();
		global $pagenow;
		$pagenow = 'wp-signup.php';
		add_filter( 'gettext', '\PressBooks\Registration\custom_signup_text', 20, 3 );
	}

	function tearDown() {
		parent::tearDown();
		remove_filter( 'gettext', '\PressBooks\Registration\custom_signup_text' );
	}

	/**
	 * @covers \PressBooks\Registration\custom_signup_text
	 */
	public function test_custom_signup_text() {
		$output = __( 'Create Site', 'pressbooks' );
		$this->assertEquals( 'Create Book', $output );
	}

}