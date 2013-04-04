<?php get_header(); ?>

	
<div id="primaryContent">

 	<?php get_template_part( 'loop'); ?>


 <div class="pagination-older"><?php next_posts_link(__('Older Entries &raquo;', 'pressbooks' )); ?></div>
 <div class="pagination-newer"><?php previous_posts_link(__('&laquo; Newer Entries', 'pressbooks' )); ?></div>

</div><!-- end .primaryContent -->

				
<?php get_sidebar(); ?>

<?php get_footer(); ?>