<?php
get_header();
$metadata = pb_get_book_information();
if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))):
if (have_posts()) the_post();
?>
<?php pb_get_links(false); ?>
<?php global $first_chapter; ?>
			<div id="post-<?php the_ID(); ?>" <?php post_class('home-post'); ?>>
					<?php if ( ! empty( $metadata['pb_cover_image'] ) ): ?>
					<img src="<?php echo $metadata['pb_cover_image']; ?>" alt="" class="featured" />
					<?php endif; ?>
					<?php if ( ! empty( $metadata['pb_about_140'] ) ) : ?>
					<div class="about140"><p><?php echo $metadata['pb_about_140']; ?></p></div>
					<?php endif; ?>
					<?php if ( ! empty( $metadata['pb_about_50'] ) ): ?>
					<div class="about50"><p><?php echo pb_decode( $metadata['pb_about_50'] ); ?></p></div>
					<?php endif; ?>
					<div class="start-reading"><a class="button-black" href="<?php global $first_chapter; echo $first_chapter; ?>"><?php _e('Start reading', 'pressbooks'); ?></a></div>
					
								<?php 
          			  $authors = get_posts(array('post_type' => 'back-matter',
          			                             'suppress_filters' => false,
          			                             'tax_query' => array(
                                             		array(
                                             			'taxonomy' => 'back-matter-type',
                                             			'field' => 'slug',
                                             			'terms' => 'about-the-author'
                                             		)
                                             	)));

      					?>
      					<?php if ($authors): ?>
      					<div class="author-cover">
      					<h4><?php _e('Authors(s)', 'pressbooks'); ?></h4>
      					<?php foreach ($authors as $author): ?>
      						<h3><?php echo $author->post_title; ?></h3>
      						<?php $the_content = apply_filters('the_content', $author->post_content); ?>
      						<?php echo $the_content; ?>
      					 
      				  <?php endforeach; ?>
      				  </div> 
      					<?php endif; ?>

			</div><!-- #post-## -->
<?php else: ?>
<?php pb_private(); ?>
<?php endif; ?>
<?php get_footer(); ?>
