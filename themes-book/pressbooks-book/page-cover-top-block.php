	
		<section class="top-block clearfix" id="post-<?php the_ID(); ?>" <?php post_class('home-post'); ?>>
	
			<?php pb_get_links(false); ?>
			<?php $metadata = pb_get_book_information();?>
			<div class="right-block">
				<a href="http://pressbooks.com" class="pressbooks-brand"><img src="<?php bloginfo('template_url'); ?>/images/pressbooks-branding.png" alt="pressbooks-branding" width="186" height="123" /></a>
				<?php if ( ! empty( $metadata['pb_cover_image'] ) ): ?>
					<div class="book-cover">
						<span class="spine"></span>
						<img src="<?php echo $metadata['pb_cover_image']; ?>" alt="book-cover" title="<?php bloginfo( 'name' ); ?> book cover" />
					
					</div>	
				<?php endif; ?>		
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
				
				<?php global $first_chapter; ?>
				<div class="call-to-action">
					<a class="btn red" href="<?php global $first_chapter; echo $first_chapter; ?>"><span class="read-icon"></span><?php _e('Read', 'pressbooks'); ?></a>
					
					<?php if ( @array_filter( get_option( 'pressbooks_ecommerce_links' ) ) ) : ?>
					 <!-- Buy -->
						 <a class="btn black" href="<?php echo get_option('home'); ?>/buy"><span class="buy-icon"></span><?php _e('Buy', 'pressbooks'); ?></a>				
					 <?php endif; ?>	
					 
					
				</div> <!-- .call-to-action -->		
			
			</div>
			
			
	</section> <!-- end .top-block -->