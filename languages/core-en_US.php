<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 * @see     pressbooks/includes/pb-l10n.php
 */

$overrides = array(

	// 'View all posts filed under %s' => 'See all articles filed under %s',
	// 'Howdy, %1$s' => 'Greetings, %1$s!',
	// Add some more strings here...

	'My Sites' => 'My Books',
	'Create a New Site' => 'Create a New Book',
);

if ( \PressBooks\Book::isBook() ) {
	$overrides['Settings'] = 'Utilities';
	$overrides['Visit Site'] = 'Visit Book';
	$overrides['Edit Site'] = 'Edit Book';
}

return $overrides;