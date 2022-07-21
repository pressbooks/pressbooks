<?php

class Registration extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @group registration
	 */
	function set_up() {
		parent::set_up();
		global $pagenow;
		$pagenow = 'wp-signup.php';
		add_filter( 'gettext', '\Pressbooks\Registration\custom_signup_text', 20, 3 );
	}

	/**
	 * @group registration
	 */
	function tear_down() {
		parent::tear_down();
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
	public function test_storage() {
		$test = 'This is a test';
		$packed = \Pressbooks\Registration\put_in_storage( $test );
		$this->assertNotEquals( $test, $packed );
		$unpacked = \Pressbooks\Registration\unpack_from_storage( $packed );
		$this->assertEquals( $test, $unpacked );

		$junk = \Pressbooks\Registration\unpack_from_storage( 'junk' );
		$this->assertEmpty( $junk );
	}

	public function test_check_for_strong_password() {
		$errors = \Pressbooks\Registration\check_for_strong_password( 'a' );
		$this->assertTrue( is_string( $errors ) );
		$this->assertStringContainsString( 'at least 12 characters', $errors );
		$this->assertStringContainsString( 'at least one upper case letter', $errors );
		$this->assertStringNotContainsString( 'at least one lower case letter', $errors );
		$this->assertStringContainsString( 'at least one number', $errors );

		$errors = \Pressbooks\Registration\check_for_strong_password( 'A' );
		$this->assertStringContainsString( 'at least 12 characters', $errors );
		$this->assertStringNotContainsString( 'at least one upper case letter', $errors );
		$this->assertStringContainsString( 'at least one lower case letter', $errors );
		$this->assertStringContainsString( 'at least one number', $errors );

		$errors = \Pressbooks\Registration\check_for_strong_password( 'aaaaAAAAaaaa' );
		$this->assertStringNotContainsString( 'at least 12 characters', $errors );
		$this->assertStringNotContainsString( 'at least one upper case letter', $errors );
		$this->assertStringNotContainsString( 'at least one lower case letter', $errors );
		$this->assertStringContainsString( 'at least one number', $errors );

		$errors = \Pressbooks\Registration\check_for_strong_password( 'aaa1AAAAaaaa' );
		$this->assertStringNotContainsString( 'at least 12 characters', $errors );
		$this->assertStringNotContainsString( 'at least one upper case letter', $errors );
		$this->assertStringNotContainsString( 'at least one lower case letter', $errors );
		$this->assertStringNotContainsString( 'at least one number', $errors );

		$errors = \Pressbooks\Registration\check_for_strong_password( 'aaa1AAAAaaaa' );
		$this->assertTrue( is_string( $errors ) );
		$this->assertEmpty( $errors );
	}

	/**
	 * @group invitation
	 */
	public function test_invitation_sent_to_existing_user() {
		$this->_book();

		$role = [ 'name' => 'author' ];
		$key = wp_generate_password( 20, false );
		$user = get_userdata( $this->factory()->user->create() );

		$meta_key = 'new_user_' . $key;

		$this->assertEmpty( get_option( $meta_key ) );
		$this->assertEmpty( get_user_meta( $user->ID, $meta_key, true ) );

		add_option(
			$meta_key,
			[
				'user_id' => $user->ID,
				'email' => $user->user_email,
				'role' => $role['name'],
			]
		);

		do_action( 'invite_user', $user->ID, $role, $key );

		$invitation = get_user_meta( $user->ID, $meta_key, true );

		$this->assertNotEmpty( get_option( $meta_key ) );
		$this->assertNotEmpty( $invitation );
		$this->assertEquals( $key, $invitation['key'] );
		$this->assertEquals( get_current_blog_id(), $invitation['blog_id'] );
		$this->assertEquals( $role['name'], $invitation['role'] );
	}

	/**
	 * @group invitation
	 */
	public function test_display_invitation() {
		$this->_book();

		$role = [ 'name' => 'author' ];
		$key = wp_generate_password( 20, false );
		$user = get_userdata( $this->factory()->user->create() );

		$meta_key = 'new_user_' . $key;

		do_action( 'invite_user', $user->ID, $role, $key );

		$this->assertNotEmpty( get_user_meta( $user->ID, $meta_key, true ) );

		wp_set_current_user( $user->ID );

		ob_start();
		\Pressbooks\Admin\Dashboard\pending_invitations_callback();
		$output = ob_get_clean();

		$this->assertStringContainsString( get_site_meta( get_current_blog_id(), 'pb_title', true ), $output );
		$this->assertStringContainsString( "<a class='button button-primary' href='" . home_url( '/newbloguser/' . $key ) . "'>Accept</a>", $output );
	}

	/**
	 * @group invitation
	 */
	public function test_display_invitation_does_not_show_to_the_wrong_user() {
		$this->_book();

		$role = [ 'name' => 'author' ];
		$key = wp_generate_password( 20, false );
		$user = get_userdata( $this->factory()->user->create() );

		$meta_key = 'new_user_' . $key;

		do_action( 'invite_user', $user->ID, $role, $key );

		$this->assertNotEmpty( get_user_meta( $user->ID, $meta_key, true ) );

		$user_2 = get_userdata( $this->factory()->user->create() );
		wp_set_current_user( $user_2->ID );

		ob_start();
		\Pressbooks\Admin\Dashboard\pending_invitations_callback();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * @group invitation
	 */
	public function test_nothing_is_displayed_when_user_has_no_invitation() {
		$this->_book();

		$key = wp_generate_password( 20, false );
		$user = get_userdata( $this->factory()->user->create() );

		$this->assertEmpty( get_user_meta( $user->ID, 'new_user_' . $key, true ) );

		wp_set_current_user( $user->ID );

		ob_start();
		\Pressbooks\Admin\Dashboard\pending_invitations_callback();
		$output = ob_get_clean();

		$this->assertEmpty( $output );
	}

	/**
	 * @group invitation
	 */
	public function test_accept_invitation() {
		$this->_book();

		$role = [ 'name' => 'author' ];
		$key = wp_generate_password( 20, false );
		$user = get_userdata( $this->factory()->user->create() );

		$meta_key = 'new_user_' . $key;

		add_option(
			$meta_key,
			[
				'user_id' => $user->ID,
				'email' => $user->user_email,
				'role' => $role['name'],
			]
		);

		do_action( 'invite_user', $user->ID, $role, $key );

		$this->assertNotEmpty( get_user_meta( $user->ID, $meta_key, true ) );

		$_SERVER['REQUEST_URI'] = '/newbloguser/' . $key;
		add_existing_user_to_blog( get_option( $meta_key ) );

		$this->assertEmpty( get_user_meta( $user->ID, $meta_key, true ) );
	}

	/**
	 * @group invitation
	 */
	public function test_clean_invitation_data_does_not_delete_data_if_activation_fails() {
		 $this->_book();

		$role = [ 'name' => 'author' ];
		$key = wp_generate_password( 20, false );
		$user = get_userdata( $this->factory()->user->create() );

		$meta_key = 'new_user_' . $key;

		add_option(
			$meta_key,
			[
				'user_id' => $user->ID,
				'email' => $user->user_email,
				'role' => $role['name'],
			]
		);

		do_action( 'invite_user', $user->ID, $role, $key );

		$this->assertNotEmpty( get_user_meta( $user->ID, $meta_key, true ) );

		$_SERVER['REQUEST_URI'] = '/newbloguser/' . $key;
		\Pressbooks\Registration\clean_invitation_data( $user->ID, false );

		$this->assertNotEmpty( get_user_meta( $user->ID, $meta_key, true ) );
	}
}
