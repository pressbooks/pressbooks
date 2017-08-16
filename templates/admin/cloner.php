<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>
<div class="wrap">
	<h1><?php _e( 'Clone', 'pressbooks' ); ?></h1>
	<p><?php _e( 'Enter the URL to a Pressbooks book to clone it.', 'pressbooks' ); ?><p>
	<form id="pb-cloner-form" action="" method="post">
		<?php wp_nonce_field( 'pb-cloner' ); ?>
		<table class="form-table">
			<tr>
				<th scope=row><?php _e( 'Source Book URL', 'pressbooks' ); ?></th>
				<td><input class="regular-text code" name="source_book_url" type="url" /></td>
			</tr>
		</table>
	</form>
	<p><input id="pb-cloner-button" class="button button-primary" type="submit" value="<?php _e( 'Clone It!', 'pressbooks' ); ?>" /></p>
	<p id="loader"><img src="<?php echo PB_PLUGIN_URL; ?>assets/dist/images/loader.gif" alt="Cloning..." width="128" height="15" /></p>
</div>
