<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class CredentialsStore {

	const NETWORK_OPTION_KEY = 'pressbooks_google_docs_oauth';

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
		if ( $this->isBrokerMode() ) {
			return true;
		}
		$creds = $this->getClientCredentials();
		return ! empty( $creds['client_id'] ) && ! empty( $creds['client_secret'] );
	}

	public function isBrokerMode(): bool {
		return defined( 'PRESSBOOKS_AUTH_BROKER_URL' ) && ! empty( PRESSBOOKS_AUTH_BROKER_URL );
	}
}
