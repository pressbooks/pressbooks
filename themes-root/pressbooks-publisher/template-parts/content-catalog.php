<?php
/**
 * @package Pressbooks Publisher
 */
?>

<?php $books = get_sites( array( 'public' => true ) );
$c = 0;
foreach ( $books as $book )	:
if ( get_blog_option( $book->blog_id, 'pressbooks_publisher_in_catalog' ) ) :
	$c++;
	switch_to_blog( $book->blog_id );
	$metadata = pb_get_book_information();
	restore_current_blog(); ?>

	<article id="post-<?php echo $book->blog_id; ?>" <?php post_class('catalog-book'); ?>>

		<a href="//<?php echo $book->domain . $book->path; ?>" title="<?php echo $metadata['pb_title']; ?>"><img src="<?php echo $metadata['pb_cover_image']; ?>" width="500" height="650" alt="<?php echo $metadata['pb_title']; ?>" /></a>

		<header class="entry-header">
			<h2 class="entry-title"><a href="//<?php echo $book->domain . $book->path; ?>" title="<?php echo $metadata['pb_title']; ?>"><?php echo $metadata['pb_title']; ?></a></h2>

			<p class="entry-author"><?php echo $metadata['pb_author']; ?></p>
		</header><!-- .entry-header -->

		<div class="entry-content">
			<?php if ( isset( $metadata['pb_about_50'] ) ) : ?>
			<p><?php $about = pb_decode( $metadata['pb_about_50'] );
			if ( strlen( $about ) > 140){
				$about =  substr( $about, 0, 140 ) . '...';
			} echo $about; ?></p>
			<?php endif; ?>
		</div><!-- .entry-content -->

		<footer class="entry-footer">

			<div class="button-wrap">
				<a href="//<?php echo $book->domain . $book->path; ?>" class="more-btn"><?php _e('Read more', 'pressbooks'); ?></a>
			</div>

			<?php if ( isset( $metadata['pb_keywords_tags'] ) ) : ?>
			<span class="book-tag"><?php echo $metadata['pb_keywords_tags']; ?></span>
			<?php endif; ?>

		</footer><!-- .entry-footer -->
	</article><!-- #post-## -->


<?php endif;
endforeach;
if ( $c == 0 ) : ?>
<p class="center"><?php _e( 'The manager of this Pressbooks network has not made any books public viewable.', 'pressbooks' ); ?></p>
<?php endif;
