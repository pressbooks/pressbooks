<?php

use Pressbooks\Modules\Import\GoogleDocs\CredentialsStore;
use Pressbooks\Modules\Import\GoogleDocs\OAuthClient;

class Modules_ImportGoogleDocsOAuthTest extends \WP_UnitTestCase {

	private CredentialsStore $store;
	private int $user_id;

	public function set_up(): void {
		parent::set_up();
		$this->store = new CredentialsStore();
		$this->store->saveClientCredentials( 'test-client-id', 'test-client-secret' );
		$this->user_id = self::factory()->user->create();
	}

	public function tear_down(): void {
		delete_site_option( CredentialsStore::NETWORK_OPTION_KEY );
		delete_user_meta( $this->user_id, CredentialsStore::USER_META_KEY );
		parent::tear_down();
	}

	/**
	 * @group import
	 */
	public function test_get_authorize_url_contains_required_params(): void {
		$oauth = new OAuthClient( $this->store );
		$url = $oauth->getAuthorizeUrl( 'https://example.com/return' );
		$this->assertStringContainsString( 'accounts.google.com', $url );
		$this->assertStringContainsString( 'client_id=test-client-id', $url );
		$this->assertStringContainsString( 'access_type=offline', $url );
		$this->assertStringContainsString( 'documents.readonly', urldecode( $url ) );
		$this->assertStringContainsString( 'drive.readonly', urldecode( $url ) );
	}

	/**
	 * @group import
	 */
	public function test_get_authorize_url_stores_state_transient(): void {
		$oauth = new OAuthClient( $this->store );
		$url = $oauth->getAuthorizeUrl( 'https://example.com/return' );
		parse_str( parse_url( $url, PHP_URL_QUERY ), $params );
		$state = $params['state'] ?? '';
		$this->assertNotEmpty( $state );
		$return = get_transient( 'pb_gdocs_state_' . $state );
		$this->assertSame( 'https://example.com/return', $return );
	}

	/**
	 * @group import
	 */
	public function test_extract_doc_id_from_url(): void {
		$this->assertSame(
			'1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms',
			OAuthClient::extractDocId( 'https://docs.google.com/document/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms/edit' )
		);
		$this->assertSame(
			'1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms',
			OAuthClient::extractDocId( 'https://docs.google.com/document/d/1BxiMVs0XRA5nFMdKvBdBZjgmUUqptlbs74OgVE2upms/edit#heading=h.abc' )
		);
		$this->assertNull( OAuthClient::extractDocId( 'https://docs.google.com/spreadsheets/d/abc/edit' ) );
		$this->assertNull( OAuthClient::extractDocId( 'not-a-url' ) );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_throws_when_no_token(): void {
		$this->expectException( \Pressbooks\Modules\Import\GoogleDocs\ReauthorizationRequiredException::class );
		$oauth = new OAuthClient( $this->store );
		$oauth->getAuthedClient( $this->user_id );
	}

	/**
	 * @group import
	 */
	public function test_get_authed_client_returns_client_when_token_valid(): void {
		$this->store->saveUserToken( $this->user_id, [
			'access_token'  => 'valid-token',
			'refresh_token' => 'rt',
			'expires_at'    => time() + 3600,
		] );
		$oauth = new OAuthClient( $this->store );
		$client = $oauth->getAuthedClient( $this->user_id );
		$this->assertInstanceOf( \Google\Client::class, $client );
		$this->assertSame( 'valid-token', $client->getAccessToken()['access_token'] );
	}
}
