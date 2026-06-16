<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Pressbooks\Modules\Import\GoogleDocs\Storage\TokenStorage;

class SettingsPage {

	protected CredentialsStore $store;
	protected OAuthClient $oauth;
	protected TokenStorage $token_storage;

	public function __construct(
		CredentialsStore $store,
		OAuthClient $oauth,
		TokenStorage $token_storage
	) {
		$this->store = $store;
		$this->oauth = $oauth;
		$this->token_storage = $token_storage;
	}

	public function hooks(): void {
		add_action( 'network_admin_menu', [ $this, 'addMenu' ] );
		add_action( 'admin_post_pb_gdocs_callback', [ $this, 'handleOAuthCallback' ] );
		add_action( 'network_admin_notices', [ $this, 'maybeRenderEncryptionKeyNotice' ] );
		add_action( 'admin_notices', [ $this, 'maybeRenderEncryptionKeyNotice' ] );
	}

	public function maybeRenderEncryptionKeyNotice(): void {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			return;
		}
		if ( $this->token_storage->isAvailable() ) {
			return;
		}
		if ( ! $this->store->isConfigured() ) {
			return;
		}
		?>
		<div class="notice notice-error">
			<p><strong><?php _e( 'Google Docs Import is disabled.', 'pressbooks' ); ?></strong></p>
			<p>
			<?php
				printf(
					/* translators: %s: configuration constant name */
					esc_html__( 'The %s constant must be defined in wp-config.php (or Bedrock config/application.php) with a 32-byte base64-encoded key. Generate one with: openssl rand -base64 32', 'pressbooks' ),
					'<code>PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY</code>'
				);
			?>
			</p>
		</div>
		<?php
	}

	public function addMenu(): void {
		add_submenu_page(
			'settings.php',
			__( 'Google Docs Import', 'pressbooks' ),
			__( 'Google Docs Import', 'pressbooks' ),
			'manage_network_options',
			'pb_network_google_docs',
			[ $this, 'renderPage' ]
		);
	}

	public function renderPage(): void {
		if ( $this->oauth->isBrokerMode() ) {
			$this->renderBrokerPage();
			return;
		}

		$updated = false;
		if ( ! empty( $_POST ) && check_admin_referer( 'pb_save_google_docs_settings' ) ) {
			if ( ! current_user_can( 'manage_network_options' ) ) {
				wp_die( esc_html__( 'Unauthorized.', 'pressbooks' ) );
			}
			$client_id = sanitize_text_field( wp_unslash( $_POST['client_id'] ?? '' ) );
			$client_secret = sanitize_text_field( wp_unslash( $_POST['client_secret'] ?? '' ) );
			$this->store->saveClientCredentials( $client_id, $client_secret );
			$updated = true;
		}

		$creds = $this->store->getClientCredentials();
		$redirect_uri = $this->oauth->getRedirectUri();
		?>
		<div class="wrap">
			<h1><?php _e( 'Google Docs Import Settings', 'pressbooks' ); ?></h1>
			<?php if ( $updated ) : ?>
				<div id="message" role="status" class="updated notice is-dismissible"><p><strong><?php _e( 'Settings saved.', 'pressbooks' ); ?></strong></p></div>
			<?php endif; ?>
			<p><?php _e( 'Configure your Google Cloud OAuth credentials to enable Google Docs import.', 'pressbooks' ); ?></p>
			<h2><?php _e( 'Required Configuration in Google Cloud Console', 'pressbooks' ); ?></h2>
			<p><?php _e( 'Add the following Authorized Redirect URI to your Google Cloud OAuth client:', 'pressbooks' ); ?></p>
			<code><?php echo esc_html( $redirect_uri ); ?></code>
			<p><?php _e( 'Required OAuth scopes:', 'pressbooks' ); ?></p>
			<ul>
				<li><code>https://www.googleapis.com/auth/documents.readonly</code></li>
				<li><code>https://www.googleapis.com/auth/drive.readonly</code></li>
			</ul>
			<form method="post" action="">
				<?php wp_nonce_field( 'pb_save_google_docs_settings' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="client_id"><?php _e( 'Client ID', 'pressbooks' ); ?></label></th>
						<td><input type="text" id="client_id" name="client_id" value="<?php echo esc_attr( $creds['client_id'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="client_secret"><?php _e( 'Client Secret', 'pressbooks' ); ?></label></th>
						<td><input type="password" id="client_secret" name="client_secret" value="<?php echo esc_attr( $creds['client_secret'] ); ?>" class="regular-text" /></td>
					</tr>
				</table>
				<?php submit_button( __( 'Save Settings', 'pressbooks' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function handleOAuthCallback(): void {
		if ( ! is_user_logged_in() ) {
			auth_redirect();
		}

		$error = sanitize_text_field( wp_unslash( $_GET['error'] ?? '' ) );
		if ( $error ) {
			$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );
			$return_url = $state ? get_site_transient( 'pb_gdocs_state_' . $state ) : false;
			if ( $return_url ) {
				delete_site_transient( 'pb_gdocs_state_' . $state );
				wp_redirect( add_query_arg( 'pb_gdocs', 'denied', $return_url ) );
			} else {
				wp_redirect( admin_url( 'admin.php?page=pb_import&pb_gdocs=denied' ) );
			}
			exit;
		}

		$broker_token = sanitize_text_field( wp_unslash( $_GET['token'] ?? '' ) );
		$code = sanitize_text_field( wp_unslash( $_GET['code'] ?? '' ) );
		$state = sanitize_text_field( wp_unslash( $_GET['state'] ?? '' ) );

		if ( $broker_token ) {
			if ( empty( $state ) ) {
				wp_die( esc_html__( 'Invalid OAuth callback parameters.', 'pressbooks' ) );
			}
			try {
				$return_url = $this->oauth->handleCallback( $broker_token, $state, get_current_user_id() );
				wp_redirect( add_query_arg( 'pb_gdocs', 'connected', $return_url ) );
				exit;
			} catch ( \Exception $e ) {
				wp_die( esc_html( $e->getMessage() ) );
			}
		}

		if ( $code ) {
			if ( empty( $state ) ) {
				wp_die( esc_html__( 'Invalid OAuth callback parameters.', 'pressbooks' ) );
			}
			try {
				$return_url = $this->oauth->handleCallback( $code, $state, get_current_user_id() );
				wp_redirect( add_query_arg( 'pb_gdocs', 'connected', $return_url ) );
				exit;
			} catch ( \Exception $e ) {
				wp_die( esc_html( $e->getMessage() ) );
			}
		}
	}

	protected function renderBrokerPage(): void {
		?>
		<div class="wrap">
			<h1><?php _e( 'Google Docs Import Settings', 'pressbooks' ); ?></h1>
			<div class="notice notice-info inline">
				<p><?php _e( 'Google authentication is managed centrally via the Pressbooks Auth Broker. No local configuration is required.', 'pressbooks' ); ?></p>
			</div>
		</div>
		<?php
	}
}
