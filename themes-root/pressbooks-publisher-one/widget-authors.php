	<?php  $args = array( 'post_type' => '_author', 'orderby' => 'menu_order title', 'order' => 'ASC' );
				$my_query = new WP_Query( $args );
				if ($my_query->have_posts()) : ?>
			<h3 class="widget-title">Authors</h3>
<ul class="sidebar-author-post"><?php while ( $my_query->have_posts() ) :  ?><?php $my_query->the_post(); ?> 
		
   <li><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></li>

   <?php endwhile; ?></ul><?php endif; ?>
    <?php wp_reset_postdata(); ?>