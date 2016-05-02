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

	'My Sites' => 'My Books',
	'Create a New Site' => 'Create a New Book',
);

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit Site'] = 'Visit Book';
	$overrides['Edit Site'] = 'Edit Book';
	$overrides['You have used your space quota. Please delete files before uploading.'] = 'Sorry, you have used all of your storage quota. Want more space? Please upgrade your book.';
}

return $overrides;