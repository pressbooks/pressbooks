<?php

  get_header();

  if (have_posts()) : while (have_posts()) : the_post();
  ?>
<div id="primaryContent">
   <div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	    <div class="post-inner-wrap">
	    
      <h1 class="postTitle"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h1>
       
      <div class="post-content"><?php the_content(__('keep reading', 'pressbooks')); ?></div>
      <?php wp_link_pages('before=<p class="page-link">&after=</p>&next_or_number=number&pagelink=page %'); ?>
      
    </div> <!-- end .post-inner-warp -->
   </div><!-- end .post -->
	 

  <?php
  

  endwhile; else: ?>

    <p>Sorry, no pages matched your criteria.</p>

<?php   endif; ?>
				
</div><!-- end .primaryContent -->

				
<?php get_sidebar(); ?>
<?php get_footer(); ?>