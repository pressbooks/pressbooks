<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package Pressbooks Publisher
 */
?>

	</div><!-- #content -->

	<footer class="content-info">
		<?php $contentinfo = sprintf(
			'%s <a href="%s">%s</a>',
			__( 'Powered by', 'pressbooks' ),
			esc_url( 'https://pressbooks.com' ),
			'Pressbooks'
		);
		printf(
			'<div class="container">%s</div> <!-- .container -->',
			apply_filters( 'pressbooks_publisher_content_info', $contentinfo )
		); ?>
	</footer><!-- .content-info -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
