<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 * @see     pressbooks/includes/pb-l10n.php
 */

$overrides = [
	'My Sites' => 'Mes livres',
	'Create a New Site' => 'Créer un nouveau livre',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit Site'] = 'Visitez livre';
	$overrides['Edit Site'] = 'Modifier livre';
}

return $overrides;
