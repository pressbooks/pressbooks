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
	<h1><?php _e( 'Clone a Book', 'pressbooks' ); ?></h1>
	<p><?php printf( __( 'This tool allows you to %1$s from one Pressbooks network to another. Search the thousands of books in the %2$s for material you would like to clone. Once a book is cloned into your network, you can edit content, add new media, and enhance with H5P interactive activities.', 'pressbooks' ), sprintf( '<a href="https://guide.pressbooks.com/chapter/book-cloning/" target="_blank">%s</a>', __('clone openly licensed books', 'pressbooks' ) ), sprintf( '<a href="https://pressbooks.directory/" target="_blank">%s</a>', __( 'Pressbooks Directory', 'pressbooks' ) ) ); ?></p>
	<form id="pb-cloner-form" action="" method="post">
		<?php wp_nonce_field( 'pb-cloner' ); ?>
		<table class="form-table" role="none">
			<tr>
				<th scope=row><label for="source_book_url"><?php _e( 'Source Book URL', 'pressbooks' ); ?></label></th>
				<td>
					<input class="regular-text code" id="source_book_url" name="source_book_url" type="url" required />
					<p class="description" id="source_book_url_description"><?php _e( 'Enter the URL to a Pressbooks book with an open license which permits cloning.', 'pressbooks' ); ?></p>
				</td>
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
					<p class="description" id="target_book_url_description"><?php _e( 'Enter an available URL where you want this book to be cloned.', 'pressbooks' ); ?></p>
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
		<p><input id="pb-cloner-button" class="button button-primary" type="submit" value="<?php _e( 'Clone This Book', 'pressbooks' ); ?>" /></p>
		<progress id="pb-sse-progressbar" max="100"></progress>
		<p><b><span id="pb-sse-minutes"></span><span id="pb-sse-seconds"></span></b> <span id="pb-sse-info" aria-live="polite"></span></p>
	</form>
</div>
