<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 * @see     pressbooks/includes/pb-l10n.php
 */

$overrides = [
	'My Sites' => 'Minu raamatud',
	'Create a New Site' => 'Loo uus raamat',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit Site'] = 'Külasta Book';
	$overrides['Edit Site'] = 'Muuda Book';
}

return $overrides;
