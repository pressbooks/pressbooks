<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Broker;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Pressbooks\Modules\Import\GoogleDocs\ReauthorizationRequiredException;
use Pressbooks\Modules\Import\GoogleDocs\Storage\StoredToken;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenMode;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage;

class BrokerRefreshClient {

	private const REFRESH_PATH = '/oauth/refresh';
	private const REVOKE_PATH = '/oauth/revoke';

	private string $broker_url;
	private string $broker_public_key;
	private string $network_secret;
	private TokenStorage $storage;

	public function __construct(
		string $broker_url,
		string $broker_public_key,
		string $network_secret,
		TokenStorage $storage
	) {
		$this->broker_url = rtrim( $broker_url, '/' );
		$this->broker_public_key = $this->resolvePublicKey( $broker_public_key );
		$this->network_secret = $network_secret;
		$this->storage = $storage;
	}

	public function refresh( int $user_id ): StoredToken {
		$stored = $this->storage->load( $user_id );
		if ( $stored === null || $stored->brokerSessionHandle() === null ) {
			throw new ReauthorizationRequiredException( __( 'No broker session handle for user.', 'pressbooks' ) );
		}

		$signed = $this->buildSignedBody( [
			'handle'     => $stored->brokerSessionHandle(),
			'google_sub' => $stored->googleSub() ?? '',
		] );

		$response = $this->post( self::REFRESH_PATH, $signed );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code === 410 ) {
			$this->storage->delete( $user_id );
			throw new ReauthorizationRequiredException( __( 'Broker reports session as gone; user must reconnect.', 'pressbooks' ) );
		}

		if ( $code === 401 ) {
			throw new \RuntimeException( __( 'Broker rejected signature or freshness.', 'pressbooks' ) );
		}

		if ( $code === 409 ) {
			throw new \RuntimeException( __( 'Broker detected replay.', 'pressbooks' ) );
		}

		if ( $code >= 500 ) {
			throw new \RuntimeException( __( 'Broker temporarily unavailable.', 'pressbooks' ) );
		}

		if ( $code !== 200 ) {
			throw new \RuntimeException(
				sprintf(
					// translators: %d: HTTP response code returned by the broker.
					__( 'Unexpected broker response code %d.', 'pressbooks' ),
					$code
				)
			);
		}

		$jwt = wp_remote_retrieve_body( $response );
		$payload = $this->verifySignedResponse( $jwt );

		$new_payload = [
			'session_handle' => $stored->brokerSessionHandle(),
			'access_token'   => $payload->access_token,
			'expires_at'     => (int) $payload->expires_at,
			'google_sub'     => $stored->googleSub(),
		];

		$this->storage->save( $user_id, new StoredToken( $new_payload, TokenMode::Broker ) );

		return new StoredToken( $new_payload, TokenMode::Broker );
	}

	public function revoke( int $user_id ): void {
		$stored = $this->storage->load( $user_id );
		if ( $stored === null ) {
			return;
		}

		$signed = $this->buildSignedBody( [
			'handle'     => $stored->brokerSessionHandle() ?? '',
			'google_sub' => $stored->googleSub() ?? '',
		] );

		$response = $this->post( self::REVOKE_PATH, $signed );
		$code = wp_remote_retrieve_response_code( $response );

		if ( $code >= 500 ) {
			throw new \RuntimeException( __( 'Broker temporarily unavailable during revoke.', 'pressbooks' ) );
		}

		if ( $code !== 204 && $code !== 200 ) {
			throw new \RuntimeException(
				sprintf(
					// translators: %d: HTTP response code returned by the broker.
					__( 'Unexpected broker revoke response code %d.', 'pressbooks' ),
					$code
				)
			);
		}

		$this->storage->delete( $user_id );
	}

	private function buildSignedBody( array $claims ): array {
		$body = array_merge( $claims, [
			'origin' => parse_url( home_url(), PHP_URL_HOST ),
			'jti'    => wp_generate_password( 32, false ),
			'iat'    => time(),
		] );

		$encoded = json_encode( $body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		$signature = hash_hmac( 'sha256', $encoded, $this->network_secret );

		return [
			'body' => $encoded,
			'headers' => [
				'Content-Type'        => 'application/json',
				'X-Network-Signature' => $signature,
			],
		];
	}

	private function verifySignedResponse( string $jwt ): object {
		$decoded = JWT::decode( $jwt, new Key( $this->broker_public_key, 'RS256' ) );

		if ( ! isset( $decoded->iss ) || $decoded->iss !== $this->broker_url ) {
			throw new \RuntimeException( __( 'Invalid broker response issuer.', 'pressbooks' ) );
		}

		$expected_aud = parse_url( home_url(), PHP_URL_HOST );
		if ( ! isset( $decoded->aud ) || $decoded->aud !== $expected_aud ) {
			throw new \RuntimeException( __( 'Invalid broker response audience.', 'pressbooks' ) );
		}

		if ( ! isset( $decoded->exp ) || $decoded->exp < time() ) {
			throw new \RuntimeException( __( 'Broker response JWT expired.', 'pressbooks' ) );
		}

		if ( ! isset( $decoded->access_token, $decoded->expires_at ) ) {
			throw new \RuntimeException( __( 'Missing access_token or expires_at in broker response.', 'pressbooks' ) );
		}

		return $decoded;
	}

	private function post( string $path, array $signed ): array {
		$response = wp_remote_post(
			$this->broker_url . $path,
			[
				'method'  => 'POST',
				'headers' => $signed['headers'],
				'body'    => $signed['body'],
				'timeout' => 15,
			]
		);

		if ( is_wp_error( $response ) ) {
			throw new \RuntimeException(
				sprintf(
					// translators: %s: underlying HTTP error detail.
					__( 'Broker unreachable: %s', 'pressbooks' ),
					$response->get_error_message()
				)
			);
		}

		return $response;
	}

	private function resolvePublicKey( string $value ): string {
		if ( file_exists( $value ) ) {
			$contents = file_get_contents( $value );
			if ( $contents === false ) {
				throw new \RuntimeException(
					sprintf(
						// translators: %s: file path.
						__( 'Failed to read broker public key file: %s', 'pressbooks' ),
						$value
					)
				);
			}
			return $contents;
		}

		return $value;
	}
}
