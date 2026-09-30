<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs\Storage;

interface Cipher {

	public function encrypt( string $plaintext, string $key ): string;

	public function decrypt( string $blob, string $key ): string;

	public function algorithm(): string;
}
