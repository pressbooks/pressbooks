<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */

if ( ! isset( $content_width ) )
	$content_width  = '670';
	
/**
* Remove extraneous menus
**/

if ( get_current_blog_id()==1 ) {
	add_action( 'admin_menu', 'pb_root_remove_menu_pages' );
}

function pb_root_remove_menu_pages() {
	remove_menu_page("link-manager.php");
	remove_submenu_page("tools.php","pressbooks-import");
}

/**
* Load the Theme Options Page that lets users control the social media icons at the top
*/
require_once ( get_template_directory() . '/inc/theme-options.php' );


/**
* Thumbnail support
**/

add_theme_support( 'post-thumbnails' );
set_post_thumbnail_size( 186, 9999); // 186 pixels wide by unlimited pixels tall, no hard crop mode
add_image_size( 'post-image', 186, 9999 ); 
add_image_size( 'featured', 330, 9999 ); 
add_image_size( 'sidebar-cover', 100, 150, true);
add_image_size( 'full-page-thumb', 155, 233, true);


/**
 * Enqueue scripts and styles
 */
function publisherroot_scripts() {

	if ( is_archive('_author') || is_page('books') ) {
		wp_enqueue_script( 'equal-height', get_template_directory_uri() . '/js/jquery.equalheights.js', array( 'jquery' ), '20120914', true );
		wp_enqueue_script( 'scripts', get_template_directory_uri() . '/js/scripts.js', array( 'jquery' ), '20120914', true );
	}
	
	
}
add_action( 'wp_enqueue_scripts', 'publisherroot_scripts' );

/**
 * Register widgetized area and update sidebar with default widgets
 */

function publisherroot_widgets_init() {

	register_sidebar(array(
		'id'          => 'sidebar_1',	
    	'name'        => __( 'Sidebar 1', 'pressbooks' ),
    	'before_widget' => '<div id="%1$s" class="%2$s widget">',
   		'after_widget' => '</div>',
    	'before_title' => '<h3 class="widget-title">',
    	'after_title' => '</h3>', ));
    		  	

 register_sidebar(array(
		'id'          => 'sidebar_2',
    	'name'        => __( 'Sidebar 2', 'pressbooks' ),
    	'before_widget' => '<div id="%1$s" class="%2$s widget">',
   		'after_widget' => '</div>',
    	'before_title' => '<h3 class="widget-title">',
    	'after_title' => '</h3>', ));
    		  	

 register_sidebar(array(
		'id'          => 'sidebar_3',
    	'name'        => __( 'Sidebar 3', 'pressbooks' ),
    	'before_widget' => '<div id="%1$s" class="%2$s widget">',
   		'after_widget' => '</div>',
    	'before_title' => '<h3 class="widget-title">',
    	'after_title' => '</h3>', ));
    	
register_sidebar(array(
		'id'          => 'home_left_col',
    	'name'        => __( 'Home Page Left Column', 'pressbooks' ),
    	'before_widget' => '<div id="%1$s" class="%2$s widget">',
   		'after_widget' => '</div>',
    	'before_title' => '<h2 class="widget-title">',
    	'after_title' => '</h2>', ));    		  	

register_sidebar(array(
		'id'          => 'home_right_col',
    	'name'        => __( 'Home Page Right Column', 'pressbooks' ),
    	'before_widget' => '<div id="%1$s" class="%2$s widget">',
   		'after_widget' => '</div>',
    	'before_title' => '<h2 class="widget-title">',
    	'after_title' => '</h2>', ));    		  	

register_sidebar(array(
		'id'          => 'footer_content',
    	'name'        => __( 'Footer', 'pressbooks' ),
    	'before_widget' => '<div id="%1$s" class="%2$s widget">',
   		'after_widget' => '</div>',
    	'before_title' => '<h3 class="widget-title">',
    	'after_title' => '</h3>', ));    		  	

}

 
add_action( 'init', 'publisherroot_widgets_init' );    	


/**
* Add Menu Support
**/

add_theme_support('automatic-feed-links');
register_nav_menu('main', 'Main Nav');



/**
* Enqueue Google font API for front end only fonts
**/
function publisherroot_enqueue_styles() {
   		 wp_enqueue_style( 'publisherroot-fonts', 'http://fonts.googleapis.com/css?family=PT+Serif:400,700');  		   		   		       		           
}     
add_action('wp_print_styles', 'publisherroot_enqueue_styles'); 



/* Add Custom Login Graphic TODO: Import user customized logo here if available */
add_action('login_head', create_function('', 'echo \'<link rel="stylesheet" type="text/css" href="'. PB_PLUGIN_URL .'assets/css/colors-pb.css" media="screen" />\';'));

/* Change login logo URL */
function custom_login_url($url) {
	    return get_bloginfo( 'url' );
}	    
add_filter( 'login_headerurl', 'custom_login_url' );




/**
* Thumbnail support
**/

add_theme_support( 'post-thumbnails' );  
set_post_thumbnail_size( 670, 370, true ); // 670 pixels wide by ??? pixels tall, hard crop mode

// Enqueue Comments

if ( is_singular() ) wp_enqueue_script( "comment-reply" ); 




/**
* Change excerpt [...] to something else
**/

function publisherroot_new_excerpt_more($more) {
    global $post;
	return ' ... <br /><a class="more-link" href="'. get_permalink($post->ID) . __('">read more &#8594;</a>', 'pressbooks');
}
add_filter('excerpt_more', 'publisherroot_new_excerpt_more');


/**
* Change Excerpt length
**/
function publisherroot_new_excerpt_length($length) {
	return 25;
}
add_filter('excerpt_length', 'publisherroot_new_excerpt_length');

/**
* Custom Post Types: Featured Content & Authors
*
**/

add_action( 'admin_head', 'publisherroot_admin_head' );

function publisherroot_admin_head() {
	?>
	<style type="text/css" media="screen">
		#adminmenu #menu-posts-_author div.wp-menu-image { background-position: -300px -33px; }
		#adminmenu #menu-posts-_author:hover div.wp-menu-image, #adminmenu #menu-posts-_author.wp-menu-open div.wp-menu-image { background-position: -300px -1px; }
		.icon32-posts-_author { background-position: -600px -5px !important; }
	</style>
	<?php
}

add_action( 'init', 'publisherroot_cpt' );

function publisherroot_cpt() {
    $labels = array( 
        'name' => _x( 'Featured Content', '_featured_content' ),
        'singular_name' => _x( 'Featured Content', '_featured_content' ),
        'add_new' => _x( 'Add New', '_featured_content' ),
        'add_new_item' => _x( 'Add New Featured Content', '_featured_content' ),
        'edit_item' => _x( 'Edit Featured Content', '_featured_content' ),
        'new_item' => _x( 'New Featured Content', '_featured_content' ),
        'view_item' => _x( 'View Featured Content', '_featured_content' ),
        'search_items' => _x( 'Search Featured Content', '_featured_content' ),
        'not_found' => _x( 'No Featured Content found', '_featured_content' ),
        'not_found_in_trash' => _x( 'No Featured Content in Trash', '_featured_content' ),
        'parent_item_colon' => _x( 'Parent Featured Content Link:', '_featured_content' ),
        'menu_name' => _x( 'Featured Content', '_featured_content' )
    );
    $args = array( 
        'labels' => $labels,
        'exclude_from_search' => false,
        'hierarchical' => true,
        'supports' => array('title','editor', 'thumbnail', 'page-attributes' ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post',
        'menu_position' => 20,
        'rewrite' => array('slug' => 'featured', 'with_front' => false),
    );
    register_post_type( '_featured_content', $args );
    $labels = array( 
        'name' => _x( 'Authors', '_author' ),
        'singular_name' => _x( 'Author', '_author' ),
        'add_new' => _x( 'Add New', '_author' ),
        'add_new_item' => _x( 'Add New Author', '_author' ),
        'edit_item' => _x( 'Edit Author', '_author' ),
        'new_item' => _x( 'New Author', '_author' ),
        'view_item' => _x( 'View Author', '_author' ),
        'search_items' => _x( 'Search Authors', '_author' ),
        'not_found' => _x( 'No Authors found', '_author' ),
        'not_found_in_trash' => _x( 'No Authors in Trash', '_author' ),
        'parent_item_colon' => _x( 'Parent Author Link:', '_author' ),
        'menu_name' => _x( 'Authors', '_author' )
    );
    $args = array( 
        'labels' => $labels,
        'exclude_from_search' => false,
        'hierarchical' => true,
        'supports' => array('title','editor', 'thumbnail', 'page-attributes'),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post',
        'menu_position' => 20,
        'rewrite' => array('slug' => 'authors', 'with_front' => false)
    );
    register_post_type( '_author', $args );
	flush_rewrite_rules( false ); // TODO: This needs to be fixed more permanently.
}

?>
