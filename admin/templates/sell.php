<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/* Outputs the content of the Sell Your Book page */

?>
<div class="wrap">

	<div id="icon-sell" class="icon32"></div>
	<h2><?php _e( 'Sell Your Book', 'pressbooks' ); ?></h2>
	<p><?php _e('Would you like help getting your ebook and print-on-demand book into stores?', 'pressbooks' ); ?></p>

	<div class="pressbooks-admin-panel">
		<!-- BookBaby -->
		<div class="sell-your-book-copy">
			<h3><?php _e('Ebook Distribution', 'pressbooks'); ?></h3>
			<p><?php printf( __( 'We recommend <a href="%s" target="_blank">BookBaby</a>, a service which will get your book into 11 different ebooks stores. ', 'pressbooks' ), 'http://bookbaby.com/pressbooks' ); ?></p>
			<p><?php _e( 'For a flat fee starting at $99 (with a 10% discount for PressBooks users and 100% royalties going to you), BookBaby will send your book to the Kindle Store, Barnes & Noble, Apple\'s iBook store, Kobo, and more.', 'pressbooks' ); ?></p>
			<a href="http://bookbaby.com/pressbooks" class="button" target="_blank"><?php _e( 'Distribute with BookBaby', 'pressbooks' ); ?></a>
		</div>

		<!-- CreateSpace -->
		<div class="sell-your-book-copy">
			<h3><?php _e('Print-On-Demand', 'pressbooks'); ?></h3>
			<p><?php printf( __( 'You can submit your PDF exports to <a href="%s" target="_blank">CreateSpace</a> to get print versions of your books into Amazon, and to order copies for yourself.', 'pressbooks' ), 'http://www.jdoqocy.com/click-5666510-10801888' ); ?></p>
			<a href="http://www.jdoqocy.com/click-5666510-10801888" class="button" target="_blank"><?php _e( 'Print-on-Demand with CreateSpace', 'pressbooks' ); ?></a>
		</div>

	</div>

	<br>
	<h3><?php _e('Create a Buy Page on PressBooks', 'pressbooks'); ?></h3>
	<p><?php _e( 'Once your books are available in stores, you can add the links to bookstores below, and we\'ll make a Buy page on the web version of your PressBooks book.', 'pressbooks' ); ?></p>

		<!-- Create the form that will be used to render our options -->
		<form method="post" action="options.php">
			<?php settings_fields( 'ecomm_settings' );
			do_settings_sections( 'ecomm_settings' ); ?>
			<?php submit_button(); ?>
		</form>



</div>