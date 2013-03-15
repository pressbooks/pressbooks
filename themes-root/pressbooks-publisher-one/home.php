<?php get_header(); ?>


	<?php get_template_part( 'front', 'featured'); ?>
 
 <div id="primaryContent">
 
 	

  	<?php get_template_part( 'loop'); ?>

  	<div class="pagination-wrap">
 		<div class="pagination-older"><?php next_posts_link(__('&#8592 Older Entries', 'pressbooks' )); ?></div>
 		<div class="pagination-newer"><?php previous_posts_link(__('Newer Entries &#8594;', 'pressbooks' )); ?></div>
 	</div> 
 
   <?php get_template_part( 'front', 'homepage-block'); ?>

</div><!-- end .primaryContent -->

				
<?php get_sidebar(); ?>

<?php get_footer(); ?>