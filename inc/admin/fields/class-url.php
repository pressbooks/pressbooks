<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

class Url extends Field {
	public string $view = 'input';

	public string $type = 'url';

	public function sanitize( mixed $value ): mixed {
		return esc_url_raw( $value );
	}
}
