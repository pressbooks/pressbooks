<!DOCTYPE html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ --> 
<!--[if lt IE 7 ]> <html <?php language_attributes(); ?> class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html <?php language_attributes(); ?> class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html <?php language_attributes(); ?> class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html <?php language_attributes(); ?> class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--><html <?php language_attributes(); ?> class="no-js"> <!--<![endif]-->
<head>
<meta charset="<?php bloginfo( 'charset' ); ?>" />
<meta name="viewport" content="width=device-width; initial-scale=1.0">
<link rel="shortcut icon" href="<?php bloginfo('stylesheet_directory'); ?>/favicon.ico" />
<title><?php
	global $page, $paged;
	wp_title( '|', true, 'right' );
	bloginfo( 'name' );

	// Add the blog description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		echo " | $site_description";

	// Add a page number if necessary:
	if ( $paged >= 2 || $page >= 2 )
		echo ' | ' . sprintf( __( 'Page %s', 'pressbooks' ), max( $paged, $page ) );

	?></title>
<link rel="profile" href="http://gmpg.org/xfn/11" />
<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />

<?php wp_head(); ?>

</head>
<body <?php body_class(); if(wp_title('', false) != '') { print ' id="' . str_replace(' ', '', strtolower(wp_title('', false))) . '"'; } ?>>
<!-- Faccebook share js sdk -->
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_US/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<!-- end Facebook JS -->
<div class="nav-container">
<nav>

 		<!-- Book Title -->
	    <h1 class="book-title"><a href="<?php echo home_url( '/' ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></h1>
	    
	   
	    
	    <div class="sub-nav-left">
			<!-- Logo -->
			<h2 class="pressbooks-logo"><a href="<?php echo PATH_CURRENT_SITE; ?>"><?php echo get_site_option('site_name'); ?></a></h2>
	    </div> <!-- end .sub-nav-left -->
	    
	    <div class="sub-nav-right">
	    
	    <?php if ( @array_filter( get_option( 'pressbooks_ecommerce_links' ) ) ) : ?>
	    <!-- Buy -->
	    <div class="buy">
			<a href="<?php echo get_option('home'); ?>/buy" class="button-red"><?php _e('Buy', 'pressbooks'); ?></a>
		</div>
		<?php endif; ?>	
		
		<?php get_template_part( 'content', 'social-header' ); ?> 
		
		</div> <!-- end .sub-nav-right -->
</nav>
	      
	  <div class="sub-nav">       
	     <!-- Author Name -->   
	    <div class="author-wrap"> 
	    	<?php $metadata = pb_get_book_information(); ?>
			<?php if ( ! empty( $metadata['pb_author'] ) ): ?>
	     	<h3><?php echo $metadata['pb_author']; ?></h3>
     		<?php endif; ?>
	     </div> <!-- end .author-name -->
	<iframe class="instapaper" border="0" scrolling="no" width="78" height="17" allowtransparency="true" frameborder="0" style="margin-bottom: -3px; z-index: 1338; border: 0px; background-color: transparent; overflow: hidden;" src="http://www.instapaper.com/e2?url=<?php the_permalink(); ?>&title=<?php the_title( ); ?>"></iframe>			     
	  </div><!-- end sub-nav -->  
	    
		 
</div> <!-- end .nav-container -->

<div class="wrapper"><!-- for sitting footer at the bottom of the page -->	    
	<div id="wrap">	    
		<div id="content">

