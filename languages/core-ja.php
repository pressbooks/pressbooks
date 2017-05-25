<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 * @see     pressbooks/includes/pb-l10n.php
 */

$overrides = [
	'My Sites' => 'マイブックス',
	'Create a New Site' => '新規ブックを作成します。',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit Site'] = 'ブックをご覧ください';
	$overrides['Edit Site'] = 'ブックをご覧ください';
}

return $overrides;
