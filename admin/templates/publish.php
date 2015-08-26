<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

/* Outputs the content of the Publish page */

?>
<div class="wrap">

	<h2><?php _e( 'Publish', 'pressbooks' ); ?></h2>
	<p><?php _e('Once your book is finished, you can download the files and submit them to ebookstores and print-on-demand providers. And if you like, Pressbooks can print your books, and ship them right to your door.', 'pressbooks' ); ?></p>

	<div class="pressbooks-admin-panel">

		<div class="sell-your-book-copy">
			<h3><?php _e('Ebook Stores', 'pressbooks'); ?></h3>
			<p>Once you have downloaded your files, you can either submit them to ebookstores yourself, or use a third-party distributor. Recommended self-serve ebookstores are <a href="https://kdp.amazon.com/">Kindle</a>, <a href="https://www.kobo.com/writinglife">Kobo</a>, and <a href="https://www.nookpress.com/">Nook</a>. Other ebook stores include Apple iBooks and Google.</p>
		<p>If you do not wish to submit your ebooks yourself, we recommend using a third-party distribution service such as <a href="https://ingramspark.com/">IngramSpark</a>, which can also make your books available online in print.</p>
		</div>

		<div class="sell-your-book-copy">
			<h3><?php _e('Print-On-Demand', 'pressbooks'); ?></h3>
			<p>If you wish to sell your printed books online, we recommend going through <a href="https://ingramspark.com/">IngramSpark</a> or Amazon's <a href="https://www.createspace.com/">CreateSpace</a>.</p>
		</div>
		<div class="sell-your-book-copy">
		<h3>Ordering Printed Books</h3>
		<p>Pressbooks can print your books and send them to you. For pricing and more details, please send an email to: <a href="mailto:print@pressbooks.com">print@pressbooks.com</a> </p>
		</div>

	</div>

	<br>
	<h3><?php _e('Adding BUY Links to Your Pressbooks Web Book', 'pressbooks'); ?></h3>
	<p><?php _e( 'If you would like to add <strong>BUY</strong> links to your Pressbooks web book, add the links to your book at the different retailers below:', 'pressbooks' ); ?></p>

		<!-- Create the form that will be used to render our options -->
		<form method="post" action="options.php">
			<?php settings_fields( 'ecomm_settings' );
			do_settings_sections( 'ecomm_settings' ); ?>
			<?php submit_button(); ?>
		</form>



</div>