<?php global $blog_id; ?>
	<?php if (get_option('blog_public') == '1' || (get_option('blog_public') == '0' && current_user_can_for_blog($blog_id, 'read'))): ?>

	<div id="sidebar">
	<!-- Login/Logout -->
	   <?php if (! is_single()): ?>
	    	<?php if (!is_user_logged_in()): ?>
			<div class="log-wrap">
				<a href="<?php echo wp_login_url(); ?>" class="log login"><?php _e('Login', 'pressbooks'); ?></a>
			</div>
	   	 	<?php else: ?>
			<div class="log-wrap">
				<a href="<?php echo  wp_logout_url(); ?>" class="log logout"><?php _e('Logout', 'pressbooks'); ?></a>
			</div>
	    	<?php endif; ?>
	    <?php endif; ?>
		<ul id="booknav">
		<!-- If Logged in show ADMIN -->
			<?php global $blog_id; ?>
			<?php if (current_user_can_for_blog($blog_id, 'edit_posts') || is_super_admin()): ?>
				<li class="admin-btn"><a href="<?php echo get_option('home'); ?>/wp-admin"><?php _e('Admin', 'pressbooks'); ?></a></li>
			<?php endif; ?>

		<!-- If READ pages, show these menu items -->
			<?php if (!is_single()): ?>
				<li class="read-btn"><a href="<?php pb_get_links(false); global $first_chapter; echo $first_chapter; ?>"><?php _e('Read', 'pressbooks'); ?></a></li>
				<li class="about-btn"><a href="<?php echo get_option('home'); ?>/about"><?php _e('About the Book', 'pressbooks'); ?></a></li>
				<li class="authors-btn"><a href="<?php echo get_option('home'); ?>/authors"><?php _e('About the Author(s)', 'pressbooks'); ?></a></li>
			<?php else: ?>

		<!-- Otherwise just show HOME button -->
				<li class="home-btn"><a href="<?php echo get_option('home'); ?>"><?php _e('Home', 'pressbooks'); ?></a></li>
			<?php endif; ?>

		<!-- TOC button always there -->
				<li class="toc-btn"><a href="<?php echo get_option('home'); ?>/table-of-contents"><?php _e('Table of Contents', 'pressbooks'); ?></a></li>
			</ul>

		<!-- Pop out TOC only on READ pages -->
		<?php if (is_single()): ?>
		<?php $book = pb_get_book_structure(); ?>
		<div id="toc">
			<a href="#" class="close"><?php _e('Close', 'pressbooks'); ?></a>
			<ul>
				<li><h4><!-- Front-matter --></h4></li>
				<ul>
					<?php foreach ($book['front-matter'] as $fm): ?>
					<?php if ($fm['post_status'] != 'publish') continue; // Skip ?>
					<li><a href="<?php echo get_permalink($fm['ID']); ?>"><?php echo $fm['post_title'];?></a></li>
					<?php endforeach; ?>
				</ul>
				<?php foreach ($book['part'] as $part):?>
				<li><h4><?php if ( count( $book['part'] ) > 1 ) echo $part['post_title']; ?></h4></li>
				<ul>
					<?php foreach ($part['chapters'] as $chapter) : ?>
						<?php if ($chapter['post_status'] != 'publish') continue; // Skip ?>
						<li><a href="<?php echo get_permalink($chapter['ID']); ?>"><?php echo $chapter['post_title']; ?></a></li>
					<?php endforeach; ?>
				</ul>
				<?php endforeach; ?>
				<li><h4><!-- Back-matter --></h4></li>
				<ul>
					<?php foreach ($book['back-matter'] as $fm): ?>
					<?php if ($fm['post_status'] != 'publish') continue; // Skip ?>
					<li><a href="<?php echo get_permalink($fm['ID']); ?>"><?php echo $fm['post_title'];?></a></li>
					<?php endforeach; ?>
				</ul>
			</ul>
		</div><!-- end #toc -->
		<?php endif; ?>


	</div><!-- end #sidebar -->
	<?php endif; ?>
