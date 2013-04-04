<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/* Outputs the content of the Sell Your Book page */

?>
<div class="wrap">

	<div id="icon-sell" class="icon32"></div>
	<h2><?php _e( 'Sell Your Book', 'pressbooks' ); ?></h2>

	<div class="sell-your-book-copy">
		<!-- BookBaby -->
		<p><?php
		printf( __( 'Would you like to get your book into ebook stores? Our distribution partner, <a href="%s" target="_blank">BookBaby</a>, can help you. ', 'pressbooks' ), 'http://bookbaby.com/pressbooks' );
		_e( 'For a flat fee starting at $99 (with a 10% discount for PressBooks users and 100% royalties going to you), BookBaby will send your book to the Kindle Store, Barnes & Noble, Apple\'s iBook store, and Kobo.', 'pressbooks' );
		?></p>
		<a href=" http://bookbaby.com/pressbooks" class="button" target="_blank"><?php _e( 'Distribute with BookBaby', 'pressbooks' ); ?></a>
		<p><?php _e( 'Once your books are available, you can add the links to bookstores below and we will make a Buy page on the web version of your PressBooks book.', 'pressbooks' ); ?></p>
	</div>

	<!-- Create the form that will be used to render our options -->
	<form method="post" action="options.php">
		<?php settings_fields( 'ecomm_settings' );
		do_settings_sections( 'ecomm_settings' ); ?>
		<?php submit_button(); ?>
	</form>

</div>