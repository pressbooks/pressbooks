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
		<table class="form-table">
			<tr>
				<th scope=row><?php _e( 'Source Book URL', 'pressbooks' ); ?></th>
				<td><input class="regular-text code" name="source_book_url" type="url" required /></td>
			</tr>
			<tr>
				<th scope=row><?php _e( 'Target Book URL', 'pressbooks' ); ?></th>
				<td>
					<?php
					printf(
						$template_string,
						'<input class="regular-text code" name="target_book_url" type="text" required />'
					);
					?>
				</td>
			</tr>
			<tr>
				<th scope=row><?php _e( 'Target Book Title', 'pressbooks' ); ?></th>
				<td>
					<input class="regular-text" name="target_book_title" type="text" />
					<p class="description"><?php _e( 'Optional. If you leave this blank, the title of the source book will be used.', 'pressbooks' ); ?></p>
				</td>
			</tr>
		</table>
		<p><input id="pb-cloner-button" class="button button-primary" type="submit" value="<?php _e( 'Clone It!', 'pressbooks' ); ?>" /><span id="loader" class="loading-content"></span></p>
		<div id="pb-sse-progressbar"></div>
		<p><b><span id="pb-sse-minutes"></span><span id="pb-sse-seconds"></span></b> <span id="pb-sse-info"></span></p>
	</form>
</div>
