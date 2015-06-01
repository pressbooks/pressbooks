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
	<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'Skip to content', 'pressbooks-publisher' ); ?></a>
	
	<header id="masthead" class="site-header" role="banner">
		
		<div class="site-width">

		  <!-- Login/Logout -->
		  <div class="link-wrap">
			   <?php if (! is_single()): ?>
			    	<?php if (!is_user_logged_in()): ?>
						<a href="<?php echo wp_login_url(); ?>" class="site-login-btn"><?php _e('Sign in', 'pressbooks-publisher'); ?></a>
			   	 	<?php else: ?>
						<a href="<?php echo  wp_logout_url(); ?>" class="site-login-btn"><?php _e('Sign out', 'pressbooks-publisher'); ?></a>
						<?php if (is_super_admin() || is_user_member_of_blog()): ?>
						<a href="<?php echo get_option('home'); ?>/wp-admin" class="site-login-btn"><?php _e('Admin', 'pressbooks-publisher'); ?></a>
						<?php endif; ?>
			    	<?php endif; ?>
			    <?php endif; ?>
		   </div>
	    		
			<?php /* Site logo*/ ?>
			<?php if ( function_exists( 'jetpack_the_site_logo' ) ) jetpack_the_site_logo(); ?>
			
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
