<?php

use Pressbooks\PressbooksSentry;

use \Sentry\ClientBuilderInterface;
use \Sentry\ClientBuilder;

/**
 * @group sentry
 */

class SentryTest extends \WP_UnitTestCase {

	/**
	 * @var PressbooksSentry
	 */
	protected $sentry;

	/**
	 * @var Pressbooks\DataCollector\User
	 */
	protected $user;

	/**
	 * @var string
	 */
	protected $fake_dsn;

	/**
	 * @var ClientBuilderInterface
	 */
	protected $sentry_client;

	/**
	 * Test setup
	 */
	public function setUp() {
		parent::setUp();
		$this->sentry_client = $this->getMockBuilder( ClientBuilderInterface::class )
			->getMock();
		$this->sentry_client->expects( $this->any() )
			->method( 'create' )
			->willReturn( new ClientBuilder() );

		$this->user = $this->factory()->user->create_and_get( [ 'role' => 'contributor' ] );
		$this->sentry = new PressbooksSentry();
		$this->fake_dsn = 'https://123@abc.ingest.sentry.io/pb';
	}

	public function tearDown() {
		$this->clearEnvironmentVariables();
		$this->sentry->init();
	}

	/**
	 * Clear Sentry environment variables
	 */
	private function clearEnvironmentVariables() {
		putenv( 'WP_ENV' );
		putenv( 'ENABLE_SENTRY' );
		putenv( 'SENTRY_DSN' );
		putenv( 'SENTRY_INITIALIZE_PHP' );
		putenv( 'SENTRY_INITIALIZE_JAVASCRIPT' );
		putenv( 'SENTRY_TRACE_SAMPLE_RATE' );
		putenv( 'SENTRY_PROJECT' );
		putenv( 'SENTRY_KEY' );
		putenv( 'SENTRY_ORGANIZATION' );
	}

	/**
	 * Test init Sentry function to test instance returned.
	 */
	public function test_getInstance() {
		$sentry = $this->sentry->init();
		$this->assertInstanceOf( '\Pressbooks\PressbooksSentry', $sentry );
	}

	public function test_areEnvironmentVariablesPresent() {
		$this->assertFalse( PressbooksSentry::areEnvironmentVariablesPresent() );
		putenv( 'WP_ENV=test' );
		$this->assertFalse( PressbooksSentry::areEnvironmentVariablesPresent() );
		putenv( 'ENABLE_SENTRY=1' );
		putenv( 'SENTRY_DSN=' . $this->fake_dsn );
		$this->assertTrue( PressbooksSentry::areEnvironmentVariablesPresent() );
	}

	public function test_areEnvironmentVariablesPresentCompatibility() {
		$this->assertFalse( PressbooksSentry::areEnvironmentVariablesPresent() );
		putenv( 'WP_ENV=test' );
		$this->assertFalse( PressbooksSentry::areEnvironmentVariablesPresent() );
		putenv( 'SENTRY_PROJECT=pb' );
		putenv( 'SENTRY_KEY=123' );
		putenv( 'SENTRY_ORGANIZATION=abc' );
		$this->assertTrue( PressbooksSentry::areEnvironmentVariablesPresent() );
	}

	/**
	 * Test get Sentry DSN function to verify necessary variables for integration.
	 */
	public function test_getSentryDSNFromEnvironmentVariables() {
		$this->assertFalse( $this->sentry->setSentryDSNFromEnvironmentVariables() );
		putenv( 'WP_ENV=test' );
		$this->assertFalse( $this->sentry->setSentryDSNFromEnvironmentVariables() );
		putenv( 'ENABLE_SENTRY=1' );
		putenv( 'SENTRY_DSN=' . $this->fake_dsn );
		$this->assertEquals( $this->fake_dsn, $this->sentry->setSentryDSNFromEnvironmentVariables() );
	}

	/**
	 * Test get Sentry DSN function to verify necessary variables for compatibility with previous Sentry integration.
	 */
	public function test_getSentryDSNFromEnvironmentVariablesCompatibility() {
		$this->assertFalse( $this->sentry->setSentryDSNFromEnvironmentVariables() );
		putenv( 'WP_ENV=test' );
		putenv( 'SENTRY_PROJECT=pb' );
		putenv( 'SENTRY_KEY=123' );
		$this->assertFalse( $this->sentry->setSentryDSNFromEnvironmentVariables() );
		putenv( 'SENTRY_ORGANIZATION=abc' );
		$this->assertEquals( $this->fake_dsn, $this->sentry->setSentryDSNFromEnvironmentVariables() );
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
		$this->assertFalse( $this->sentry->phpObserver() );
		$this->sentry->setUserForTracking( $this->user );
		putenv( 'WP_ENV=test' );
		putenv( 'ENABLE_SENTRY=1' );
		putenv( 'SENTRY_DSN=' . $this->fake_dsn );
		$this->assertFalse( $this->sentry->phpObserver() );
	}

	/**
	 * Test javascript observer for Sentry, it should enqueue the sentry.js script
	 */
	public function test_javascriptObserver() {
		$this->sentry->setUserForTracking( $this->user );
		$this->assertFalse( $this->sentry->javascriptObserver() );
		putenv( 'WP_ENV=test' );
		putenv( 'ENABLE_SENTRY=1' );
		putenv( 'SENTRY_DSN=' . $this->fake_dsn );
		$this->sentry->setSentryDSNFromEnvironmentVariables();
		$this->assertTrue( $this->sentry->javascriptObserver() );
		global $wp_scripts;
		$this->assertContains( PressbooksSentry::WP_SCRIPT_NAME, $wp_scripts->queue );
	}
}
