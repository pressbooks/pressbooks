<?php get_header(); ?>
	<div class="full-page">

 <?php  if (have_posts()): ?>

  <?php while (have_posts()) : the_post(); ?>

    		<div id="post-<?php the_ID(); ?>" <?php post_class('full-page-single'); ?>>
    
   
    		        <h2 class="postTitle"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h2>
      
	
			      	<div class="post-content author-single">
				      	
				      	<!-- Thumbnail -->				
				<?php if( has_post_thumbnail() ) { ?>
				<a href="<?php the_permalink(); ?>" rel="bookmark" class="single-post-image"><?php the_post_thumbnail('post-image', array('title' => ''.get_the_title().'' ));  ?></a>
				<?php } else { ?>
				<!-- TODO: images/default-cover.gif doesn't exist? -->
				<a href="<?php the_permalink(); ?>" rel="bookmark" class="single-post-image-default"> <img src="<?php bloginfo('template_directory'); ?>/images/default-cover.gif" alt="<?php the_title(); ?>" width="186" height="280" title="<?php the_title(); ?>" /></a>
				<?php } ?>
				
				      	<?php the_content(__('Read more &raquo;', 'pressbooks')); ?>
				   </div><!-- end .post-content -->
				   <?php wp_link_pages('before=<p class="page-link">&after=</p>&next_or_number=number&pagelink=page %'); ?>
				   <p class="postMeta"><?php edit_post_link(__('Edit', 'pressbooks'), ''); ?></p>
		
				   <hr class="noCss" />
	
	 </div> <!-- end .post_class -->	
		      	<?php endwhile; ?>
	
		    

		     <?php else: ?>

		 <p><?php _e('Sorry, no posts matched your criteria.', 'pressbooks' ); ?></p>

		 <?php endif; ?>
      
</div><!-- end .full-page -->  	

<?php get_footer(); ?>