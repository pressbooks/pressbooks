<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

// @phpcs:disable PSR1.Files.SideEffects.FoundWithSymbols

namespace Pressbooks\Admin\Fields;

class TextArea extends Field {
	public string $view = 'textarea';

	public bool $allowHtml = true;
};
