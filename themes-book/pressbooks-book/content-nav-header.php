<nav class="nav-wrap">		
			<!-- Book Title -->
			<h1 class="book-title"><a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>			    			   
   

			<div class="menu-wrap">
				<!-- Logo -->
				<h2 class="pressbooks-logo"><a href="<?php echo PATH_CURRENT_SITE; ?>"><img src="<?php bloginfo('template_url'); ?>/images/pressbooks-logo.gif" alt="pressbooks-logo" width="42" height="42" /><span class="assistive-text"><?php echo get_site_option('site_name'); ?></span></a></h2>
	
			
				<a id="right-menu" class="icon-menu-list" href="#right-menu"><span class="assistive-text">Menu</span></a>
				

				<?php get_template_part('content', 'toc'); ?>
			</div>	

</nav>
      

