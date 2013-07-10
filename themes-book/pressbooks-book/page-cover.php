<?php
	get_header();
	$metadata = pb_get_book_information();
	if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))):
	if (have_posts()) the_post();
?>


	<?php get_template_part('page-cover', 'top-block'); ?>
	<?php get_template_part('page-cover', 'second-block'); ?>
	<?php get_template_part('page-cover', 'third-block'); ?>


		
			
<?php else: ?>

	<?php get_template_part('page-cover', 'private-block'); ?>


<?php endif; ?>

<?php get_footer(); ?>
