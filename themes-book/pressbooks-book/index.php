<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

get_header();
$book = pb_get_book_structure();
?>
<?php if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))): ?>
	<?php if (have_posts()) the_post(); ?>
	<div id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<h2 class="page-title"><?php _e('Table of Contents', 'pressbooks'); ?></h2>
	<!-- Table of content loop goes here. -->
	<ul id="inline-toc">

		<li>
			<ul>
			<?php foreach ($book['front-matter'] as $fm) : ?>
				<?php if ( $fm['post_status'] !== 'publish' ) {
					if ( current_user_can_for_blog( $blog_id, 'read' ) ) {
						if ( absint( get_option( 'permissive_private_content' ) ) !== 1 ) continue; // Skip
					} elseif ( !current_user_can_for_blog( $blog_id, 'read' ) ) {
						 continue; // Skip
					}
				} ?>
				<li class="front-matter <?php echo pb_get_section_type( get_post($fm['ID']) ) ?>"><a href="<?php echo get_permalink($fm['ID']); ?>"><?php echo $fm['post_title']; ?></a></li>
			<?php endforeach; ?>
			</ul>
		</li>

		<?php foreach ($book['part'] as $part) : ?>
			<li><h4><?php if ( count( $book['part'] ) > 1 ) echo $part['post_title']; ?></h4><ul>
			<?php foreach ($part['chapters'] as $chapter) : ?>
				<?php if ( $chapter['post_status'] !== 'publish' ) {
					if ( current_user_can_for_blog( $blog_id, 'read' ) ) {
						if ( absint( get_option( 'permissive_private_content' ) ) !== 1 ) continue; // Skip
					} elseif ( !current_user_can_for_blog( $blog_id, 'read' ) ) {
						 continue; // Skip
					}
				} ?>
				<li class="chapter <?php echo pb_get_section_type( get_post($chapter['ID']) ) ?>"><a href="<?php echo get_permalink($chapter['ID']); ?>"><?php echo $chapter['post_title']; ?></a></li>
			<?php endforeach; ?>
			</ul></li>
		<?php endforeach; ?>

		<li>
			<ul>
				<?php foreach ($book['back-matter'] as $bm) : ?>
				<?php if ( $bm['post_status'] !== 'publish' ) {
					if ( current_user_can_for_blog( $blog_id, 'read' ) ) {
						if ( absint( get_option( 'permissive_private_content' ) ) !== 1 ) continue; // Skip
					} elseif ( !current_user_can_for_blog( $blog_id, 'read' ) ) {
						 continue; // Skip
					}
				} ?>
				<li class="back-matter <?php echo pb_get_section_type( get_post($bm['ID']) ) ?>"><a href="<?php echo get_permalink($bm['ID']); ?>"><?php echo $bm['post_title']; ?></a></li>
				<?php endforeach; ?>
			</ul>
		</li>

	</ul>
	</div><!-- #post-## -->
<?php else: ?>
	<?php pb_private(); ?>
<?php endif; ?>
<?php get_footer(); ?>
