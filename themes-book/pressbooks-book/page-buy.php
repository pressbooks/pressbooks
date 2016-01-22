<?php get_header(); ?>
<?php if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))): ?>
           <div id="post-<?php the_ID(); ?>" <?php post_class('buy-page'); ?>>
		     <h2 class="page-title"><?php _e('Buy the Book', 'pressbooks'); ?></h2>
		     
		     <?php $urls = get_option('pressbooks_ecommerce_links'); ?>
		     
		     <?php if (empty($urls)): ?>
		       <p><?php _e('It\'s coming!', 'pressbooks'); ?></p>
		     <?php else: ?>
			     <?php foreach ($urls as $key => $url): ?>
			       <?php if (empty($url)): ?>
			         <?php unset($urls[$key]);?>
			       <?php endif; ?>
			     <?php endforeach; ?>	
			     <?php if (empty($urls)): ?>
				 	<p><?php _e('It\'s coming!', 'pressbooks'); ?></p>
				 <?php else: ?>
					 <p><?php printf( __( 'You can buy <a href="%1$s">%2$s</a> by following any of the links below:', 'pressbooks' ), get_bloginfo( 'url' ), get_bloginfo( 'name' ) ); ?></p>
					 			 
					 <ul class="buy-book">
					   <?php if (isset($urls['amazon']) && $urls['amazon']): ?>
					   <li class="buy-amazon"><a href="<?php print $urls['amazon']; ?>" class="bookstore-logo-link logo"><img src="<?php bloginfo('template_directory'); ?>/images/amazon.png" width="100" height="20" alt="amazon-logo" title="Amazon"/></a><?php printf( __('Purchase on  <a href="%1$s">amazon.com</a>', 'pressbooks'), $urls['amazon']); ?></li>
		         <?php endif; ?>
					   
					   <?php if (isset($urls['oreilly']) && $urls['oreilly']): ?>
					   <li class="buy-oreilly"><a href="<?php print $urls['oreilly']; ?>" class="bookstore-logo-link logo"><img src="<?php bloginfo('template_directory'); ?>/images/oreilly.png" width="100" height="18" alt="oreilly-logo" title="Oreilly"/></a><?php printf( __('Purchase on  <a href="%1$s">oreilly.com</a>', 'pressbooks'), $urls['oreilly']); ?></li>
		         <?php endif; ?>
					   
					   <?php if (isset($urls['barnesandnoble']) && $urls['barnesandnoble']): ?>
					   <li class="buy-barnes-and-noble"><a href="<?php print $urls['barnesandnoble']; ?>" class="bookstore-logo-link logo"><img src="<?php bloginfo('template_directory'); ?>/images/barnes-and-noble.png" width="100" height="16" alt="barnes-and-noble-logo" title="Barnes &amp; Noble"/></a><?php printf( __('Purchase on  <a href="%1$s">barnesandnoble.com</a>', 'pressbooks'), $urls['barnesandnoble']); ?></li>
		         <?php endif; ?>
					   
					   <?php if (isset($urls['kobo']) && $urls['kobo']): ?>
					   <li class="buy-kobo"><a href="<?php print $urls['kobo']; ?>" class="bookstore-logo-link logo"><img src="<?php bloginfo('template_directory'); ?>/images/kobo.png" width="54" height="29" alt="kobo-logo" title="Kobo"/></a><?php printf( __('Purchase on  <a href="%1$s">kobobooks.com</a>', 'pressbooks'), $urls['kobo']); ?></li>
		         <?php endif; ?>
					   
					   <?php if (isset($urls['ibooks']) && $urls['ibooks']): ?>
					   <li class="buy-ibooks"><a href="<?php print $urls['ibooks']; ?>" class="bookstore-logo-link logo"><img src="<?php bloginfo('template_directory'); ?>/images/ibooks.png" width="34" height="34" alt="ibooks-logo" title="iBook"/></a><?php printf( __('Purchase on  <a href="%1$s">apple.com</a>', 'pressbooks'), $urls['ibooks']); ?></li>
		         <?php endif; ?>
					   
					   <?php if (isset($urls['otherservice']) && $urls['otherservice']): ?>
					   <li class="buy-other"><a href="<?php print $urls['otherservice']; ?>" class="bookstore-other-link logo"><?php _e('Elsewhere', 'pressbooks'); ?></a><?php _e('Purchase here:', 'pressbooks'); ?> <a href="<?php print $urls['otherservice']; ?>"><?php print $urls['otherservice']; ?></a></li>
					   <?php endif; ?>
		         	 </ul>
				<?php endif; ?>
			<?php endif; ?>
	</div><!-- end .post -->		 
<?php else: ?>
<?php pb_private(); ?>
<?php endif; ?>
<?php get_footer(); ?>
