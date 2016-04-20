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

	/**
	 * @covers \PressBooks\Registration\add_password_field
	 */
	public function test_add_password_field() {

		// Test for field label in output
		
		$e = new WP_Error();
		$this->expectOutputRegex( '/<\/label>/' );
		\PressBooks\Registration\add_password_field( $e );
	}

	/**
	 * @covers \PressBooks\Registration\validate_passwords
	 */
	public function test_validate_passwords() {

		global $_POST;

		// Test for correct stage

		$content = array( 'errors' => new WP_Error() );
		$_POST['stage'] = '';

		\PressBooks\Registration\validate_passwords( $content );

		$this->assertEquals( '', $content['errors']->get_error_message( 'password_1' ) );

		// Test for empty password field

		$content = array( 'errors' => new WP_Error() );
		$_POST['stage'] = 'validate-user-signup'; // Validation stage
		$_POST['password_1'] = ''; // Empty password
		$_POST['password_2'] = 'barrel aquiline abolish belabour'; // Legitimate password
		
		\PressBooks\Registration\validate_passwords( $content );
		
		$this->assertEquals( 'You have to enter a password.', $content['errors']->get_error_message( 'password_1' ) );

		// Test for password mismatch

		$content = array( 'errors' => new WP_Error() );
		$_POST['stage'] = 'validate-user-signup'; // Validation stage
		$_POST['password_1'] = 'colloquy glint tendril choler'; // Legitimate password
		$_POST['password_2'] = 'barrel aquiline abolish belabour'; // Legitimate password that doesn't match
		
		\PressBooks\Registration\validate_passwords( $content );
		
		$this->assertEquals( 'Passwords do not match.', $content['errors']->get_error_message( 'password_1' ) );
	}

	/**
	 * @covers \PressBooks\Registration\validate_passwords
	 */
	public function test_add_temporary_password() {

		global $_POST;
		
		// Test for base64-encoded password key in $meta array, matching input password
		
		$_POST['password_1'] = 'colloquy glint tendril choler';
		
		$meta = \PressBooks\Registration\add_temporary_password( array() );
		$this->assertEquals( 'colloquy glint tendril choler', base64_decode( $meta['password'] ) );
		
		// Test for absence of password key in $meta array when no password is provided
		
		unset( $_POST['password_1'] );
		
		$meta = \PressBooks\Registration\add_temporary_password( array() );
		$this->assertArrayNotHasKey( 'password', $meta );
	}
	
	/**
	 * @covers \PressBooks\Registration\add_hidden_password_field
	 */
	public function test_add_hidden_password_field() {

		global $_POST;
		
		// Test for password field in output when password is supplied
		
		$_POST['password_1'] = 'colloquy glint tendril choler';
		
		$this->expectOutputRegex( '/(<input type="hidden" name="password_1.*)(" value=").*(").*(\/>)/' );
		\PressBooks\Registration\add_hidden_password_field( array() );

	}

//	/**
//	 * @covers \PressBooks\Registration\override_password_generation
//	 */
//	public function test_override_password_generation() {
//		$this->markTestIncomplete();
//	}

}
