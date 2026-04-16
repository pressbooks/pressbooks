<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class SettingsPage {

	protected CredentialsStore $store;
	protected OAuthClient $oauth;

	public function __construct( CredentialsStore $store, OAuthClient $oauth ) {
		$this->store = $store;
		$this->oauth = $oauth;
	}

	public function hooks(): void {
		add_action( 'network_admin_menu', [ $this, 'addMenu' ] );
		add_action( 'network_admin_edit_pb_save_google_docs_settings', [ $this, 'saveSettings' ] );
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
		if ( isset( $_GET['pb_oauth_callback'] ) && isset( $_GET['code'] ) ) {
			$this->handleOAuthCallback();
			return;
		}

		$creds = $this->store->getClientCredentials();
		$redirect_uri = $this->oauth->getRedirectUri();
		?>
		<div class="wrap">
			<h1><?php _e( 'Google Docs Import Settings', 'pressbooks' ); ?></h1>
			<p><?php _e( 'Configure your Google Cloud OAuth credentials to enable Google Docs import.', 'pressbooks' ); ?></p>
			<h2><?php _e( 'Required Configuration in Google Cloud Console', 'pressbooks' ); ?></h2>
			<p><?php _e( 'Add the following Authorized Redirect URI to your Google Cloud OAuth client:', 'pressbooks' ); ?></p>
			<code><?php echo esc_html( $redirect_uri ); ?></code>
			<p><?php _e( 'Required OAuth scopes:', 'pressbooks' ); ?></p>
			<ul>
				<li><code>https://www.googleapis.com/auth/documents.readonly</code></li>
				<li><code>https://www.googleapis.com/auth/drive.readonly</code></li>
			</ul>
			<form method="post" action="<?php echo esc_url( network_admin_url( 'edit.php?action=pb_save_google_docs_settings' ) ); ?>">
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

	public function saveSettings(): void {
		check_admin_referer( 'pb_save_google_docs_settings' );
		if ( ! current_user_can( 'manage_network_options' ) ) {
			wp_die( __( 'Unauthorized.', 'pressbooks' ) );
		}
		$client_id = sanitize_text_field( $_POST['client_id'] ?? '' );
		$client_secret = sanitize_text_field( $_POST['client_secret'] ?? '' );
		$this->store->saveClientCredentials( $client_id, $client_secret );
		wp_safe_redirect( add_query_arg( [
			'page'    => 'pb_network_google_docs',
			'updated' => 'true',
		], network_admin_url( 'settings.php' ) ) );
		exit;
	}

	protected function handleOAuthCallback(): void {
		$code = sanitize_text_field( $_GET['code'] ?? '' );
		$state = sanitize_text_field( $_GET['state'] ?? '' );
		if ( empty( $code ) || empty( $state ) ) {
			wp_die( __( 'Invalid OAuth callback parameters.', 'pressbooks' ) );
		}
		try {
			$return_url = $this->oauth->handleCallback( $code, $state, get_current_user_id() );
			wp_safe_redirect( add_query_arg( 'pb_gdocs', 'connected', $return_url ) );
			exit;
		} catch ( \Exception $e ) {
			wp_die( esc_html( $e->getMessage() ) );
		}
	}
}
