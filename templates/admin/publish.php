<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/* Outputs the content of the Publish page */ ?>

<div class="wrap">

	<h2><?php _e( 'Publish', 'pressbooks' ); ?></h2>
	<p><?php _e('Once your book is finished, you can download the files and submit them to ebookstores and print-on-demand providers. And if you like, Pressbooks can print your books, and ship them right to your door.', 'pressbooks' ); ?></p>

	<div class="postbox">
		<div class="inside">
			<h3><?php _e('Ebook Stores', 'pressbooks'); ?></h3>
			<p><?php printf( __( 'Once you have downloaded your files, you can either submit them to ebookstores yourself, or use a third-party distributor. Recommended self-serve ebookstores are <a href="%1s">Kindle</a>, <a href="%2s">Kobo</a>, and <a href="%3s">Nook</a>. Other ebook stores include Apple iBooks and Google.', 'pressbooks' ), 'https://kdp.amazon.com', 'https://www.kobo.com/writinglife', 'https://www.nookpress.com' ); ?></p>
			<p><?php printf( __( 'If you do not wish to submit your ebooks yourself, we recommend using a third-party distribution service such as <a href="%1s">IngramSpark</a>, which can also make your books available online in print.', 'pressbooks' ), 'https://ingramspark.com' ); ?></p>

			<h3><?php _e('Print-on-Demand', 'pressbooks'); ?></h3>
			<p><?php printf( __( 'If you wish to sell your printed books online, we recommend going through <a href="%1s">IngramSpark</a> or Amazon\'s <a href="%2s">CreateSpace</a>.', 'pressbooks' ), 'https://ingramspark.com', 'https://www.createspace.com' ); ?></p>
			
			<h3><?php _e( 'Ordering Printed Books', 'pressbooks' ); ?></h3>
			<p><?php printf( __( 'Pressbooks can print your books and send them to you. For pricing and more details, please send an email to: <a href="%1s">print@pressbooks.com</a>', 'pressbooks' ), 'mailto:print@pressbooks.com' ); ?></p>
		</div>
	</div>

	<h3><?php _e( 'Adding BUY Links to Your Pressbooks Web Book', 'pressbooks' ); ?></h3>
	<p><?php _e( 'If you would like to add <strong>BUY</strong> links to your Pressbooks web book, add the links to your book at the different retailers below:', 'pressbooks' ); ?></p>

	<form method="post" action="options.php">
		<?php settings_fields( 'ecomm_settings' );
		do_settings_sections( 'ecomm_settings' ); ?>
		<?php submit_button(); ?>
	</form>



</div>

