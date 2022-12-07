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
<div class="pb-cloner">
	<h1><?php _e( 'Clone a Book', 'pressbooks' ); ?></h1>
	<form id="pb-cloner-form" action="" method="post">
		<?php wp_nonce_field( 'pb-cloner' ); ?>
		<p><label for="source_book_url"><?php _e( 'Source Book URL', 'pressbooks' ); ?></label></p>
		<input class="regular-text code" id="source_book_url" name="source_book_url" type="url" required />
		<p class="description" id="source_book_url_description"><?php _e( 'Enter the URL to a Pressbooks book with an open license which permits cloning.', 'pressbooks' ); ?></p>
		<label for="target_book_url"><?php _e( 'Target Book URL', 'pressbooks' ); ?></label>
		<?php
		printf(
				$template_string,
				'<input class="regular-text code" id="target_book_url" name="target_book_url" type="text" required />'
		);
		?>
		<p class="description" id="target_book_url_description"><?php _e( 'Enter an available URL where you want this book to be cloned.', 'pressbooks' ); ?></p>
		<p><input id="pb-cloner-button" class="button button-primary" type="submit" value="<?php _e( 'Clone This Book', 'pressbooks' ); ?>" /></p>
		<progress id="pb-sse-progressbar" max="100"></progress>
		<p><b><span id="pb-sse-minutes"></span><span id="pb-sse-seconds"></span></b> <span id="pb-sse-info" aria-live="polite"></span></p>
	</form>
	<div id="searchbox"></div>
	<div id="book-cards"class="book-cards"></div>
</div>
