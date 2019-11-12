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
		ob_start(); // begin collecting output
		$this->user_bulk->printMenu();
		$html = ob_get_clean();

		$parser = new HtmlParser( true );
		$doc = $parser->loadHTML( $html );
		$form = $doc->getElementsByTagName( 'form' )[0];

		$this->assertTrue( $doc instanceof \DOMDocument );
	}
}
