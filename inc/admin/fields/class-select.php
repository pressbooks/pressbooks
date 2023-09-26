<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

class Select extends Field {
	public array $options = [];

	public string $default = '';

	public string $view = 'select';

	public function __construct(string $name, string $label, ?string $description = null, ?string $id = null, array $options = [], string $default = '')
	{
		parent::__construct($name, $label, $description, $id);

		$this->options = $options;
		$this->default = $default;
	}
}
