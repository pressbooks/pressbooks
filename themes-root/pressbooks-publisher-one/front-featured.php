<?php  $args = array( 'post_type' => '_featured_content', 'posts_per_page' => 1 );
	   $my_query = new WP_Query( $args );
   if( $my_query->have_posts() ) : while( $my_query->have_posts() ) : $my_query->the_post(); ?> 
					
					
		<div class="featured-content-wrap">
				<div class="featured-content">
				<!-- Thumbnail -->				
				<?php if( has_post_thumbnail() ) { ?>
				<a href="<?php the_permalink(); ?>" rel="bookmark" class="featured-image"><?php the_post_thumbnail('featured-book', array('title' => ''.get_the_title().'' ));  ?></a>
				<?php } else { ?>
				<a href="<?php the_permalink(); ?>" rel="bookmark" class="featured-image"> <img src="<?php bloginfo('template_directory'); ?>/images/default-featured-image.png" alt="<?php the_title(); ?>" width="330" height="285" title="<?php the_title(); ?>" /></a>
				<?php } ?>	
				
				<!-- Post content -->
				<div class="post">
					<h2 class="postTitle"><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h2>
					<!-- Author name -->				
					<?php $featuredsubtitle = get_post_meta($post->ID, 'featured-subtitle', true);
		        	      if($featuredsubtitle) : ?>
		        	      <h4 class="featured-sub-title">by <?php echo $featuredsubtitle ?></h4>
		        	<?php endif; ?>

					<?php the_content(__('Read more &#8594;', 'pressbooks')); ?>

				</div><!-- end .post -->
			</div>	<!-- end .featured-content -->
		</div><!--end .featured-content-wrap -->	
				
		    <?php endwhile; ?>
		    <?php endif; ?>
			<?php wp_reset_postdata(); ?>