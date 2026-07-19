<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Pressbooks\Modules\Import\GoogleDocs\Broker\BrokerRefreshClient;
use Pressbooks\Modules\Import\GoogleDocs\Storage\BrokerBackedStorage;
use Pressbooks\Modules\Import\GoogleDocs\Storage\DirectEncryptedStorage;
use Pressbooks\Modules\Import\GoogleDocs\Storage\SodiumCipher;
use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage;

/**
 * Wires up the Google Docs import object graph and registers its admin hooks.
 */
class Bootstrap {

	public static function init(): void {
		$creds_store = CredentialsStore::fromEnvironment();
		$token_storage = self::buildTokenStorage( $creds_store );
		$oauth = new OAuthClient(
			$token_storage,
			$creds_store,
			self::buildBrokerRefresh( $creds_store, $token_storage )
		);

		// Network admin settings page
		( new SettingsPage( $creds_store, $oauth, $token_storage ) )->hooks();

		self::registerImportType( $creds_store, $token_storage );
		self::registerOAuthActions( $oauth, $creds_store );
		self::registerPickerSupport( $oauth, $creds_store );
	}

	private static function buildTokenStorage( CredentialsStore $creds_store ): TokenStorage {
		$cipher = new SodiumCipher();
		$encryption_key = defined( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY' ) ? PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY : '';

		return $creds_store->isBrokerMode()
			? new BrokerBackedStorage( $cipher, $encryption_key )
			: new DirectEncryptedStorage( $cipher, $encryption_key );
	}

	private static function buildBrokerRefresh( CredentialsStore $creds_store, TokenStorage $token_storage ): ?BrokerRefreshClient {
		if ( $creds_store->isBrokerMode() && defined( 'PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY' ) && defined( 'PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET' ) ) {
			return new BrokerRefreshClient(
				PRESSBOOKS_AUTH_BROKER_URL,
				PRESSBOOKS_AUTH_BROKER_PUBLIC_KEY,
				PRESSBOOKS_AUTH_BROKER_NETWORK_SECRET,
				$token_storage
			);
		}

		return null;
	}

	/**
	 * Register the Google Docs option in the import type dropdown.
	 */
	private static function registerImportType( CredentialsStore $creds_store, TokenStorage $token_storage ): void {
		add_filter( 'pb_select_import_type', function ( array $types ) use ( $creds_store, $token_storage ) {
			if ( $creds_store->isConfigured() && $token_storage->isAvailable() ) {
				$types[ GoogleDocs::TYPE_OF ] = __( 'Google Docs', 'pressbooks' );
			}
			return $types;
		} );
	}

	/**
	 * Register the OAuth authorize and disconnect admin-post actions.
	 */
	private static function registerOAuthActions( OAuthClient $oauth, CredentialsStore $creds_store ): void {
		// OAuth authorize action
		add_action( 'admin_post_pb_gdocs_authorize', function () use ( $oauth, $creds_store ) {
			check_admin_referer( 'pb_gdocs_authorize' );
			if ( ! $creds_store->isConfigured() ) {
				wp_die( esc_html__( 'Google Docs import is not configured.', 'pressbooks' ) );
			}
			$return_url = wp_get_referer() ?: admin_url( 'admin.php?page=pb_import' );
			$auth_url = $oauth->getAuthorizeUrl( $return_url );
			wp_redirect( $auth_url );
			exit;
		} );

		// OAuth disconnect action
		add_action( 'admin_post_pb_gdocs_disconnect', function () use ( $oauth ) {
			check_admin_referer( 'pb_gdocs_disconnect' );
			try {
				$oauth->disconnect( get_current_user_id() );
				$return_url = wp_get_referer() ?: admin_url( 'admin.php?page=pb_import' );
				wp_safe_redirect( add_query_arg( 'pb_gdocs', 'disconnected', $return_url ) );
				exit;
			} catch ( \Throwable $e ) {
				wp_die( esc_html( $e->getMessage() ) );
			}
		} );
	}

	/**
	 * Google Picker support: with the drive.file scope the app only has access to
	 * files the user explicitly selects through the Picker, so the import page
	 * needs a "Select from Google Drive" button. The Picker runs client-side and
	 * needs the current user's own access token, served by a nonce-protected
	 * AJAX endpoint.
	 */
	private static function registerPickerSupport( OAuthClient $oauth, CredentialsStore $creds_store ): void {
		// Access token endpoint for the Picker (returns the requesting user's own token only).
		add_action( 'wp_ajax_pb_gdocs_picker_token', function () use ( $oauth ) {
			check_ajax_referer( 'pb_gdocs_picker' );
			if ( ! current_user_can( 'edit_posts' ) ) {
				wp_send_json_error( [ 'message' => __( 'Unauthorized.', 'pressbooks' ) ], 403 );
			}
			try {
				$client = $oauth->getAuthedClient( get_current_user_id() );
				$token = $client->getAccessToken();
				wp_send_json_success( [
					'access_token' => $token['access_token'] ?? '',
					'expires_at'   => $token['expires_at'] ?? 0,
				] );
			} catch ( ReauthorizationRequiredException $e ) {
				wp_send_json_error( [
					'reauthorize'   => true,
					'authorize_url' => wp_nonce_url( admin_url( 'admin-post.php?action=pb_gdocs_authorize' ), 'pb_gdocs_authorize' ),
					'message'       => $e->getMessage(),
				] );
			} catch ( \Throwable $e ) {
				wp_send_json_error( [ 'message' => $e->getMessage() ] );
			}
		} );

		// Enqueue the Picker script on the import page.
		add_action( 'admin_enqueue_scripts', function () use ( $creds_store ) {
			$page = sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $page !== 'pb_import' ) {
				return;
			}
			if ( ! $creds_store->isConfigured() || ! $creds_store->isPickerConfigured() ) {
				return;
			}

			$picker = $creds_store->getPickerConfig();
			$version = defined( 'PB_PLUGIN_VERSION' ) ? PB_PLUGIN_VERSION : null;

			wp_enqueue_script( 'google-api-loader', 'https://apis.google.com/js/api.js', [], $version, true );
			wp_enqueue_script(
				'pb-gdocs-picker',
				plugins_url( 'assets/picker.js', __FILE__ ),
				[ 'google-api-loader' ],
				$version,
				true
			);
			wp_localize_script( 'pb-gdocs-picker', 'pbGdocsPicker', [
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'pb_gdocs_picker' ),
				'apiKey'       => $picker['api_key'],
				'appId'        => $picker['app_id'],
				/**
				 * Selectors for the import form; filter if the import page markup changes.
				 */
				'typeSelector' => apply_filters( 'pb_gdocs_picker_type_selector', '#type_of' ),
				'urlSelector'  => apply_filters( 'pb_gdocs_picker_url_selector', '#import_http_gdocs' ),
				'strings'      => [
					'buttonLabel' => __( 'Select from Google Drive', 'pressbooks' ),
					'changeLabel' => __( 'Change document', 'pressbooks' ),
					'gapiError'   => __( 'Could not load the Google Picker. Check your network connection and ad blockers.', 'pressbooks' ),
					'tokenError'  => __( 'Could not authenticate with Google. Please reconnect your Google account.', 'pressbooks' ),
				],
			] );
		} );
	}
}
