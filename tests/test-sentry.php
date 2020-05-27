<?php

class SentryTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * Test sentry init function
	 * It should be false since we are using mock credentials for Sentry
	 *
	 * @return \PHPUnit\Framework\Assert
	 *
	 */
	public function test_sentry_init() {
		// We set environment variable to force testing Init function
		putenv( 'SENTRY_KEY=mock_key' );
		putenv( 'SENTRY_ORGANIZATION=mock_org' );
		putenv( 'SENTRY_PROJECT=mock_project' );
		putenv( 'WP_ENV=testing' );

		$this->assertFalse( \Pressbooks\Utility\initialize_sentry() );
	}
}
