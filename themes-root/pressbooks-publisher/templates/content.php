<div class="page-content col-xs-12 col-md-8 col-md-offset-2">
	<div class="catalog row">
	<?php
	/**
	 * Filter the WP_Site_Query args for the catalog display.
	 *
	 * @since 3.9.7
	 */
	$args = apply_filters( 'pb_publisher_catalog_query_args', array( 'public' => '1' ) );
	$books = new WP_Site_Query( $args );
	foreach ( $books->sites as $book ) {
		if ( get_blog_option( $book->blog_id, 'pressbooks_publisher_in_catalog' ) ) {
			switch_to_blog( $book->blog_id );
			$metadata = pb_get_book_information();
			restore_current_blog(); ?>

		<div id="book-<?php echo $book->blog_id; ?>" class="book">

			<a href="//<?php echo $book->domain . $book->path; ?>" title="<?php echo $metadata['pb_title']; ?>"><img src="<?php echo $metadata['pb_cover_image']; ?>" width="500" height="650" alt="<?php echo $metadata['pb_title']; ?>" /></a>

			<header class="header">
				<h2 class="title"><a href="//<?php echo $book->domain . $book->path; ?>" title="<?php echo $metadata['pb_title']; ?>"><?php echo $metadata['pb_title']; ?></a></h2>
				<p class="author"><?php echo $metadata['pb_author']; ?></p>
			</header><!-- .header -->

			<div class="excerpt">
				<?php if ( isset( $metadata['pb_about_50'] ) ) : ?>
				<p><?php $about = pb_decode( $metadata['pb_about_50'] );
				if ( strlen( $about ) > 140 ) {
					$about = substr( $about, 0, 140 ) . '&hellip;';
				} echo $about; ?></p>
				<?php endif; ?>
			</div><!-- .excerpt -->

			<footer class="footer">

				<div class="button-wrap">
					<a href="//<?php echo $book->domain . $book->path; ?>" class="btn btn-primary"><?php _e( 'Read More', 'pressbooks' ); ?></a>
				</div>

				<?php if ( isset( $metadata['pb_keywords_tags'] ) ) { ?>
					<span class="tag"><?php echo $metadata['pb_keywords_tags']; ?></span>
				<?php } ?>

			</footer><!-- .footer -->
		</div><!-- #book-## -->
	<?php }
	} ?>
	</div>
</div>
