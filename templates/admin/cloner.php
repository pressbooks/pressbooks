<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Pressbooks\Cloner;

?>
<div class="wrap">
	<h1><?php _e( 'Clone', 'pressbooks' ); ?></h1>
	<p><?php _e( 'Select a book within your network. You can clone it as a new book, or you can clone any of its content into an existing book to which you have administrative access.', 'pressbooks' ); ?><p>
	<?php $cloner = new Cloner( 'https://apurvatestbook.textopress.com/' ); ?>
	<pre><?php // Debug here. ?></pre>
	<form action="">
		<table class="form-table">
			<tr>
				<th scope=row><?php _e( 'Source Book URL', 'pressbooks' ); ?></th>
				<td><input class="regular-text code" name="source_book_id" type="url" /></td>
			</tr>
		</table>
		<p><input class="button button-primary" type="submit" value="<?php _e( 'Clone It!', 'pressbooks' ); ?>" /></p>
	</form>
</div>
