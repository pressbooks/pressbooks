<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 * @see     pressbooks/includes/pb-l10n.php
 */

$overrides = [
	'My Sites' => '我的書籍',
	'Create a New Site' => '創建一個新的書',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit Site'] = '訪問書';
	$overrides['Edit Site'] = '編輯書';
}

return $overrides;
