<?php

class RemoteGetRetryTest extends \WP_UnitTestCase {

	public function setUp() {
		parent::setUp();

		$i = 0;
		$mockedResponses = [
			[ 'response' => [ 'code' => 400 ] ],
			[ 'response' => [ 'code' => 400 ] ],
			[ 'response' => [ 'code' => 200 ] ],
		];

		add_filter( 'pre_http_request', function ( $preempt, $request, $url ) use ( &$i, $mockedResponses ) {
			$preempt = $mockedResponses[ $i ];
			$i++;
			return $preempt;
		}, 1, 3);

		// disable sleep to speed up tests
		add_filter( 'pressbooks_remote_get_retry_wait_time', function( $sleep ) {
			return 0;
		});
	}


	public function test_remote_get_retry() {

		$response = \Pressbooks\Utility\remote_get_retry( 'http://example.com', [] );

		$this->assertEquals( $response['response']['code'], 200 );
	}

	public function test_remote_get_single_retry() {

		$response = \Pressbooks\Utility\remote_get_retry( 'http://example.com', [], 1 );

		$this->assertEquals( $response['response']['code'], 400 );
	}
}
