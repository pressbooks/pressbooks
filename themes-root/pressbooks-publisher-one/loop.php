
 <?php  if (have_posts()): ?>

<?php if (is_archive ()){ ?>
			<div id="archives"> 
			  <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
			   <?php /* If this is a category archive */ if (is_category()) { ?>
			    <h2><?php single_cat_title(); ?> </h2>
			   <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
			    <h2>Posts Tagged <?php single_tag_title(); ?></h2>
			   <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
			    <h2>a <?php the_time('F jS, Y'); ?></h2>
			   <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
			    <h2> <?php the_time('F, Y'); ?></h2>
			   <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
			    <h2><?php the_time('Y'); ?></h2>
			   <?php /* If this is an author archive */ } elseif (is_author()) { ?>
			   <?php
					$curauth = (isset($_GET['author_name'])) ? get_user_by('slug', $author_name) : get_userdata(intval($author));
				?>					   
			    <h2>Author Archive <?php echo $curauth->nickname; ?></h2>
			   <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
			    <h2>Blog Archives</h2>
			   <?php } ?>
			</div>
     <?php }
?>
	
  <?php while (have_posts()) : the_post(); ?>

    		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	    		<div class="post-inner-wrap">
   				 <?php if ('_featured_content' == get_post_type() ){ ?>
   				 	<h5 class="category-label">Featured Content</h5>
				 <?php } else { ?>
					<h5 class="category-label"><?php the_category(', ') ?></h5>	
				 <?php } ?>	
   
    		        <h2 class="postTitle"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h2>
      
    		        <p class="theDate"><?php the_time('F j, Y'); ?> by <?php the_author_posts_link(); ?></p>
	
			      	<div class="post-content">
				      	<?php the_post_thumbnail('post-image'); ?>
				      	<?php the_content(__('read more &#8594;', 'pressbooks')); ?>
				   </div><!-- end .post-content -->
				   <?php wp_link_pages('before=<p class="page-link">&after=</p>&next_or_number=number&pagelink=page %'); ?>
				   <p class="postMeta"><span class="cmmnts"><?php comments_popup_link(__('No Comments', '1 Comment', '% Comments', 'pressbooks' )); ?></span><?php if ( get_the_tags () ) : ?> | Tags: <?php the_tags(' '); ?><?php endif; ?>  <?php edit_post_link(__('Edit', 'pressbooks' ), ''); ?></p>
		
				   <hr class="noCss" />
	    		</div><!-- end .post-inner-wrap -->
				   <?php comments_template(); // Get wp-comments.php template ?>
	 </div> <!-- end .post_class -->	
		      	<?php endwhile; ?>
	
		    

		     <?php else: ?>

		 <div class="post"><p><?php _e('Sorry, no posts matched your criteria.', 'pressbooks' ); ?></p></div>

		 <?php endif; ?>
		 
		 <?php if ( is_single ()) { ?>
		     <div class="post-link">		
			     <div class="pagination-newer"><?php next_post_link('%link &#8594;'); ?></div>
			     <div class="pagination-older"><?php previous_post_link('&#8592 %link'); ?></div> 
			 </div>
		<?php } ?>	 
      