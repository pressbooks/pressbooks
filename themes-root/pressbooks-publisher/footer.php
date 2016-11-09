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

	<footer id="colophon" class="site-footer" role="contentinfo">
		<?php $contentinfo = sprintf(
			'%s &copy; %d | %s %s <a href="%s">%s</a>',
			__( 'Copyright', 'pressbooks' ),
			date( 'Y' ),
			get_bloginfo( 'name' ),
			__( 'is powered by', 'pressbooks' ),
			esc_url( 'https://pressbooks.com' ),
			'Pressbooks'
		);
		printf(
			'<div class="site-info">%s</div> <!-- .site-info -->',
			apply_filters( 'pressbooks_publisher_content_info', $contentinfo )
		); ?>
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
