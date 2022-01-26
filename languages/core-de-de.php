<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @see     l10n/namespace.php
 */

$overrides = [
	'My Sites' => 'Meine Bücher',
	'Create a New Site' => 'Erstellen Sie ein neues Buch',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit site'] = 'Besuchen buch';
	$overrides['Visit Site'] = 'Besuchen Buch';
	$overrides['Edit Site'] = 'Bearbeiten Book';
}

return $overrides;
