<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Fields;

class TextArea extends Field {
	public string $view = 'textarea';

	public bool $allowHtml = true;
};
