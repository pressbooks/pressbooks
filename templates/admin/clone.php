<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
} ?>
<div class="wrap">
	<h1><?php _e( 'Clone', 'pressbooks' ); ?></h1>
	<p><?php _e( 'Select a book within your network. You can clone it as a new book, or you can clone any of its content into an existing book to which you have administrative access.', 'pressbooks' ); ?><p>
	<div class="books-wrapper">
		<select class="books"><option value=""><?php _e( 'Select a book', 'pressbooks' ); ?></option></select>
		<span class="spinner is-active"></span>
	</div>
</div>
