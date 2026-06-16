<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage;

class OAuthClient {

	const SCOPES = [
		'https://www.googleapis.com/auth/documents.readonly',
		'https://www.googleapis.com/auth/drive.readonly',
	];

	const STATE_TRANSIENT_TTL = 600;

	private CredentialsStore $creds_store;

	private TokenStorage $token_storage;

	private bool $useBroker;

	private ?Broker\BrokerRefreshClient $broker_refresh_client;

	public function __construct(
		TokenStorage $token_storage,
		CredentialsStore $creds_store,
		?Broker\BrokerRefreshClient $broker_refresh_client = null
	) {
		$this->token_storage = $token_storage;
		$this->creds_store = $creds_store;
		$this->broker_refresh_client = $broker_refresh_client;
		$this->useBroker = $creds_store->isBrokerMode();
	}

	public function setBrokerRefreshClient( Broker\BrokerRefreshClient $client ): void {
		$this->broker_refresh_client = $client;
	}

	public static function fromEnvironment( CredentialsStore $creds_store ): self {
		$cipher = new Storage\SodiumCipher();
		$encryption_key = defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ? PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY : '';
		$storage = $creds_store->isBrokerMode()
			? new Storage\BrokerBackedStorage( $cipher, $encryption_key )
			: new Storage\DirectEncryptedStorage( $cipher, $encryption_key );
		$broker_refresh = null;
		if ( $creds_store->isBrokerMode() && defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) && defined( 'PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET' ) ) {
			$broker_refresh = new Broker\BrokerRefreshClient(
				PRESSBOOKS_AUTH_BROKER_URL,
				PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY,
				PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET,
				$storage
			);
		}
		return new self( $storage, $creds_store, $broker_refresh );
	}

	public function isBrokerMode(): bool {
		return $this->useBroker;
	}

	public function isConnected( int $user_id ): bool {
		$token = $this->token_storage->load( $user_id );
		if ( $token === null ) {
			return false;
		}
		if ( $this->useBroker ) {
			return $token->brokerSessionHandle() !== null;
		}
		return $token->refreshToken() !== null;
	}

	public function buildClient(): \Google\Client {
		if ( $this->useBroker ) {
			throw new \RuntimeException( 'buildClient() must not be called in broker mode. OAuth is handled by the Pressbooks Auth Broker.' );
		}

		$creds = $this->creds_store->getClientCredentials();
		$client = new \Google\Client();
		$client->setClientId( $creds['client_id'] );
		$client->setClientSecret( $creds['client_secret'] );
		$client->setRedirectUri( $this->getRedirectUri() );
		$client->setAccessType( 'offline' );
		$client->setPrompt( 'consent' );
		$client->setScopes( self::SCOPES );

		return $client;
	}

	public function getAuthorizeUrl( string $return_url ): string {
		$state = wp_generate_password( 32, false );
		set_site_transient( 'pb_gdocs_state_' . $state, $return_url, self::STATE_TRANSIENT_TTL );

		if ( $this->useBroker ) {
			return $this->getBrokerAuthorizeUrl( $state );
		}

		$client = $this->buildClient();
		$client->setState( $state );
		return $client->createAuthUrl();
	}

	public function handleCallback( string $jwt_or_code, string $state, int $user_id ): string {
		if ( $this->useBroker ) {
			return $this->handleBrokerCallback( $jwt_or_code, $state, $user_id );
		}

		return $this->handleGoogleCallback( $jwt_or_code, $state, $user_id );
	}

	public function getAuthedClient( int $user_id ): \Google\Client {
		$token = $this->token_storage->load( $user_id );

		if ( $token === null ) {
			throw new ReauthorizationRequiredException( 'No token found. Please authorize first.' );
		}

		if ( $this->useBroker ) {
			if ( $token->isExpired() ) {
				if ( $this->broker_refresh_client === null ) {
					throw new ReauthorizationRequiredException( 'Token expired and no BrokerRefreshClient is configured.' );
				}
				$token = $this->broker_refresh_client->refresh( $user_id );
			}
			$client = new \Google\Client();
			$client->setAccessToken( $token->payload );
			return $client;
		}

		$client = $this->buildClient();
		$client->setAccessToken( $token->payload );

		if ( $client->isAccessTokenExpired() ) {
			$refresh_token = $token->refreshToken();
			if ( ! $refresh_token ) {
				$this->token_storage->delete( $user_id );
				throw new ReauthorizationRequiredException( 'No refresh token available. Please reauthorize.' );
			}

			$new_token = $client->fetchAccessTokenWithRefreshToken( $refresh_token );

			if ( isset( $new_token['error'] ) ) {
				$this->token_storage->delete( $user_id );
				throw new ReauthorizationRequiredException( 'Token refresh failed: ' . ( $new_token['error_description'] ?? $new_token['error'] ) );
			}

			$new_token['refresh_token'] = $refresh_token;
			$new_token['expires_at'] = time() + ( $new_token['expires_in'] ?? 3600 );

			$this->token_storage->save( $user_id, new StoredToken( $new_token, TokenMode::Direct ) );
			$client->setAccessToken( $new_token );
		}

		return $client;
	}

	public function disconnect( int $user_id ): void {
		$token = $this->token_storage->load( $user_id );

		if ( $token === null ) {
			return;
		}

		if ( $this->useBroker ) {
			if ( $this->broker_refresh_client !== null ) {
				try {
					$this->broker_refresh_client->revoke( $user_id );
					return;
				} catch ( \Throwable $e ) {
					throw new \RuntimeException( 'Failed to revoke token at broker: ' . $e->getMessage() . ' Please try again.', 0, $e );
				}
			}
			$this->token_storage->delete( $user_id );
			return;
		}

		try {
			$client = $this->buildClient();
			$client->setAccessToken( $token->payload );
			$client->revokeToken();
		} catch ( \Exception $e ) {
			throw new \RuntimeException( 'Failed to revoke token at Google: ' . $e->getMessage() . ' Please try again.', 0, $e );
		}
		$this->token_storage->delete( $user_id );
	}

	public static function extractDocId( string $url ): ?string {
		if ( preg_match( '#docs\.google\.com/document/d/([a-zA-Z0-9_-]+)#', $url, $matches ) ) {
			return $matches[1];
		}

		return null;
	}

	public function getRedirectUri(): string {
		return network_home_url( 'wp-admin/admin-post.php?action=pb_gdocs_callback' );
	}

	private function getBrokerAuthorizeUrl( string $state ): string {
		$params = [
			'origin' => parse_url( home_url(), PHP_URL_HOST ),
			'wp_state' => $state,
		];
		return rtrim( PRESSBOOKS_AUTH_BROKER_URL, '/' ) . '/oauth/start?' . http_build_query( $params );
	}

	private function getPublicKey(): string {
		$value = PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY;

		if ( file_exists( $value ) ) {
			$contents = file_get_contents( $value );
			if ( $contents === false ) {
				throw new \RuntimeException( 'Failed to read broker public key file: ' . $value );
			}
			return $contents;
		}

		return $value;
	}

	private function handleBrokerCallback( string $jwt, string $state, int $user_id ): string {
		$transient_key = 'pb_gdocs_state_' . $state;
		$return_url = get_site_transient( $transient_key );

		if ( empty( $return_url ) ) {
			throw new \RuntimeException( 'Invalid or expired OAuth state.' );
		}

		delete_site_transient( $transient_key );

		if ( ! defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) || empty( PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY ) ) {
			throw new \RuntimeException( 'Broker public key not configured.' );
		}

		$public_key = $this->getPublicKey();
		$decoded = JWT::decode( $jwt, new Key( $public_key, 'RS256' ) );

		if ( ! isset( $decoded->iss ) || $decoded->iss !== PRESSBOOKS_AUTH_BROKER_URL ) {
			throw new \RuntimeException( 'Invalid JWT issuer.' );
		}

		$expected_aud = parse_url( home_url(), PHP_URL_HOST );
		if ( ! isset( $decoded->aud ) || $decoded->aud !== $expected_aud ) {
			throw new \RuntimeException( 'Invalid JWT audience.' );
		}

		if ( ! isset( $decoded->exp ) || $decoded->exp < time() ) {
			throw new \RuntimeException( 'JWT has expired.' );
		}

		if ( ! isset( $decoded->jti ) ) {
			throw new \RuntimeException( 'Missing JWT ID.' );
		}

		$jti_key = 'pb_gdocs_jti_' . $decoded->jti;
		if ( get_site_transient( $jti_key ) ) {
			throw new \RuntimeException( 'JWT has already been used.' );
		}
		set_site_transient( $jti_key, '1', 300 );

		if ( ! isset( $decoded->wp_state ) || $decoded->wp_state !== $state ) {
			throw new \RuntimeException( 'JWT state mismatch.' );
		}

		if ( ! isset( $decoded->tokens->access_token, $decoded->session_handle ) ) {
			throw new \RuntimeException( 'Missing access_token or session_handle in JWT.' );
		}

		if ( property_exists( $decoded->tokens, 'refresh_token' ) && ! empty( $decoded->tokens->refresh_token ) ) {
			throw new \RuntimeException( 'Broker handoff JWT must not contain a refresh_token.' );
		}

		if ( ! isset( $decoded->google_sub ) || ! is_string( $decoded->google_sub ) || $decoded->google_sub === '' ) {
			throw new \RuntimeException( 'Missing google_sub in JWT.' );
		}

		$payload = [
			'session_handle' => (string) $decoded->session_handle,
			'access_token'   => (string) $decoded->tokens->access_token,
			'expires_at'     => isset( $decoded->tokens->expires_at )
				? (int) $decoded->tokens->expires_at
				: ( time() + ( $decoded->tokens->expires_in ?? 3600 ) ),
			'google_sub'     => (string) $decoded->google_sub,
		];

		$this->token_storage->save(
			$user_id,
			new StoredToken( $payload, TokenMode::Broker )
		);

		return $return_url;
	}

	private function handleGoogleCallback( string $code, string $state, int $user_id ): string {
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
		$this->token_storage->save( $user_id, new StoredToken( $token, TokenMode::Direct ) );

		return $return_url;
	}
}
