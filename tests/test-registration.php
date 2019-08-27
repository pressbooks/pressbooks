<?php

class Registration extends \WP_UnitTestCase {

	/**
	 * @group registration
	 */
	function setUp() {
		parent::setUp();
		global $pagenow;
		$pagenow = 'wp-signup.php';
		add_filter( 'gettext', '\Pressbooks\Registration\custom_signup_text', 20, 3 );
	}

	/**
	 * @group registration
	 */
	function tearDown() {
		parent::tearDown();
		remove_filter( 'gettext', '\Pressbooks\Registration\custom_signup_text' );
	}


	/**
	 * @group registration
	 */
	public function test_custom_signup_text() {
		$output = __( 'Create Site', 'pressbooks' );
		$this->assertEquals( 'Create Book', $output );
	}

	/**
	 * @group registration
	 */
	public function test_add_password_field() {

		// Test for field label in output

		$e = new WP_Error();
		$this->expectOutputRegex( '/<\/label>/' );
		\Pressbooks\Registration\add_password_field( $e );
	}

	/**
	 * @group registration
	 */
	public function test_validate_passwords() {

		global $_POST;

		// Test for correct stage

		$content = [ 'errors' => new WP_Error() ];
		$_POST['stage'] = '';

		\Pressbooks\Registration\validate_passwords( $content );

		$this->assertEquals( '', $content['errors']->get_error_message( 'password_1' ) );

		// Test for empty password field

		$content = [ 'errors' => new WP_Error() ];
		$_POST['stage'] = 'validate-user-signup'; // Validation stage
		$_POST['password_1'] = ''; // Empty password
		$_POST['password_2'] = 'barrel aquiline abolish belabour'; // Legitimate password

		\Pressbooks\Registration\validate_passwords( $content );

		$this->assertEquals( 'You have to enter a password.', $content['errors']->get_error_message( 'password_1' ) );

		// Test for password mismatch

		$content = [ 'errors' => new WP_Error() ];
		$_POST['stage'] = 'validate-user-signup'; // Validation stage
		$_POST['password_1'] = 'colloquy glint tendril choler'; // Legitimate password
		$_POST['password_2'] = 'barrel aquiline abolish belabour'; // Legitimate password that doesn't match

		\Pressbooks\Registration\validate_passwords( $content );

		$this->assertEquals( 'Passwords do not match.', $content['errors']->get_error_message( 'password_1' ) );
	}

	/**
	 * @group registration
	 */
	public function test_add_temporary_password() {

		global $_POST;

		// Test for temporarily encrypted password key in $meta array, matching input password

		$_POST['password_1'] = 'colloquy glint tendril choler';

		$meta = \Pressbooks\Registration\add_temporary_password( [] );
		$this->assertEquals( 'colloquy glint tendril choler', \Pressbooks\Registration\unpack_from_storage( $meta['password'] ) );

		// Test for absence of password key in $meta array when no password is provided

		unset( $_POST['password_1'] );

		$meta = \Pressbooks\Registration\add_temporary_password( [] );
		$this->assertArrayNotHasKey( 'password', $meta );
	}

	/**
	 * @group registration
	 */
	public function test_add_hidden_password_field() {

		global $_POST;

		// Test for password field in output when password is supplied

		$_POST['password_1'] = 'colloquy glint tendril choler';

		$this->expectOutputRegex( '/(<input type="hidden" name="password_1.*)(" value=").*(").*(\/>)/' );
		\Pressbooks\Registration\add_hidden_password_field();

	}

	/**
	 * @group registration
	 */
	public function test_hide_plaintext_password() {
		ob_start();
		\Pressbooks\Registration\hide_plaintext_password();
		$buffer = ob_get_clean();
		$this->assertContains( '#signup-welcome p:nth-child(2)', $buffer );
	}

	/**
	 * @group registration
	 */
	public function test_storage() {
		$test = 'This is a test';
		$packed = \Pressbooks\Registration\put_in_storage( $test );
		$this->assertNotEquals( $test, $packed );
		$unpacked = \Pressbooks\Registration\unpack_from_storage( $packed );
		$this->assertEquals( $test, $unpacked );

		$junk = \Pressbooks\Registration\unpack_from_storage( 'junk' );
		$this->assertEmpty( $junk );
	}

}
