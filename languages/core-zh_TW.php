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

	'My Sites' => '我的書籍',
	'Create a New Site' => '創建一個新的書',
);

if ( \PressBooks\Book::isBook() ) {
	$overrides['Settings'] = '公用事業';
}

return $overrides;
