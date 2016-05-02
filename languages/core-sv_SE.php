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

	'My Sites' => 'Mina Böcker',
	'Create a New Site' => 'Skapa en ny bok',
	);

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit Site'] = 'Besök Bok';
	$overrides['Edit Site'] = 'Visa Bok';
	$overrides['Du har använt din utrymmeskvot. Vänligen ta bort filer innan du lägger upp.'] = 'Tyvärr, du har använt alla dina lagringskvoten. Vill du ha mer utrymme? Uppgradera din bok.';
}

return $overrides;