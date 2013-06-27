<?php get_header(); ?>
<?php $metadata = pb_get_book_information(); ?>
<?php if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))): ?>
		<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
		<h2 class="page-title"><?php _e('About The Book', 'pressbooks'); ?></h2>
		
		 <!-- Display About unlimited description first -->
		  <?php  if ( ! empty( $metadata['pb_about_unlimited'] ) ): ?>
          <?php echo $metadata['pb_about_unlimited']; ?>
          
          <!-- if no About unlimited description, set About 50 word description -->
          <?php elseif ( ! empty( $metadata['pb_about_50'] ) ): ?>
          <?php echo $metadata['pb_about_50']; ?>
          
          <!-- if no About 140 word description, set About 140 characters description -->
          <?php elseif ( ! empty( $metadata['pb_about_140'] ) ): ?>
          <?php echo $metadata['pb_about_140']; ?>
          
           <!-- if no About set at all --> 
          <?php else: ?>
           <p><?php _e('It\'s coming!', 'pressbooks'); ?></p>
          
          <?php endif; ?>
			</div><!-- #post-## -->
<?php else: ?>
<?php pb_private(); ?>
<?php endif; ?>
<?php get_footer(); ?>