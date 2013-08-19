<?php

if ( ! defined( 'ABSPATH' ) )
	exit;

use \PressBooks\Image as PB_Image;
use \PressBooks\Catalog as PB_Catalog;

// TODO: Move logic out of the template

// -------------------------------------------------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------------------------------------------------

/**
 * Get catalog logo
 *
 * @param array $profile
 *
 * @return string
 */
function _logo_url( $profile ) {

	if ( empty( $profile['pb_catalog_logo'] ) )
		return PB_PLUGIN_URL . 'assets/images/default-book-cover-100x100.jpg';

	elseif ( PB_Image\is_default_cover( $profile['pb_catalog_logo'] ) )
		return PB_PLUGIN_URL . 'assets/images/default-book-cover-100x100.jpg';

	else
		return PB_Catalog::thumbnailFromUserId( $profile['users_id'], 'thumbnail' );
}


/**
 * Try to get the height of cover using the image name
 * Ie. http://blah/foobar-225x126.jpg would return 126
 *
 * @param string $cover_url
 *
 * @return int
 */
function _cover_height( $cover_url ) {

	$cover_height = 300;

	if ( preg_match( '/x(\d+)(?=\.(jp?g|png|gif)$)/i', $cover_url, $matches ) ) {
		$new_cover_height = (int) $matches[1];
		if ( $new_cover_height < 100 ) $new_cover_height = $cover_height;
		elseif ( $new_cover_height > $cover_height ) $new_cover_height = $cover_height;
	}

	return isset( $new_cover_height ) ? $new_cover_height : $cover_height;
}


/**
 * Get book data
 * Sort by featured DESC, title ASC
 *
 * @param PB_Catalog $catalog
 *
 * @return array
 */
function _books( PB_Catalog $catalog ) {

	$books = $catalog->getAggregate();

	foreach ( $books as $key => $val ) {
	
		// Deleted
		if ( $val['deleted'] ) {
			unset ( $books[$key] );
			continue;
		}

		// Calculate cover height
		$books[$key]['cover_height'] = _cover_height( $val['cover_url']['pb_cover_medium'] );
	}

	return \PressBooks\Utility\multi_sort( $books, 'featured:desc', 'title:asc' );
}

/**
 * Get tags for classes
 *
 * @param array $book
 *
 * @return string
 */
function _tag_classes( $book ) {
	
	$classes = ' ';
	foreach ( $book["tag_1"] as $tag ) {
		$classes .= $tag["id"] . ' ';
	}
	foreach ( $book["tag_2"] as $tag ) {
		$classes .= $tag["id"] . ' ';
	}
	return $classes;
	
}


/**
 * Get base url
 *
 * @global int $_current_user_id
 *
 * @return string
 */
function _base_url() {

	static $base_url = false; // Cheap cache
	if ( false === $base_url ) {
		global $_current_user_id;
		$base_url = get_userdata( $_current_user_id )->user_login;
		$base_url = network_site_url( "/catalog/$base_url" );
	}

	return $base_url;
}

// -------------------------------------------------------------------------------------------------------------------
// Variables
// -------------------------------------------------------------------------------------------------------------------

$base_href = PB_PLUGIN_URL . 'themes-root/pressbooks-publisher-one/';
$catalog = new PB_Catalog( absint( $pb_user_id ) ); // Note: $pb_user_id is set in PB_Catalog::loadTemplate()
$profile = $catalog->getProfile();
$books = _books( $catalog );

// Private! Don't touch
global $_current_user_id;
$_current_user_id = $catalog->getUserId();

// -------------------------------------------------------------------------------------------------------------------
// HTML
// -------------------------------------------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<!-- paulirish.com/2008/conditional-stylesheets-vs-css-hacks-answer-neither/ -->
<!--[if lt IE 7 ]> <html <?php language_attributes(); ?> class="no-js ie6"> <![endif]-->
<!--[if IE 7 ]>    <html <?php language_attributes(); ?> class="no-js ie7"> <![endif]-->
<!--[if IE 8 ]>    <html <?php language_attributes(); ?> class="no-js ie8"> <![endif]-->
<!--[if IE 9 ]>    <html <?php language_attributes(); ?> class="no-js ie9"> <![endif]-->
<!--[if (gt IE 9)|!(IE)]><!--><html <?php language_attributes(); ?> class="no-js"> <!--<![endif]-->
<head>
	<base href="<?php echo $base_href; ?>" />
	<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<link rel="shortcut icon" href="<?php bloginfo('stylesheet_directory'); ?>/favicon.ico" />
	<title><?php _e( 'Catalog Page', 'pressbooks' ); ?> | PressBooks</title>
	<link rel="stylesheet" type="text/css" href="style-catalog.css?ver=2" />
	<link href='<?php echo \PressBooks\Sanitize\maybe_https( 'http://fonts.googleapis.com/css?family=Oswald|Open+Sans:400,400italic,600' ); ?>' rel='stylesheet' type='text/css'>
	<script type="text/javascript" src="<?php echo network_site_url( '/wp-includes/js/jquery/jquery.js?ver=1.10.2' ); ?>"></script>
	<script src="<?php echo PB_PLUGIN_URL; ?>symbionts/jquery/jquery.equalizer.min.js?ver=1.2.3" type="text/javascript"></script>
	<script src="<?php echo PB_PLUGIN_URL; ?>symbionts/jquery/jquery.mixitup.min.js?ver=1.5.4" type="text/javascript"></script>
	<script src="js/small-menu.js?ver=0.0.1" type="text/javascript"></script>
	<?php if ( function_exists( 'ga_mu_plugin_add_script_to_head' ) ) ga_mu_plugin_add_script_to_head(); ?>
</head>
<body>

<div class="catalog-wrap">
		<div class="log-wrap">	<!-- Login/Logout -->
			<?php if (!is_user_logged_in()): ?>
				<a href="<?php echo wp_login_url(); ?>" class=""><?php _e( 'login', 'pressbooks' ); ?></a>
			<?php else: ?>
				<a href="<?php echo wp_logout_url(); ?>" class=""><?php _e( 'logout', 'pressbooks' ); ?></a>
				<?php
				if ( get_current_user_id() == $pb_user_id || is_super_admin()) {
					$user_info = get_userdata( $pb_user_id );
					$admin_url = get_blogaddress_by_id( $user_info->primary_blog ) . 'wp-admin/index.php?page=pb_catalog';
					if ( is_super_admin() && get_current_user_id() != $pb_user_id ) {
						$admin_url .= "&user_id=$pb_user_id";
					}
					?><a href="<?php echo $admin_url; ?>"><?php _e('Admin', 'pressbooks'); ?></a><?php
				}
				?>
			<?php endif; ?>
		</div> <!-- end .log-wrap -->
	<div id="catalog-sidebar" class="catalog-sidebar">
		<h2 class="pressbooks-logo">
			<a href="<?php echo network_site_url(); ?>">PressBooks</a>
		</h2>
		<p class="tag-menu assistive-text">Menu</p>
		<div class="sidebar-inner-wrap">
			<a href="<?php echo _base_url(); ?>">
			<img class="catalog-logo" src="<?php echo _logo_url( $profile ); ?>" alt="catalog-logo" width="100" height="99" />
			</a>
			<p class="about-blurb"><?php
				if ( ! empty( $profile['pb_catalog_about'] ) )
					echo preg_replace( '/<p[^>]*>(.*)<\/p[^>]*>/i', '$1', $profile['pb_catalog_about'] ); // Make valid HTML by removing first <p> and last </p>
			?></p>
			<br />
	
			<!-- Tags -->
			<?php for ( $i = 1; $i <= 2; ++$i ) : ?>
			<?php $tags = $catalog->getTags( $i, false ); ?>
				<h3><?php echo ( ! empty( $profile["pb_catalog_tag_{$i}_name"] ) ) ? $profile["pb_catalog_tag_{$i}_name"] : __( 'Tag', 'pressbooks' ) . " $i"; ?></h3>
				<ul>
					<?php
					foreach ( $tags as $val ) {
						echo "<li class=\"filter-group-{$i}\" data-filter=\"{$val['id']}\">";
						echo "{$val['tag']}</li>" . "\n";
					}
					?>
				</ul>
			<?php endfor; ?>
		</div><!-- end .sidebar-inner-wra -->	

	</div><!-- end catalog-sidebar -->

	<!-- Books! -->
	<div class="catalog-content-wrap">		
		<div class="catalog-content" id="catalog-content">
			<h1><?php _e( 'Catalog', 'pressbooks' ); ?><span class="filtered-by">, <?php _e( 'filtering by', 'pressbooks' ); ?> </span><span class="current-filters"></span> <span class="clear-filters" style="font-weight:normal;font-size:60%;">[<a class="clear-filters" href="#">x</a>]</span></h1>
	
			
			<!-- Books -->
			<?php foreach ( $books as $b ) : ?>
				<div class="book-data mix<?php echo _tag_classes( $b ); ?>">
	
					<div class="book">
						<p class="book-description"><a href="<?php echo get_site_url( $b['blogs_id'], '', 'http'  ); ?>"><?php echo wp_trim_words( strip_tags( pb_decode( $b['about'] ) ), 50, '...' ); ?><span class="book-link">&rarr;</span></a></p>
						<img src="<?php echo $b['cover_url']['pb_cover_medium']; ?>" alt="book-cover" width="225" height="<?php echo $b['cover_height']; ?>" />
					</div><!-- end .book -->
	
					<div class="book-info">
						<h2><?php echo $b['title']; ?></h2>
	
						<p><a href="<?php echo get_site_url( $b['blogs_id'], '', 'http' ); ?>"><?php echo $b['author']; ?></a></p>
					</div><!-- end book-info -->
	
				</div><!-- end .book-data -->
			<?php
			endforeach;
			?>
			
			<div class="fail-message"><?php _e('Sorry, but no books matched your filtering criteria. Please <a class="clear-filters" href="#">clear your current filters</a> and try again.', 'pressbooks' ); ?></div>
			
			</div>	<!-- end .catalog-content-->
			<div class="footer">
				<p><a href="<?php echo network_site_url(); ?>"><?php _e( 'PressBooks: the CMS for Books.', 'pressbooks' ); ?></a></p>
			</div>
					
		</div>	<!-- end .catalog-content-wrap -->

</div><!-- end .catalog-wrap -->

<script type="text/javascript">
	// <![CDATA[
	jQuery.noConflict();
	jQuery(window).load(function () {
		jQuery('#catalog-content').mixitup({ filterLogic : 'and', layoutMode : 'list' });
		jQuery('#catalog-content').equalizer({ columns: '> div.book-data', min: 350 });
		jQuery('.filter-group-1').click( function () {
			var filter1_id = jQuery(this).attr( 'data-filter' );
			var filter1_name = jQuery(this).text();
			if ( jQuery('.filter-group-2.active').length !== 0 ) {
				var filter2_id = jQuery('.filter-group-2.active').attr( 'data-filter' );
				var filter2_name = jQuery('.filter-group-2.active').text();
			} else {
				var filter2_id = 'all';
				var filter2_name = '';
			}
			jQuery('#catalog-content').mixitup( 'filter', filter1_id + ' ' + filter2_id );
			jQuery('.filter-group-1.active').removeClass( 'active' );
			jQuery(this).addClass( 'active' );
			if ( filter2_name !== '' ) {
				var currentFilters = filter1_name + ', ' + filter2_name;
			} else {
				var currentFilters = filter1_name;
			}
			jQuery('.catalog-content h1 span.current-filters').text( currentFilters );
			jQuery('.catalog-content h1 span.filtered-by').show();
			jQuery('.catalog-content h1 span.current-filters').show();
			jQuery('.catalog-content h1 span.clear-filters').show();
		} );
		jQuery('.filter-group-2').click( function () {
			var filter2_id = jQuery(this).attr( 'data-filter' );
			var filter2_name = jQuery(this).text();
			if ( jQuery('.filter-group-1.active').length !== 0 ) {
				var filter1_id = jQuery('.filter-group-1.active').attr( 'data-filter' );
				var filter1_name = jQuery('.filter-group-1.active').text();
			} else {
				var filter1_id = 'all';
				var filter1_name = '';
			}
			jQuery('#catalog-content').mixitup( 'filter', filter1_id + ' ' + filter2_id );
			jQuery('.filter-group-2.active').removeClass( 'active' );
			jQuery(this).addClass( 'active' );
			if ( filter1_name !== '' ) {
				var currentFilters = filter1_name + ', ' + filter2_name;
			} else {
				var currentFilters = filter2_name;
			}
			jQuery('.catalog-content h1 span.current-filters').text( currentFilters );
			jQuery('.catalog-content h1 span.filtered-by').show();
			jQuery('.catalog-content h1 span.current-filters').show();
			jQuery('.catalog-content h1 span.clear-filters').show();
		} );
		jQuery('a.clear-filters').click(function (e) {
			jQuery('.filter-group-1.active').removeClass( 'active' );
			jQuery('.filter-group-2.active').removeClass( 'active' );
			jQuery('#catalog-content').mixitup( 'filter', 'all' );
			jQuery('.catalog-content h1 span.filtered-by').hide();
			jQuery('.catalog-content h1 span.clear-filters').hide();
			jQuery('.catalog-content h1 span.current-filters').text( '' );
		    e.preventDefault();
		});
	});
	// ]]>
</script>

<?php wp_footer(); ?>
</body>
</html>