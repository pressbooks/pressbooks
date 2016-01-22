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
		<div class="site-info">
		Copyright &copy; <?php echo date('Y'); ?> <?php bloginfo('name');?> <a href="<?php echo esc_url( __( 'http://pressbooks.com', 'pressbooks' ) ); ?>"><?php printf( esc_html__( 'is powered by %s', 'pressbooks' ), 'Pressbooks.com' ); ?></a>
		</div><!-- .site-info -->
	</footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
