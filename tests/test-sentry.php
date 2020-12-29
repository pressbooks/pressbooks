<?php

use Pressbooks\Sentry;

/**
 * @group sentry
 */

class SentryTest extends \WP_UnitTestCase {

	/**
	 * @var Sentry
	 */
	protected $sentry;

	/**
	 * @var Pressbooks\DataCollector\User
	 */
	protected $user;

	/**
	 * Test setup
	 */
	public function setUp() {
		parent::setUp();
		$this->user = $this->factory()->user->create_and_get( [ 'role' => 'contributor' ] );
		$this->sentry = new Sentry();
	}

	/**
	 * Test init Sentry function to test instance returned.
	 */
	public function test_getInstance() {
		putenv( 'SENTRY_DSN=test_mock_dsn' );
		putenv( 'WP_ENV=test' );
		putenv( 'SENTRY_INITIALIZE_PHP=1' );
		putenv( 'SENTRY_INITIALIZE_JAVASCRIPT=1' );
		$sentry = $this->sentry->init();
		$this->assertInstanceOf( '\Pressbooks\Sentry', $sentry );
	}

	/**
	 * Test Sentry user for tracking is the same that current WP user.
	 */
	public function test_getCurrentUser() {
		$sentry_user = $this->sentry->setUserForTracking( $this->user );
		$this->assertEquals( $this->user->user_login, $sentry_user->user_login );
		$this->assertEquals( $this->user->user_email, $sentry_user->user_email );
	}

	/**
	 * Test phpObserver Sentry function. Since Sentry connection is mocked, it should return false.
	 */
	public function test_phpObserver() {
		$this->sentry->setUserForTracking( $this->user );
		$this->assertFalse( $this->sentry->phpObserver() );
	}

	/**
	 * Test javascript observer for Sentry, it should enqueue the sentry.js script
	 */
	public function test_javascriptObserver() {
		$this->sentry->setUserForTracking( $this->user );
		$this->assertTrue( $this->sentry->javascriptObserver() );
		global $wp_scripts;
		$this->assertContains( Sentry::WP_SCRIPT_NAME, $wp_scripts->queue );
	}
}
