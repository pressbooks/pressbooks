<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

interface TokenStorage {

	public function load( int $user_id ): ?StoredToken;

	public function save( int $user_id, StoredToken $token ): bool;

	public function delete( int $user_id ): bool;

	public function isAvailable(): bool;
}
