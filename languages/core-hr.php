<?php
/**
 * Change core WordPress strings using $overrides array.
 *
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 * @see     l10n/namespace.php
 */

$overrides = [
	'My Sites' => 'Moje knjige',
	'Create a New Site' => 'Napiši novu knjigu',
];

if ( \Pressbooks\Book::isBook() ) {
	$overrides['Visit site'] = 'Posjeti knjigu';
	$overrides['Visit Site'] = 'Posjeti knjigu';
	$overrides['Edit Site'] = 'Uredi knjigu';
	$overrides['You have used your space quota. Please delete files before uploading.'] = 'Nažalost, iskoristili ste svoju kvotu za pohranu. Želite više prostora? Nadogradite svoju knjigu.';
	$overrides['Delete Site'] = 'Obriši knjigu';
	$overrides['Delete My Site'] = 'Obriši moju knjigu';
	$overrides['Delete My Site Permanently'] = 'Trajno obriši moju knjigu';
	$overrides["I'm sure I want to permanently disable my site, and I am aware I can never get it back or use %s again."] = "Siguran sam da želim trajno onemogućiti svoju knjigu i svjestan sam da je nikad ne mogu vratiti ili ponovno koristiti.";
	$overrides['If you do not want to use your %s site any more, you can delete it using the form below. When you click <strong>Delete My Site Permanently</strong> you will be sent an email with a link in it. Click on this link to delete your site.'] = 'Ako više ne želite koristiti svoju knjigu "%s", možete je izbrisati pomoću obrasca u nastavku. Kada kliknete na <strong>Trajno izbriši moju knjigu</strong>, bit će vam poslana poruka e-pošte s vezom. Kliknite tu vezu da biste izbrisali knjigu.';
	$overrides['Remember, once deleted your site cannot be restored.'] = 'Vodite računa da jednom obrisanu knjigu više nije moguće vratiti.';
	$overrides['Thank you. Please check your email for a link to confirm your action. Your site will not be deleted until this link is clicked.'] = 'Hvala. Molimo provjerite svoju e-poštu, poslana Vam je poruka sa daljnjim uputama i vezom za brisanje knjige. Vaša knjiga neće biti obrisana dok ne kliknete na vezu iz poruke.';
	$overrides['Thank you for using %s, your site has been deleted. Happy trails to you until we meet again.' ] = 'Hvala što ste koristili %s, Vaša knjiga je obrisana.';
}

return $overrides;
