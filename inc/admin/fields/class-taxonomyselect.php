<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

class TaxonomySelect extends Field {
	public array $options = [];

	public string $taxonomy = '';

	public string $default = '';

	public string $view = 'taxonomy-select';

	public function __construct(string $name, string $label, ?string $description = null, ?string $id = null, bool $multiple = false, string $taxonomy = null, array $options = [], string $default = '')
	{
		parent::__construct($name, $label, $description, $id, $multiple);

		$this->taxonomy = $taxonomy;
		$this->options = $options;
		$this->default = $default;
	}
}
