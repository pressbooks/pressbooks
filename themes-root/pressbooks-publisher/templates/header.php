<header class="banner">
  <div class="container">
    <div class="link-wrap">
		<?php if ( is_user_logged_in() ) {
			if ( is_super_admin() || is_user_member_of_blog() ) { ?>
				<a href="<?php echo get_option( 'home' ); ?>/wp-admin" class="btn btn-primary btn-sm"><?php _e( 'Admin', 'pressbooks' ); ?></a>
			<?php }
			$user_info = get_userdata( get_current_user_id() );
			if ( $user_info->primary_blog ) { ?>
				<a href="<?php echo get_blogaddress_by_id( $user_info->primary_blog ); ?>wp-admin/index.php?page=pb_catalog" class="btn btn-primary btn-sm"><?php _e( 'My Books', 'pressbooks' ); ?></a>
			<?php } ?>
			<a href="<?php echo wp_logout_url(); ?>" class="btn btn-primary btn-sm"><?php _e( 'Sign Out', 'pressbooks' ); ?></a>
		<?php } ?>
		</div>
		<div class="logo"><?php the_custom_logo(); ?></div>
		<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
		<h2><?php bloginfo( 'description' ); ?></h2>
		<?php if ( get_theme_mod( 'pressbooks_publisher_intro_textbox' ) !== '' ) { ?>
			<?php if ( 'one-column' == get_theme_mod( 'pressbooks_publisher_intro_text_col' ) ) { ?>
			<div class="intro-text one-column">
			<?php } elseif ( 'two-column' == get_theme_mod( 'pressbooks_publisher_intro_text_col' ) ) { ?>
			<div class="intro-text two-column">
			<?php } ?>
				<?php echo get_theme_mod( 'pressbooks_publisher_intro_textbox' ); ?>
			</div>
		<?php } ?>
		<?php if ( ! is_user_logged_in() ) { ?>
			<div class="login-block">
			<?php if ( class_exists( '\PressbooksOAuth\OAuth' ) ) {
				do_action( 'pressbooks_oauth_connect' );
				}	else { ?>
				<a href="<?php echo wp_login_url( get_option( 'home' ) ); ?>" class="button"><?php _e( 'Sign In', 'pressbooks' ); ?></a>
				<?php if ( get_option( 'users_can_register' ) ) { ?>
					<a class="button" href="<?php echo esc_url( wp_registration_url() ); ?>"><?php _e( 'Register' ); ?></a>
				<?php }
			} ?>
			</div>
		<?php } ?>
	</div>
</header>
