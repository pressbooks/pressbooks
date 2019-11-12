<?php

use Pressbooks\Admin\Users\UserBulk;
use Pressbooks\HtmlParser;

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
	public function setUp() {
		parent::setUp();
		$this->user_bulk = new UserBulk();
	}

	/**
	 * @group userbulk
	 */
	public function test_hooks() {
		$this->user_bulk->hooks( $this->user_bulk );
		$this->assertEquals( true, has_action( 'admin_menu', [ $this->user_bulk, 'addMenu' ] ) );
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
		$this->assertEquals( 1, $doc->getElementsByTagName( 'form' )->count() );
		$this->assertInstanceOf( DOMElement::class, $users_input );
		$this->assertInstanceOf( DOMElement::class, $user_rol_dropdown );
		$this->assertEquals( 'users', $users_input->getAttribute( 'name' ) );
		$this->assertEquals( 'role', $user_rol_dropdown->getAttribute( 'name' ) );
	}

	/**
	 * @group userbulk
	 */
	public function test_bulkAddUsers() {
		$_REQUEST['_wpnonce'] = wp_create_nonce( 'user_bulk_new' );
		$_POST['users'] = implode( "\r\n", $this->post_users );
		$_POST['role'] = 'contributor';
		$_POST['submit'] = 'Add users';

		$this->factory()->user->create_and_get(
			[
				'user_login' => 'existinguser',
				'user_email' => 'existinguser@pressbooks.com',
				'role'       => 'author',
			]
		);

		$this->factory()->user->create_and_get(
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
}
