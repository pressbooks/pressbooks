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

	'My Sites' => 'I miei libri',
	'Create a New Site' => 'Crea un nuovo libro',
);

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit Site'] = 'Vedi libro';
	$overrides['Edit Site'] = 'Modifica libro';
}

return $overrides;
