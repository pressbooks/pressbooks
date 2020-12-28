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
	 * Test setup
	 */
	public function setUp() {
		parent::setUp();
		$this->sentry = new Sentry();
	}

	/**
	 * Test init Sentry function to test instance returned.
	 */
	public function test_getInstance() {
		putenv( 'SENTRY_DSN=test_mock_dsn' );
		$sentry = $this->sentry->init();
		$this->assertInstanceOf( '\Pressbooks\Sentry', $sentry );
	}

	/**
	 * Test phpObserver Sentry function. Since Sentry connection is mocked, it should return false.
	 */
	public function test_phpObserver() {
		Sentry::getCurrentUserForTracking();
		$this->assertTrue( $this->sentry->phpObserver() );
	}

	/**
	 * Test javascript observer for Sentry, it should enqueue the sentry.js script
	 */
	public function test_javascriptObserver() {
		Sentry::getCurrentUserForTracking();
		$this->assertTrue( $this->sentry->javascriptObserver() );
		global $wp_scripts;
		$this->assertContains( Sentry::WP_SCRIPT_NAME, $wp_scripts->queue );
	}
}
