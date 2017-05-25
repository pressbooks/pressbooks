<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 * @see     pressbooks/includes/pb-l10n.php
 */

$overrides = [
	'My Sites' => 'Meine Bücher',
	'Create a New Site' => 'Erstellen Sie ein neues Buch',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit Site'] = 'Besuchen Buch';
	$overrides['Edit Site'] = 'Bearbeiten Book';
}

return $overrides;
