<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  PressBooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 * @see     pressbooks/includes/pb-l10n.php
 */

$overrides = array(

	// 'View all posts filed under %s' => 'See all articles filed under %s',
	// 'Howdy, %1$s' => 'Greetings, %1$s!',
	// Add some more strings here...

	'My Sites' => 'Minu raamatud',
	'Create a New Site' => 'Loo uus raamat',
);

if ( \PressBooks\Book::isBook() ) {
	$overrides['Settings'] = 'Tööriistad';
	$overrides['Visit Site'] = 'Külasta Book';
	$overrides['Edit Site'] = 'Muuda Book';
}

return $overrides;