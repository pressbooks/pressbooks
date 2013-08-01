<?php get_header();
if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))):
if (have_posts()) the_post(); ?>
			<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
				<?php if (is_front_page()): ?>
					<h2 class="entry-title"><?php the_title(); ?></h2>
				<?php else: ?>
					<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php endif; ?>
				<div class="entry-content">
					<?php edit_post_link( __( 'Edit', 'pressbooks' ), '<span class="edit-link">', '</span>' ); ?>
					<?php the_content(); ?>
					<?php wp_link_pages( array( 'before' => '<div class="page-link">' . __( 'Pages:', 'pressbooks' ), 'after' => '</div>' ) ); ?>
				</div><!-- .entry-content -->
			</div><!-- #post-## -->
<?php else: ?>
<?php pb_private(); ?>
<?php endif; ?>
<?php get_footer(); ?>
