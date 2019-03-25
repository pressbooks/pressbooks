<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @see     l10n/namespace.php
 */

$overrides = [
	'My Sites' => 'Mis libros',
	'Create a New Site' => 'Crear un nuevo libro',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit site'] = 'Visita libro';
	$overrides['Visit Site'] = 'Visita libro';
	$overrides['Edit Site'] = 'Editar libro';
}

return $overrides;
