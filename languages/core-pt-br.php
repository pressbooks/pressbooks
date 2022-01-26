<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @see     l10n/namespace.php
 */

$overrides = [
	'My Sites' => 'Meus livros',
	'Create a New Site' => 'Criar um novo livro',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit site'] = 'Visitar livro';
	$overrides['Visit Site'] = 'Visitar Livro';
	$overrides['Edit Site'] = 'Editar Livro';
}

return $overrides;
