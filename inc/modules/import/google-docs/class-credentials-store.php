<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class CredentialsStore {

	const NETWORK_OPTION_KEY = 'pressbooks_google_docs_oauth';
	const USER_META_KEY = 'pressbooks_google_docs_token';

	public function getClientCredentials(): array {
		$option = get_site_option( self::NETWORK_OPTION_KEY, [] );
		return [
			'client_id'     => $option['client_id'] ?? '',
			'client_secret' => $option['client_secret'] ?? '',
		];
	}

	public function saveClientCredentials( string $client_id, string $client_secret ): bool {
		return update_site_option( self::NETWORK_OPTION_KEY, [
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		] );
	}

	public function isConfigured(): bool {
		$creds = $this->getClientCredentials();
		return ! empty( $creds['client_id'] ) && ! empty( $creds['client_secret'] );
	}

	public function getUserToken( int $user_id ): ?array {
		$token = get_user_meta( $user_id, self::USER_META_KEY, true );
		return ! empty( $token ) && is_array( $token ) ? $token : null;
	}

	public function saveUserToken( int $user_id, array $token ): bool {
		return (bool) update_user_meta( $user_id, self::USER_META_KEY, $token );
	}

	public function deleteUserToken( int $user_id ): bool {
		return delete_user_meta( $user_id, self::USER_META_KEY );
	}

	public function isUserConnected( int $user_id ): bool {
		$token = $this->getUserToken( $user_id );
		return $token !== null && ! empty( $token['refresh_token'] );
	}
}
