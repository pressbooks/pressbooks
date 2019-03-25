<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @see     l10n/namespace.php
 */

$overrides = [
	'My Sites' => 'Mina Böcker',
	'Create a New Site' => 'Skapa en ny bok',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit site'] = 'Besök bok';
	$overrides['Visit Site'] = 'Besök Bok';
	$overrides['Edit Site'] = 'Visa Bok';
	$overrides['Du har använt din utrymmeskvot. Vänligen ta bort filer innan du lägger upp.'] = 'Tyvärr, du har använt alla dina lagringskvoten. Vill du ha mer utrymme? Uppgradera din bok.';
}

return $overrides;
