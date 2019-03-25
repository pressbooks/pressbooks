<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @see     l10n/namespace.php
 */

$overrides = [
	'My Sites' => 'I miei libri',
	'Create a New Site' => 'Crea un nuovo libro',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit site'] = 'Vedi libro';
	$overrides['Visit Site'] = 'Vedi libro';
	$overrides['Edit Site'] = 'Modifica libro';
}

return $overrides;
