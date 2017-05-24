<?php $bloginfourl = get_bloginfo('url'); ?>

	<div <?php post_class(); ?>>
		<h2 class="entry-title denied-title"><?php _e('Access Denied', 'pressbooks'); ?></h2>
		<!-- Table of content loop goes here. -->
		<div class="entry-content denied-text">
			<p><?php printf(
				__( 'This book is private, and accessible only to registered users. If you have an account you can %s.', 'pressbooks' ),
				sprintf(
					'<a href="%1$s">%2$s</a>',
					get_bloginfo( 'url' ) . '/wp-login.php',
					__( 'login here', 'pressbooks' )
				)
			); ?></p>
			<p><?php printf(
				__( 'You can also set up your own Pressbooks book at %s.', 'pressbooks' ),
				sprintf(
					'<a href="%1$s">%2$s</a>',
					apply_filters( 'pb_signup_url', 'https://pressbooks.com' ),
					apply_filters( 'pb_signup_url', 'Pressbooks.com' )
				)
			); ?></p>
		</div>
	</div><!-- #post-## -->
