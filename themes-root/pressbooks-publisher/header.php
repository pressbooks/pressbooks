<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package Pressbooks Publisher
 */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="profile" href="http://gmpg.org/xfn/11">
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<div id="page" class="hfeed site">
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'pressbooks' ); ?></a>

	<header id="masthead" class="site-header" role="banner">

		<div class="site-width">

		  <!-- Login/Logout -->
		  <div class="link-wrap">
			   <?php if (! is_single()): ?>
			    	<?php if (!is_user_logged_in()): ?>
						<a href="<?php echo wp_login_url( get_option('home') ); ?>" class="site-login-btn"><?php _e('Sign in', 'pressbooks'); ?></a>
			   	 	<?php else: ?>
						<a href="<?php echo  wp_logout_url(); ?>" class="site-login-btn"><?php _e('Sign out', 'pressbooks'); ?></a>
						<?php $user_info = get_userdata( get_current_user_id() ); if ( $user_info->primary_blog ) : ?>
						<a href="<?php $user_info = get_userdata( get_current_user_id() ); echo get_blogaddress_by_id( $user_info->primary_blog ); ?>wp-admin/index.php?page=pb_catalog" class="site-login-btn"><?php _e('My Books', 'pressbooks'); ?></a>
						<?php endif; ?>
						<?php if (is_super_admin() || is_user_member_of_blog()): ?>
						<a href="<?php echo get_option('home'); ?>/wp-admin" class="site-login-btn"><?php _e('Admin', 'pressbooks'); ?></a>
						<?php endif; ?>

			    	<?php endif; ?>
			    <?php endif; ?>
		   </div>

			<?php /* Site logo */ ?>
			<?php pressbooks_publisher_custom_logo(); ?>

			<div class="site-branding">
				<h1 class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
				<h2 class="site-description"><?php bloginfo( 'description' ); ?></h2>


				<?php if( get_theme_mod( 'pressbooks_publisher_intro_textbox' ) !== '' ): ?>

					<?php if ( 'one-column' == get_theme_mod( 'pressbooks_publisher_intro_text_col' ) ) : ?>

						<div class="intro-text one-column">

					<?php elseif ( 'two-column' == get_theme_mod( 'pressbooks_publisher_intro_text_col' ) ) : ?>

						<div class="intro-text two-column">

					<?php endif; ?>

					<?php echo get_theme_mod( 'pressbooks_publisher_intro_textbox' ); ?>

				</div>
				<?php endif; ?>

			</div><!-- .site-branding -->

		</div>
	</header><!-- #masthead -->

	<div id="content" class="site-content">
