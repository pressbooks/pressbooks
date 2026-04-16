<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class OAuthClient {

	const SCOPES = [
		\Google\Service\Docs::DOCUMENTS_READONLY,
		\Google\Service\Drive::DRIVE_READONLY,
	];

	const STATE_TRANSIENT_TTL = 600;

	private CredentialsStore $store;

	public function __construct( CredentialsStore $store ) {
		$this->store = $store;
	}

	/**
	 * Build a base Google\Client with credentials but no user token.
	 */
	public function buildClient(): \Google\Client {
		$creds = $this->store->getClientCredentials();
		$client = new \Google\Client();
		$client->setClientId( $creds['client_id'] );
		$client->setClientSecret( $creds['client_secret'] );
		$client->setRedirectUri( $this->getRedirectUri() );
		$client->setAccessType( 'offline' );
		$client->setPrompt( 'consent' );
		$client->setScopes( self::SCOPES );

		return $client;
	}

	/**
	 * Generate the OAuth authorize URL and store a CSRF state transient.
	 */
	public function getAuthorizeUrl( string $return_url ): string {
		$client = $this->buildClient();
		$state = wp_generate_password( 32, false );
		set_site_transient( 'pb_gdocs_state_' . $state, $return_url, self::STATE_TRANSIENT_TTL );
		$client->setState( $state );

		return $client->createAuthUrl();
	}

	/**
	 * Handle the OAuth callback: validate state, exchange code for token, store it.
	 *
	 * @return string The return URL stored in the state transient.
	 * @throws \RuntimeException If state is invalid.
	 */
	public function handleCallback( string $code, string $state, int $user_id ): string {
		$transient_key = 'pb_gdocs_state_' . $state;
		$return_url = get_site_transient( $transient_key );

		if ( empty( $return_url ) ) {
			throw new \RuntimeException( 'Invalid or expired OAuth state.' );
		}

		delete_site_transient( $transient_key );

		$client = $this->buildClient();
		$token = $client->fetchAccessTokenWithAuthCode( $code );

		if ( isset( $token['error'] ) ) {
			throw new \RuntimeException( 'Token exchange failed: ' . ( $token['error_description'] ?? $token['error'] ) );
		}

		$token['expires_at'] = time() + ( $token['expires_in'] ?? 3600 );
		$this->store->saveUserToken( $user_id, $token );

		return $return_url;
	}

	/**
	 * Return an authenticated Google\Client for the given user.
	 * Refreshes the token if it has expired.
	 *
	 * @throws ReauthorizationRequiredException If no token exists or refresh fails.
	 */
	public function getAuthedClient( int $user_id ): \Google\Client {
		$token = $this->store->getUserToken( $user_id );

		if ( ! $token ) {
			throw new ReauthorizationRequiredException( 'No token found. Please authorize first.' );
		}

		$client = $this->buildClient();
		$client->setAccessToken( $token );

		if ( $client->isAccessTokenExpired() ) {
			$refresh_token = $token['refresh_token'] ?? null;

			if ( ! $refresh_token ) {
				$this->store->deleteUserToken( $user_id );
				throw new ReauthorizationRequiredException( 'No refresh token available. Please reauthorize.' );
			}

			$new_token = $client->fetchAccessTokenWithRefreshToken( $refresh_token );

			if ( isset( $new_token['error'] ) ) {
				$this->store->deleteUserToken( $user_id );
				throw new ReauthorizationRequiredException( 'Token refresh failed: ' . ( $new_token['error_description'] ?? $new_token['error'] ) );
			}

			$new_token['refresh_token'] = $refresh_token;
			$new_token['expires_at'] = time() + ( $new_token['expires_in'] ?? 3600 );
			$this->store->saveUserToken( $user_id, $new_token );
			$client->setAccessToken( $new_token );
		}

		return $client;
	}

	/**
	 * Revoke the user's token and delete it from storage.
	 */
	public function disconnect( int $user_id ): void {
		$token = $this->store->getUserToken( $user_id );

		if ( $token ) {
			try {
				$client = $this->buildClient();
				$client->setAccessToken( $token );
				$client->revokeToken();
			} catch ( \Exception $e ) {
				// Revocation is best-effort; proceed with local cleanup.
			}
		}

		$this->store->deleteUserToken( $user_id );
	}

	/**
	 * Extract a Google Docs document ID from a URL.
	 *
	 * @return string|null The document ID or null if not a valid Google Docs URL.
	 */
	public static function extractDocId( string $url ): ?string {
		if ( preg_match( '#docs\.google\.com/document/d/([a-zA-Z0-9_-]+)#', $url, $matches ) ) {
			return $matches[1];
		}

		return null;
	}

	/**
	 * The OAuth redirect URI for the network admin callback.
	 */
	public function getRedirectUri(): string {
		return network_admin_url( 'settings.php?page=pb_network_google_docs&pb_oauth_callback=1' );
	}
}
