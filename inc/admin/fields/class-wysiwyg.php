<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

class Wysiwyg extends Field {
	public string $view = 'wysiwyg';

	public function sanitize(mixed $value): mixed
	{
		return $value;
	}

	public function save(int $post_id): void
	{

	}

	public function delete(int $post_id): void
	{

	}
}
