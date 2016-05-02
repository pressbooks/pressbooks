<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 * @see     pressbooks/includes/pb-l10n.php
 */

$overrides = array(

	// 'View all posts filed under %s' => 'See all articles filed under %s',
	// 'Howdy, %1$s' => 'Greetings, %1$s!',
	// Add some more strings here...

	'My Sites' => 'Mis libros',
	'Create a New Site' => 'Crear un nuevo libro',
);

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit Site'] = 'Visita libro';
	$overrides['Edit Site'] = 'Editar libro';
}

return $overrides;