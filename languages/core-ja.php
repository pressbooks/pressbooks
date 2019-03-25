<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @see     l10n/namespace.php
 */

$overrides = [
	'My Sites' => 'マイブックス',
	'Create a New Site' => '新規ブックを作成します。',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit site'] = 'ブックをご覧ください';
	$overrides['Visit Site'] = 'ブックをご覧ください';
	$overrides['Edit Site'] = 'ブックをご覧ください';
}

return $overrides;
