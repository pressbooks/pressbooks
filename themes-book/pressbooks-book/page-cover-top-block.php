<section id="post-<?php the_ID(); ?>" <?php post_class( array( 'top-block', 'clearfix', 'home-post' ) ); ?>>
	
	<?php pb_get_links(false); ?>
	<?php $metadata = pb_get_book_information();?>
	<div class="log-wrap">	<!-- Login/Logout -->
	   <?php if (! is_single()): ?>
	    	<?php if (!is_user_logged_in()): ?>
				<a href="<?php echo wp_login_url(); ?>" class=""><?php _e('login', 'pressbooks'); ?></a>
	   	 	<?php else: ?>
				<a href="<?php echo  wp_logout_url(); ?>" class=""><?php _e('logout', 'pressbooks'); ?></a>
				<?php if (is_super_admin() || is_user_member_of_blog()): ?>
				<a href="<?php echo get_option('home'); ?>/wp-admin"><?php _e('Admin', 'pressbooks'); ?></a>
				<?php endif; ?>
	    	<?php endif; ?>
	    <?php endif; ?>
	</div>   
	<div class="right-block">
		<a href="http://pressbooks.com" class="pressbooks-brand"><img src="<?php bloginfo('template_url'); ?>/images/pressbooks-branding-2x.png" alt="pressbooks-branding" width="186" height="123" /> <span><?php _e('Make your own books on PressBooks', 'pressbooks'); ?></span></a>
	</div>	
						
			<div class="book-info">
				<!-- Book Title -->
				<h1><a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
				
					
				<?php if ( ! empty( $metadata['pb_author'] ) ): ?>
			     	<p class="book-author"><?php echo $metadata['pb_author']; ?></p>
			     	<span class="stroke"></span>
		     	<?php endif; ?>
				
					
				<?php if ( ! empty( $metadata['pb_about_140'] ) ) : ?>
					<p class="sub-title"><?php echo $metadata['pb_about_140']; ?></p>
					<span class="detail"></span>
				<?php endif; ?>						
				
				<?php if ( ! empty( $metadata['pb_about_50'] ) ): ?>
					<p><?php echo pb_decode( $metadata['pb_about_50'] ); ?></p>
				<?php endif; ?>
		
			</div> <!-- end .book-info -->
			
				<?php if ( ! empty( $metadata['pb_cover_image'] ) ): ?>
				<div class="book-cover">
				
						<img src="<?php echo $metadata['pb_cover_image']; ?>" alt="book-cover" title="<?php bloginfo( 'name' ); ?> book cover" />
					
				</div>	
				<?php endif; ?>
				
				<div class="call-to-action-wrap">
					<?php global $first_chapter; ?>
					<div class="call-to-action">
						<a class="btn red" href="<?php global $first_chapter; echo $first_chapter; ?>"><span class="read-icon"></span><?php _e('Read', 'pressbooks'); ?></a>
						
						<?php if ( @array_filter( get_option( 'pressbooks_ecommerce_links' ) ) ) : ?>
						 <!-- Buy -->
							 <a class="btn black" href="<?php echo get_option('home'); ?>/buy"><span class="buy-icon"></span><?php _e('Buy', 'pressbooks'); ?></a>				
						 <?php endif; ?>	
						 
						
					</div> <!-- end .call-to-action -->		
				</div><!--  end .call-to-action-wrap -->
				
			
			
			
	</section> <!-- end .top-block -->