<?php

use Pressbooks\Admin\Users\UserBulk;
use Pressbooks\HtmlParser;

class UserBulkTest extends \WP_UnitTestCase {

	/**
	 * @var UserBulk
	 */
	protected $user_bulk;

	/**
	 * @var array
	 */
	protected $post_users = [
		'existinguser@pressbooks.com',
		'existinguser2@pressbooks.com',
		'existinguser@gmail.com',
		'newuser@gmail.com',
		'newuser3@gmail.com',
	];

	/**
	 * Test setup
	 */
	public function setUp() {
		parent::setUp();
		$this->user_bulk = new UserBulk();
	}

	/**
	 * @group userbulk
	 */
	public function test_hooks() {
		global $wp_filter;
		$result = $this->user_bulk->init();
		$this->assertInstanceOf( UserBulk::class, $result );
		$this->user_bulk->hooks( $result );
		$this->assertNotEmpty( $wp_filter );
	}

	/**
	 * @group userbulk
	 */
	public function test_init() {
		$instance = UserBulk::init();
		$this->assertTrue( $instance instanceof UserBulk );
	}

	/**
	 * @group userbulk
	 */
	public function test_addMenu() {
		$this->user_bulk->addMenu( );
		$this->assertTrue( true ); // Did not crash
	}

	/**
	 * @group userbulk
	 */
	public function test_printMenu() {
		ob_start();
		$this->user_bulk->printMenu();
		$html = ob_get_clean();
		$parser = new HtmlParser( true );
		$doc = $parser->loadHTML( $html );
		$users_input = $doc->getElementById( 'users' );
		$user_rol_dropdown = $doc->getElementById( 'adduser-role' );

		$this->assertTrue( $doc instanceof \DOMDocument );
		$this->assertEquals( 1, $doc->getElementsByTagName( 'form' )->length );
		$this->assertInstanceOf( DOMElement::class, $users_input );
		$this->assertInstanceOf( DOMElement::class, $user_rol_dropdown );
		$this->assertEquals( 'users', $users_input->getAttribute( 'name' ) );
		$this->assertEquals( 'role', $user_rol_dropdown->getAttribute( 'name' ) );
	}

	/**
	 * @group userbulk
	 */
	public function test_printMenuException() {
		$_REQUEST['_wpnonce'] = 'fsdflkjdfsiofueriu';
		$_POST['users'] = implode( "\r\n", $this->post_users );
		$_POST['role'] = 'contributor';
		$_POST['submit'] = 'Add users';

		ob_start();
		$this->user_bulk->printMenu();
		$html = ob_get_clean();
		$this->assertNotFalse( strpos( $html, 'class="error notice is-dismissible"' ) );
	}

	/**
	 * @group userbulk
	 */
	public function test_bulkAddUsers() {
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'user_bulk_new' );
		$_POST['users'] = implode( "\r\n", $this->post_users );
		$_POST['role'] = 'contributor';
		$_POST['submit'] = 'Add users';

		$this->factory()->user->create(
			[
				'user_login' => 'existinguser',
				'user_email' => 'existinguser@pressbooks.com',
				'role'       => 'author',
			]
		);

		$this->factory()->user->create(
			[
				'user_login' => 'existinguser2',
				'user_email' => 'existinguser2@pressbooks.com',
				'role'       => 'author',
			]
		);

		$results = $this->user_bulk->bulkAddUsers();

		foreach( $results as $r ) {
			$this->assertTrue( in_array( $r['email'], $this->post_users ) );
			$this->assertTrue( $r['status'] );
		}
	}

	/**
	 * @group userbulk
	 */
	public function test_linkNewUserToBook() {
		$existing_user = $this->factory()->user->create_and_get(
			[
				'user_login' => 'contributor',
				'user_email' => 'contributor@pressbooks.com',
			]
		);
		$new_user_email = 'newuseremail@testdomain.com';
		$wp_error = $this->user_bulk->linkNewUserToBook( $existing_user->user_email, 'editor' );
		$success = $this->user_bulk->linkNewUserToBook( $new_user_email, 'editor' );

		$this->assertTrue( $wp_error instanceof WP_Error ); // cannot link existing users
		$this->assertTrue( $success );
	}

	/**
	 * @group userbulk
	 */
	public function test_generateUserNameFromEmail() {
		$invalid_email = 'invalid@email@.com';
		$invalid_user_name = $this->user_bulk->generateUserNameFromEmail( $invalid_email );
		$valid_email = 'validemail@pressbooks.com';
		$valid_user_data = $this->user_bulk->generateUserNameFromEmail( $valid_email );

		$this->assertFalse( $invalid_user_name );
		$this->assertEquals( 'validemail', $valid_user_data['user_name'] );
		$this->assertEquals( $valid_email, $valid_user_data['user_email'] );
		$this->assertFalse( $valid_user_data['errors']->has_errors() );

		// Persist user in order to test user login deduplication
		$this->factory()->user->create(
			[
				'user_login' => 'validemail',
				'user_email' => 'validemail@pressbooks.com',
			]
		);
		$valid_email_existing_username = 'validemail@gmail.com';
		$valid_user_data_dedup = $this->user_bulk->generateUserNameFromEmail( $valid_email_existing_username );

		$this->assertEquals( 'validemail1', $valid_user_data_dedup['user_name'] );
		$this->assertEquals( $valid_email_existing_username, $valid_user_data_dedup['user_email'] );
		$this->assertFalse( $valid_user_data_dedup['errors']->has_errors() );
	}

	/**
	 * @group userbulk
	 */
	public function test_sanitizeUser() {
		$this->assertEquals( 'test', $this->user_bulk->sanitizeUser( 'test' ) );
		$this->assertEquals( 'test', $this->user_bulk->sanitizeUser( '(:test:)' ) );
		$this->assertEquals( 'tst1', $this->user_bulk->sanitizeUser( 'tst' ) );
		$this->assertEquals( 'tst1', $this->user_bulk->sanitizeUser( '(:tst:)' ) );
		$this->assertEquals( 'yo11', $this->user_bulk->sanitizeUser( 'yo' ) );
		$this->assertEquals( 'yo11', $this->user_bulk->sanitizeUser( '(:yo:)' ) );
		$this->assertEquals( '1111a', $this->user_bulk->sanitizeUser( '1111' ) );
		$this->assertEquals( '1a11', $this->user_bulk->sanitizeUser( '1' ) );
	}

	/**
	 * @group userbulk
	 */
	public function test_getBulkResultHtml() {
		$success = [];
		$errors = [];

		foreach( $this->post_users as $email ) {
			if ( rand( 0, 1 ) ) {
				array_push( $success, [
					'email'     => $email,
					'status'    => true
				] );
			} else {
				array_push( $errors, [
					'email'     => $email,
					'status'    => new WP_Error( 1, 'WP error message' )
				] );
			}
		}

		$html = $this->user_bulk->getBulkResultHtml( array_merge( $success, $errors ) );
		$parser = new HtmlParser( true );
		$doc = $parser->loadHTML( $html );

		if ( ! empty( $success ) ) {
			$success_message_str = $doc->getElementById( 'bulk-success' )->textContent;
			foreach( $success as $result ) {
				$this->assertTrue( false !== strpos( $success_message_str, $result['email'] ) );
			}
		}

		if ( ! empty( $errors ) ) {
			$error_message_str = $doc->getElementById( 'bulk-errors' )->textContent;
			foreach( $errors as $result ) {
				$this->assertTrue( false !== strpos( $error_message_str, $result['email'] ) );
			}
		}
	}
}
