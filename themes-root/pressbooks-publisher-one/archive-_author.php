<?php get_header(); ?>

 
	<div class="full-page">
		<?php global $wp_query;
			$args = array_merge( $wp_query->query_vars, array( 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
			query_posts( $args );  ?>
		<?php  if (have_posts()): ?>
 

			<div class="full-page-header"> 

			    <h2>Authors</h2>
			
			</div>

	
  <?php while (have_posts()) : the_post(); ?>

    		<div id="post-<?php the_ID(); ?>" <?php post_class('full-page-post'); ?>>
    		
    		
    		<!-- Thumbnail -->				
				<?php if( has_post_thumbnail() ) { ?>
				<a href="<?php the_permalink(); ?>" rel="bookmark" class="full-page-image"><?php the_post_thumbnail('full-page-thumb', array('title' => ''.get_the_title().'' ));  ?></a>
				<?php } else { ?>
				<a href="<?php the_permalink(); ?>" rel="bookmark" class="full-page-image"> <img src="<?php bloginfo('template_directory'); ?>/images/default-image-full-page.png" alt="<?php the_title(); ?>" width="155" height="233" title="<?php the_title(); ?>" /></a>
				<?php } ?>	
    		
 
    		        <h2 class="postTitle"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h2>    
	
			      	<div class="post-content">
				      	<?php the_excerpt(__('Read more &raquo;', 'pressbooks')); ?>
				      	
				   </div><!-- end .post-content -->
		
				   <hr class="noCss" />
	 </div> <!-- end .post_class -->	
		      	<?php endwhile; ?>
	
		    

		     <?php else: ?>

		 <div class="full-page-header">
			    <h2>Authors</h2>
		 <p><?php _e('Sorry, no authors could be found.', 'pressbooks' ); ?></p>
		 </div>
		 <?php endif; ?>
        


		 <div class="pagination-older"><?php next_posts_link('Older Entries ') ?></div>
		 <div class=" pagination-newer"><?php previous_posts_link('Newer Entries ') ?></div> 
    
</div><!-- end .full-page -->

	

<?php get_footer(); ?>
