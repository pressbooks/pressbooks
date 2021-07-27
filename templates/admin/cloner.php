<?php if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$base_url = wp_parse_url( network_home_url(), PHP_URL_HOST );
if ( is_subdomain_install() ) {
	$template_string = "<p>%s</p><p>.$base_url</p>";
} else {
	$template_string = "<p>$base_url/</p><p>%s</p>";
}

?>
<div class="wrap">
	<h1><?php _e( 'Clone', 'pressbooks' ); ?></h1>
	<p><?php _e( 'Enter the URL to a Pressbooks book to clone it.', 'pressbooks' ); ?><p>
	<form id="pb-cloner-form" action="" method="post">
		<?php wp_nonce_field( 'pb-cloner' ); ?>
		<table class="form-table" role="none">
			<tr>
				<th scope=row><label for="source_book_url"><?php _e( 'Source Book URL', 'pressbooks' ); ?></label></th>
				<td><input class="regular-text code" id="source_book_url" name="source_book_url" type="url" required /></td>
			</tr>
			<tr>
				<th scope=row><label for="target_book_url"><?php _e( 'Target Book URL', 'pressbooks' ); ?></label></th>
				<td>
					<?php
					printf(
						$template_string,
						'<input class="regular-text code" id="target_book_url" name="target_book_url" type="text" required />'
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope=row><label for="target_book_title"><?php _e( 'Target Book Title', 'pressbooks' ); ?></label></th>
				<td>
					<input class="regular-text" id="target_book_title" name="target_book_title" type="text" aria-describedby="target_book_title_description" />
					<p class="description" id="target_book_title_description"><?php _e( 'Optional. If you leave this blank, the title of the source book will be used.', 'pressbooks' ); ?></p>
				</td>
			</tr>
		</table>
		<p><input id="pb-cloner-button" class="button button-primary" type="submit" value="<?php _e( 'Clone It!', 'pressbooks' ); ?>" /></p>
		<progress id="pb-sse-progressbar" max="100"></progress>
		<p><b><span id="pb-sse-minutes"></span><span id="pb-sse-seconds"></span></b> <span id="pb-sse-info" aria-live="polite"></span></p>
	</form>
</div>
