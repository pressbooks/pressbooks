<?php global $blog_id; ?>
	<?php if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))): ?>

	<div id="sidebar">

		<?php if ( @array_filter( get_option( 'pressbooks_ecommerce_links' ) ) ) : ?>
			    <!-- Buy -->
				<p class="icon-fontawesome-webfont"><a class="buy-button" href="<?php echo get_option('home'); ?>/buy"><?php _e('Buy', 'pressbooks'); ?></a></p>		
			<?php endif; ?>	
	</div><!-- end #sidebar -->
	<?php endif; ?>
