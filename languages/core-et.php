<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @see     l10n/namespace.php
 */

$overrides = [
	'My Sites' => 'Minu raamatud',
	'Create a New Site' => 'Loo uus raamat',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit site'] = 'Külasta book';
	$overrides['Visit Site'] = 'Külasta Book';
	$overrides['Edit Site'] = 'Muuda Book';
}

return $overrides;
