<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

class EncryptionKeyMissingException extends \RuntimeException {

	public function __construct( ?string $message = null ) {
		parent::__construct(
			$message ?? __( 'PRESSBOOKS_GOOGLE_DOCS_ENCRYPTION_KEY is not defined. Add a 32-byte base64-encoded key to wp-config.php (or Bedrock config/application.php). Generate one with: openssl rand -base64 32', 'pressbooks' )
		);
	}
}
