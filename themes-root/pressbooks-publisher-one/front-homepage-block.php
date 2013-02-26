	<?php  $args = array( 'category_name' => 'shorts-excerpts', 'posts_per_page' => 1 );
				       $my_query = new WP_Query( $args );
				   if( $my_query->have_posts() ) : while( $my_query->have_posts() ) : $my_query->the_post(); ?> 
				
				<div class="homepage-block-left-warp homepage-block">
					<?php if (is_active_sidebar( 'home_left_col')): ?>
				 	    <?php dynamic_sidebar( 'home_left_col' ); ?>
				 	<?php endif; ?>
					<h5 class="homepage-category-label"><?php the_category(', ') ?></h5>	
					<div class="post">
						<h2><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h2>
						<p class="theDate"><?php the_time('F j, Y'); ?> by <?php the_author_posts_link(); ?></p>
		
					      	<?php the_excerpt(__('read more &#8594;', 'pressbooks')); ?>
			        </div><!-- end .post -->
		       
		        </div> <!-- end .book-shorts-excerpts-warp -->
		        <?php endwhile;  ?>
		        <?php endif; ?> 
				<?php wp_reset_postdata(); ?>
				
				
				<?php  $args = array( 'category_name' => 'reviews-press', 'posts_per_page' => 1 );
					   $my_query = new WP_Query( $args );
				   if( $my_query->have_posts() ) : while( $my_query->have_posts() ) : $my_query->the_post(); ?> 
				
				<div class="homepage-block-right-warp homepage-block">
					<?php if (is_active_sidebar( 'home_right_col')): ?>
				 	    <?php dynamic_sidebar( 'home_right_col' ); ?>
				 	<?php endif; ?>
					<h5 class="homepage-category-label"><?php the_category(', '); ?></h5>	
					<div class="post">
						<h2><a href="<?php the_permalink() ?>" rel="bookmark"><?php the_title(); ?></a></h2>
						<p class="theDate"><?php the_time('F j, Y'); ?> by <?php the_author_posts_link(); ?></p>
	
				      	<?php the_excerpt(__('read more &#8594;', 'pressbooks')); ?>
				      </div><!-- end .post -->
		       
		        </div> <!-- end .book-reviews-press-warp -->
		        <?php endwhile;  ?>
		        <?php endif; ?>
				<?php wp_reset_postdata(); ?>