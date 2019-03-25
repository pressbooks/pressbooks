<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @see     l10n/namespace.php
 */

$overrides = [
	'My Sites' => 'My Books',
	'Create a New Site' => 'Create a New Book',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit site'] = 'Visit book';
	$overrides['Visit Site'] = 'Visit Book';
	$overrides['Edit Site'] = 'Edit Book';
	$overrides['You have used your space quota. Please delete files before uploading.'] = 'Sorry, you have used all of your storage quota. Want more space? Please upgrade your book.';
	$overrides['Delete Site'] = 'Delete Book';
	$overrides['Delete My Site'] = 'Delete My Book';
	$overrides['Delete My Site Permanently'] = 'Delete My Book Permanently';
	$overrides["I'm sure I want to permanently disable my site, and I am aware I can never get it back or use %s again."] = "I'm sure I want to permanently disable my book, and I am aware I can never get it back or use %s again.";
	$overrides['If you do not want to use your %s site any more, you can delete it using the form below. When you click <strong>Delete My Site Permanently</strong> you will be sent an email with a link in it. Click on this link to delete your site.'] = 'If you do not want to use your %s book any more, you can delete it using the form below. When you click <strong>Delete My Book Permanently</strong> you will be sent an email with a link in it. Click on this link to delete your book.';
	$overrides['Remember, once deleted your site cannot be restored.'] = 'Remember, once deleted, your book cannot be restored.';
	$overrides['Thank you. Please check your email for a link to confirm your action. Your site will not be deleted until this link is clicked.'] = 'Thank you. Please check your email for a link to confirm your action. Your book will not be deleted until this link is clicked.';
	$overrides['Thank you for using %s, your site has been deleted. Happy trails to you until we meet again.' ] = 'Thank you for using %s, your book has been deleted.';
}

return $overrides;
