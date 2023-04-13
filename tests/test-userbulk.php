<?php

use Pressbooks\Admin\Users\UserBulk;
use Pressbooks\HtmlParser;

/**
 * @group users-bulk
 */
class UserBulkTest extends \WP_UnitTestCase {

	use utilsTrait;

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
	public function set_up() {
		parent::set_up();
		$this->user_bulk = new UserBulk();
	}

	public function test_hooks() {
		global $wp_filter;
		$result = $this->user_bulk->init();
		$this->assertInstanceOf( UserBulk::class, $result );
		$this->user_bulk->hooks( $result );
		$this->assertNotEmpty( $wp_filter );
		$this->assertEquals( 10, has_action( 'network_admin_menu', [ $result, 'addMenu' ] ) );
		$this->assertFalse( has_action( 'admin_menu', [ $result, 'addMenu' ] ) );

		$this->_book();
		$this->user_bulk->hooks( $result );
		$this->assertEquals( 10, has_action( 'admin_menu', [ $result, 'addMenu' ] ) );
	}

	public function test_init() {
		$instance = UserBulk::init();
		$this->assertInstanceOf( UserBulk::class, $instance );
	}

	public function test_addMenu() {
		$this->user_bulk->addMenu( );
		$this->assertTrue( true ); // Did not crash
	}

	/**
	 * @test
	 */
	public function print_menu_for_book_admins(): void {
		$this->_book();
		$doc = $this->printMenu();

		$users_input = $doc->getElementById( 'users' );
		$user_rol_dropdown = $doc->getElementById( 'adduser-role' );

		$this->assertInstanceOf( \DOMDocument::class, $doc );
		$this->assertEquals( 1, $doc->getElementsByTagName( 'form' )->length );
		$this->assertInstanceOf( DOMElement::class, $users_input );
		$this->assertInstanceOf( DOMElement::class, $user_rol_dropdown );
		$this->assertEquals( 'users', $users_input->getAttribute( 'name' ) );
		$this->assertEquals( 'role', $user_rol_dropdown->getAttribute( 'name' ) );
	}

	/**
	 * @test
	 */
	public function print_menu_for_network_admins(): void {
		$doc = $this->printMenu();

		$users_input = $doc->getElementById( 'users' );

		$this->assertInstanceOf( \DOMDocument::class, $doc );
		$this->assertEquals( 1, $doc->getElementsByTagName( 'form' )->length );
		$this->assertInstanceOf( DOMElement::class, $users_input );
		$this->assertNull( $doc->getElementById( 'adduser-role' ) );
		$this->assertEquals( 'users', $users_input->getAttribute( 'name' ) );
	}

	private function printMenu(): DOMDocument {
		ob_start();
		$this->user_bulk->printMenu();
		$html = ob_get_clean();
		$parser = new HtmlParser( true );
		return $parser->loadHTML( $html );
	}

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

	public function test_bulkAddUsers() {
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'user_bulk_new' );
		$_POST['users'] = implode( "\r\n", $this->post_users );
		$_POST['role'] = 'contributor';
		$_POST['submit'] = 'Add users';
		$assertions = [
			$this->user_bulk::USER_STATUS_INVITED,
			$this->user_bulk::USER_STATUS_INVITED,
			$this->user_bulk::USER_STATUS_NEW,
			$this->user_bulk::USER_STATUS_NEW,
			$this->user_bulk::USER_STATUS_NEW,
		];

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
		$count = count( $results );

		for ( $i = 0; $i < $count; $i++ ) {
			$this->assertTrue( in_array( $results[$i]['email'], $this->post_users ) );
			$this->assertEquals( $results[$i]['status'], $assertions[$i] );
		}
	}

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

		$this->assertInstanceOf( WP_Error::class, $wp_error ); // cannot link existing users
		$this->assertEquals( $success, $this->user_bulk::USER_STATUS_NEW );
	}

	public function test_generateUserNameFromEmail() {
		$invalid_email = 'invalid@email@.com';
		$invalid_user_name = $this->user_bulk->generateUserNameFromEmail( $invalid_email );
		$valid_email = 'validemail@pressbooks.com';
		$valid_user_data = $this->user_bulk->generateUserNameFromEmail( $valid_email );

		$this->assertInstanceOf( WP_Error::class, $invalid_user_name['errors'] );
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

	public function test_getBulkResultHtml() {
		$success = [];
		$errors = [];

		foreach( $this->post_users as $email ) {
			if ( rand( 0, 1 ) ) {
				$success[] = [
					'email' => $email,
					'status' => true
				];
			} else {
				$errors[] = [
					'email' => $email,
					'status' => new WP_Error(1, 'WP error message')
				];
			}
		}

		$html = $this->user_bulk->getBulkResultHtml( array_merge( $success, $errors ) );
		$parser = new HtmlParser( true );
		$doc = $parser->loadHTML( $html );

		if ( ! empty( $success ) ) {
			$success_message_str = $doc->getElementById( 'bulk-success' )->textContent;
			foreach( $success as $result ) {
				$this->assertTrue( str_contains( $success_message_str ?? '', $result['email'] ) );
			}
		}

		if ( ! empty( $errors ) ) {
			$error_message_str = $doc->getElementById( 'bulk-errors' )->textContent;
			foreach( $errors as $result ) {
				$this->assertTrue( str_contains( $error_message_str, $result['email'] ) );
			}
		}
	}

	public function test_getBulkMessageSubtitle() {
		$subtitle_error =  'The following user(s) could not be added.';
		$subtitle_success_invite_book = 'User(s) successfully added to this book.';
		$subtitle_success_invite_network = 'User(s) successfully added to the network.';
		$subtitle_success = 'An invitation email has been sent to the user(s) below. A confirmation link must be clicked before their account is created.';

		$this->assertEquals( $this->user_bulk->getBulkMessageSubtitle( $this->user_bulk::USER_STATUS_ERROR ), $subtitle_error );
		$this->assertEquals( $this->user_bulk->getBulkMessageSubtitle( $this->user_bulk::USER_STATUS_INVITED ), $subtitle_success_invite_network );
		$this->assertEquals( $this->user_bulk->getBulkMessageSubtitle( $this->user_bulk::USER_STATUS_NEW ), $subtitle_success );

		$this->_book();

		$this->assertEquals( $this->user_bulk->getBulkMessageSubtitle( $this->user_bulk::USER_STATUS_INVITED ), $subtitle_success_invite_book );
	}
}
