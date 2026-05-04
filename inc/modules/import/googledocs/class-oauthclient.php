<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class OAuthClient {

	const SCOPES = [
		'https://www.googleapis.com/auth/documents.readonly',
		'https://www.googleapis.com/auth/drive.readonly',
	];

	const STATE_TRANSIENT_TTL = 600;

	private CredentialsStore $store;

	private bool $useBroker;

	public function __construct( CredentialsStore $store ) {
		$this->store = $store;
		$this->useBroker = defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) && ! empty( PRESSBOOKS_AUTH_BROKER_URL );
	}

	public function isBrokerMode(): bool {
		return $this->useBroker;
	}

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

	public function disconnect( int $user_id ): void {
		$token = $this->store->getUserToken( $user_id );

		if ( $token ) {
			try {
				$client = $this->buildClient();
				$client->setAccessToken( $token );
				$client->revokeToken();
			} catch ( \Exception $e ) {
			}
		}

		$this->store->deleteUserToken( $user_id );
	}

	public static function extractDocId( string $url ): ?string {
		if ( preg_match( '#docs\.google\.com/document/d/([a-zA-Z0-9_-]+)#', $url, $matches ) ) {
			return $matches[1];
		}

		return null;
	}

	public function getRedirectUri(): string {
		return network_admin_url( 'settings.php?page=pb_network_google_docs&pb_oauth_callback=1' );
	}

	private function getBrokerAuthorizeUrl( string $state ): string {
		$params = [
			'redirect_uri' => $this->getRedirectUri(),
			'state' => $state,
		];
		return rtrim( PRESSBOOKS_AUTH_BROKER_URL, '/' ) . '/auth/redirect?' . http_build_query( $params );
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

		if ( ! isset( $decoded->tokens ) ) {
			throw new \RuntimeException( 'Missing tokens in JWT.' );
		}

		$tokens = (array) $decoded->tokens;
		$tokens['expires_at'] = time() + ( $tokens['expires_in'] ?? 3600 );
		$this->store->saveUserToken( $user_id, $tokens );

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
		$this->store->saveUserToken( $user_id, $token );

		return $return_url;
	}
}
