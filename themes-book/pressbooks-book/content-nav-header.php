<nav class="nav-container">		
			<!-- Logo -->
			<h2 class="pressbooks-logo"><a href="<?php echo PATH_CURRENT_SITE; ?>"><?php echo get_site_option('site_name'); ?></a></h2>
	
			<!-- Book Title -->
			<h1 class="book-title"><a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>			    			   
   
		
			
		<div class="menu-wrap">
	
			<div class="menu-wrap-inner">
				<a id="right-menu" class="icon-reorder" href="#right-menu"><span class="assistive-text">Menu</span></a>
			</div>

		  <?php get_template_part('content', 'toc'); ?>
		  
		  
		  <div class="overlay"></div>

	    <div id="modal1" class="modal">
	        <p class="closeBtn icon-fontawesome-webfont-1"><span class="assistive-text">Close</span></p>
			<?php get_template_part('content', 'buy'); ?>
	    </div>

		</div> <!-- end .sub-nav-right -->
</nav>
      

    	<?php $metadata = pb_get_book_information(); ?>
		<?php if ( ! empty( $metadata['pb_author'] ) ): ?>
     	<h3 class="book-author"><?php echo $metadata['pb_author']; ?></h3>
 		<?php endif; ?>

