<?php get_header();
if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))):
if ( have_posts() ) : ?>
	<div>
		<h2 class="page-title"><?php printf( esc_html__( 'Search Results for: %s', 'pressbooks' ), '<span>' . get_search_query() . '</span>' ); ?></h2>
		<ul class="search-results">
		<?php while ( have_posts() ) : the_post();
					get_template_part( 'content', 'search' );
				endwhile; ?>
			</ul>
					<div class="nav"><?php the_posts_navigation(); ?></div>
				</div>
				<?php else :
					get_template_part( 'content', 'none' );
				endif; ?>
<?php else: ?>
<?php pb_private(); ?>
<?php endif; ?>
<?php get_footer(); ?>
