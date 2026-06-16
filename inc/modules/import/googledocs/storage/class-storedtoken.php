<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

final class StoredToken {

	public readonly array $payload;
	public readonly TokenMode $mode;

	public function __construct( array $payload, TokenMode $mode ) {
		$this->payload = $payload;
		$this->mode = $mode;
	}

	public function accessToken(): ?string {
		return $this->payload['access_token'] ?? null;
	}

	public function refreshToken(): ?string {
		return $this->payload['refresh_token'] ?? null;
	}

	public function brokerSessionHandle(): ?string {
		return $this->payload['session_handle'] ?? null;
	}

	public function googleSub(): ?string {
		return $this->payload['google_sub'] ?? null;
	}

	public function expiresAt(): int {
		return (int) ( $this->payload['expires_at'] ?? 0 );
	}

	public function isExpired( int $skew = 0 ): bool {
		return $this->expiresAt() <= ( time() + $skew );
	}
}
