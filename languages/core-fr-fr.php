<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @see     l10n/namespace.php
 */

$overrides = [
	'My Sites' => 'Mes livres',
	'Create a New Site' => 'Créer un nouveau livre',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit site'] = 'Visitez livre';
	$overrides['Visit Site'] = 'Visitez livre';
	$overrides['Edit Site'] = 'Modifier livre';
}

return $overrides;
