<?php

use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Pressbooks\Modules\Import\GoogleDocs\DocsFetcher;

class Modules_ImportGoogleDocsFetcherTest extends \WP_UnitTestCase {

	private function makeClientWithMock( array $responses ): \Google\Client {
		$mock    = new MockHandler( $responses );
		$stack   = HandlerStack::create( $mock );
		$guzzle  = new \GuzzleHttp\Client( [ 'handler' => $stack ] );

		$client = new \Google\Client();
		$client->setHttpClient( $guzzle );
		$client->setAccessToken( [
			'access_token' => 'fake_token',
			'expires_in'   => 3600,
			'created'      => time(),
			'token_type'   => 'Bearer',
		] );
		return $client;
	}

	/**
	 * @group import
	 */
	public function test_fetch_document_returns_php_array(): void {
		$json = json_encode( [
			'documentId' => 'doc_abc',
			'title'      => 'My Test Doc',
			'body'       => [ 'content' => [] ],
		] );

		$client  = $this->makeClientWithMock( [ new Response( 200, [ 'Content-Type' => 'application/json' ], $json ) ] );
		$fetcher = new DocsFetcher( $client );
		$result  = $fetcher->fetchDocument( 'doc_abc' );

		$this->assertIsArray( $result );
		$this->assertSame( 'doc_abc', $result['documentId'] );
		$this->assertSame( 'My Test Doc', $result['title'] );
		$this->assertArrayHasKey( 'body', $result );
	}

	/**
	 * @group import
	 */
	public function test_get_file_metadata_returns_name_and_mime_type(): void {
		$json = json_encode( [
			'name'     => 'My Document',
			'mimeType' => 'application/vnd.google-apps.document',
		] );

		$client  = $this->makeClientWithMock( [ new Response( 200, [ 'Content-Type' => 'application/json' ], $json ) ] );
		$fetcher = new DocsFetcher( $client );
		$result  = $fetcher->getFileMetadata( 'doc_abc' );

		$this->assertSame( 'My Document', $result['title'] );
		$this->assertSame( 'application/vnd.google-apps.document', $result['mimeType'] );
	}

	/**
	 * @group import
	 */
	public function test_download_image_returns_body_on_200(): void {
		$image_bytes = 'fake_image_data';

		$mock_http = $this->createMock( \GuzzleHttp\Client::class );
		$mock_http->method( 'get' )->willReturn( new Response( 200, [], $image_bytes ) );

		$mock_client = $this->createMock( \Google\Client::class );
		$mock_client->method( 'authorize' )->willReturn( $mock_http );

		$fetcher = new DocsFetcher( $mock_client );
		$result  = $fetcher->downloadImage( 'https://example.com/img.png' );

		$this->assertSame( $image_bytes, $result );
	}

	/**
	 * @group import
	 */
	public function test_download_image_returns_false_on_non_200(): void {
		$mock_http = $this->createMock( \GuzzleHttp\Client::class );
		$mock_http->method( 'get' )->willReturn( new Response( 404, [], '' ) );

		$mock_client = $this->createMock( \Google\Client::class );
		$mock_client->method( 'authorize' )->willReturn( $mock_http );

		$fetcher = new DocsFetcher( $mock_client );
		$result  = $fetcher->downloadImage( 'https://example.com/missing.png' );

		$this->assertFalse( $result );
	}

	/**
	 * @group import
	 */
	public function test_download_image_returns_false_on_exception(): void {
		$mock_http = $this->createMock( \GuzzleHttp\Client::class );
		$mock_http->method( 'get' )->willThrowException( new \Exception( 'Network error' ) );

		$mock_client = $this->createMock( \Google\Client::class );
		$mock_client->method( 'authorize' )->willReturn( $mock_http );

		$fetcher = new DocsFetcher( $mock_client );
		$result  = $fetcher->downloadImage( 'https://example.com/img.png' );

		$this->assertFalse( $result );
	}

	/**
	 * @group import
	 */
	public function test_fetch_and_cache_writes_json_file_and_returns_path(): void {
		$doc_id = 'doc_cache_test';
		$json   = json_encode( [
			'documentId' => $doc_id,
			'title'      => 'Cached Doc',
			'body'       => [ 'content' => [] ],
		] );

		$client    = $this->makeClientWithMock( [ new Response( 200, [ 'Content-Type' => 'application/json' ], $json ) ] );
		$fetcher   = new DocsFetcher( $client );
		$cache_dir = sys_get_temp_dir() . '/pb-gdoc-test-' . uniqid();
		mkdir( $cache_dir, 0777, true );

		$path = $fetcher->fetchAndCache( $doc_id, $cache_dir );

		$this->assertFileExists( $path );
		$this->assertStringContainsString( $doc_id, basename( $path ) );
		$this->assertStringEndsWith( '.json', $path );
		$contents = json_decode( file_get_contents( $path ), true );
		$this->assertSame( 'Cached Doc', $contents['title'] );

		unlink( $path );
		rmdir( $cache_dir );
	}
}
