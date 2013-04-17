<?php
get_header();
if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))):
if (have_posts()) the_post(); ?>
			<div id="post-<?php the_ID(); ?>" <?php post_class('author-block-wrap'); ?>>
				<h2 class="page-title"><?php _e('Authors', 'pressbooks'); ?></h2>
				
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
				
				<!-- Author page info displayed if populated in Admin area -->
					<?php foreach ($authors as $author): ?>
					<div class="author-block">
					<h3 class="author-name"><?php echo $author->post_title;?></h3>
					
					<!-- Author Bio -->
					<div class="bio">
						<?php $the_content = apply_filters('the_content', $author->post_content); ?>
					  <?php echo $the_content; ?>
					</div>
				</div>
				<?php endforeach; ?>
			</div><!-- #post-## -->
		
<?php else: ?>
<?php pb_private(); ?>
<?php endif; ?>
<?php get_footer(); ?>
