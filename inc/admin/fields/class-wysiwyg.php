<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

class Wysiwyg extends Field {
	public string $view = 'wysiwyg';

	public ?int $rows;

	public bool $allowHtml = true;

	public function __construct(string $name, string $label, ?string $description = null, ?string $id = null, ?int $rows = null)
	{
		parent::__construct($name, $label, $description, $id);

		$this->rows = $rows ?? 20;
	}
}
