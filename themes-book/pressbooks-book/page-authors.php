<?php
get_header();
if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))):
if (have_posts()) the_post(); ?>
			<div id="post-<?php the_ID(); ?>" <?php post_class('author-block-wrap'); ?>>
				
				<?php
			  $authors = get_posts(array('post_type' => 'back-matter',
			                             'suppress_filters' => false,
										 'orderby' => 'menu_order',
										 'order' => 'ASC',
			                             'tax_query' => array(
                                   		array(
                                   			'taxonomy' => 'back-matter-type',
                                   			'field' => 'slug',
                                   			'terms' => 'about-the-author'
                                   		)
                                   	)));
			  ?>
				<h2 class="page-title"><?php echo @$authors[0]->post_title; ?></h2>
				<!-- Author page info displayed if populated in Admin area -->
				<?php
				$i = 0;
				foreach ( $authors as $author ): ?>
					<div class="author-block">
						<?php if ( 0 != $i ): ?><h3 class="author-name"><?php echo $author->post_title; ?></h3><?php endif; ?>
						<!-- Author Bio -->
						<div class="bio">
							<?php $the_content = apply_filters( 'the_content', $author->post_content ); ?>
							<?php echo $the_content; ?>
							<?php ++$i; ?>
						</div>
					</div>
				<?php endforeach; ?>
			</div><!-- #post-## -->
		
<?php else: ?>
<?php pb_private(); ?>
<?php endif; ?>
<?php get_footer(); ?>
