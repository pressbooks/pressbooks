<?php
/**
 * Pressbooks Publisher functions and definitions
 *
 * @package Pressbooks Publisher
 */

if ( ! function_exists( 'pressbooks_publisher_setup' ) ) :
/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function pressbooks_publisher_setup() {

	/*
	 * Make theme available for translation.
	 * Translations can be filed in the /languages/ directory.
	 * If you're building a theme based on Pressbooks Publisher, use a find and replace
	 * to change 'pressbooks-publisher' to the name of your theme in all the template files
	 */
	load_theme_textdomain( 'pressbooks', PB_PLUGIN_DIR . 'languages' );

	// Add default posts and comments RSS feed links to head.
	add_theme_support( 'automatic-feed-links' );

	/*
	 * Let WordPress manage the document title.
	 * By adding theme support, we declare that this theme does not use a
	 * hard-coded <title> tag in the document head, and expect WordPress to
	 * provide it for us.
	 */
	add_theme_support( 'title-tag' );

	/*
	 * Enable support for Post Thumbnails on posts and pages.
	 *
	 * @link http://codex.wordpress.org/Function_Reference/add_theme_support#Post_Thumbnails
	 */
	add_theme_support( 'post-thumbnails' );
	add_image_size( 'pressbooks-publisher-site-logo', 99999, 55, false);
	add_image_size( 'pressbooks-publisher-book-cover', 500, 650, true);

	/*
	 * Switch default core markup for search form, comment form, and comments
	 * to output valid HTML5.
	 */
	add_theme_support( 'html5', array(
		'search-form', 'comment-form', 'comment-list', 'gallery', 'caption',
	) );

	/**
	 * Migrate Jetpack Site Logo to core custom logo.
	 */
	
	if ( get_option( 'site_logo' ) ) {
		$site_logo = get_option( 'site_logo' );
		if ( isset( $site_logo['id'] ) ) {
			set_theme_mod( 'custom_logo', $site_logo['id'] );
			delete_option( 'site_logo' );
		}
	}

	/**
	 * Enable support for custom logo.
	 */
	add_theme_support( 'custom-logo', array( 'size' => 'pressbooks-publisher-site-logo' ) );

}
endif; // pressbooks_publisher_setup
add_action( 'after_setup_theme', 'pressbooks_publisher_setup' );

/* * *************************************************************
 *
 * Build Google font url based on
 * http://themeshaper.com/2014/08/13/how-to-add-google-fonts-to-wordpress-themes/
 *
 * ************************************************************* */
function pressbooks_publisher_fonts_url() {
	$fonts_url = '';

	/* Translators: If there are characters in your language that are not
    * supported by Oswald, translate this to 'off'. Do not translate
    * into your own language.
    */
	$oswald = _x( 'on', 'Oswald font: on or off', 'pressbooks' );

	/* Translators: If there are characters in your language that are not
    * supported by Droid Serif, translate this to 'off'. Do not translate
    * into your own language.
    */
	$droid_serif = _x( 'on', 'Droid Serif font: on or off', 'pressbooks' );

	/* Translators: If there are characters in your language that are not
    * supported by Droid Sans, translate this to 'off'. Do not translate
    * into your own language.
    */
	$droid_sans = _x( 'on', 'Droid Sans font: on or off', 'pressbooks' );

	if ( 'off' !== $oswald || 'off' !== $droid_serif || 'off' !== $droid_sans ) {
		$font_families = array();

		if ( 'off' !== $oswald ) {
			$font_families[] = 'Oswald';
		}

		if ( 'off' !== $droid_serif ) {
			$font_families[] = 'Droid+Serif:400,400italic,700';
		}

		if ( 'off' !== $droid_sans ) {
			$font_families[] = 'Droid+Sans';
		}

		$query_args = array(
			'family' => urlencode( implode( '|', $font_families ) ),
			'subset' => urlencode( 'latin,latin-ext' ),
		);

		$fonts_url = add_query_arg( $query_args, '//fonts.googleapis.com/css' );
	}

	return $fonts_url;
}


 /* * *************************************************************
 *
 * Enqueue Google font on front end
 *
 * ************************************************************* */
function pressbooks_publisher_scripts_styles() {
	wp_enqueue_style( 'pressbooks-publisher-fonts', pressbooks_publisher_fonts_url(), array(), null );
}
add_action( 'wp_enqueue_scripts', 'pressbooks_publisher_scripts_styles' );


/**
 * Set the content width in pixels, based on the theme's design and stylesheet.
 *
 * Priority 0 to make it available to lower priority callbacks.
 *
 * @global int $content_width
 */
function pressbooks_publisher_content_width() {
	$GLOBALS['content_width'] = apply_filters( 'pressbooks_publisher_content_width', 640 );
}
add_action( 'after_setup_theme', 'pressbooks_publisher_content_width', 0 );


/**
 * Enqueue scripts and styles.
 */
function pressbooks_publisher_scripts() {
	wp_enqueue_style( 'pressbooks-publisher-style', get_stylesheet_uri() );

	wp_enqueue_script( 'pressbooks-publisher-navigation', get_template_directory_uri() . '/js/navigation.js', array(), '20120206', true );

	wp_enqueue_script( 'pressbooks-publisher-skip-link-focus-fix', get_template_directory_uri() . '/js/skip-link-focus-fix.js', array(), '20130115', true );

	wp_enqueue_script( 'pressbooks-publisher-match-height', get_template_directory_uri() . '/js/jquery.matchHeight-min.js', array('jquery'), '20150519', true );

	wp_enqueue_script( 'pressbooks-publisher-script', get_template_directory_uri() . '/js/script.js', array(), '20150519', true );

	if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
		wp_enqueue_script( 'comment-reply' );
	}
}
add_action( 'wp_enqueue_scripts', 'pressbooks_publisher_scripts' );

/**
 * Custom template tags for this theme.
 */
require get_template_directory() . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require get_template_directory() . '/inc/extras.php';

/**
 * Customizer additions.
 */
require get_template_directory() . '/inc/customizer.php';

/**
 * Hide the admin bar.
 */
add_filter( 'show_admin_bar', function () { return false; } );

/**
 * Hide sidebar items.
 */

add_action( 'admin_menu', 'pressbooks_publisher_menu', 1 );

function pressbooks_publisher_menu() {

	global $menu, $submenu;

	remove_menu_page( 'index.php' );
	remove_menu_page( 'edit.php' );
	remove_menu_page( 'upload.php' );
	remove_menu_page( 'link-manager.php' );
	remove_menu_page( 'edit.php?post_type=page' );
	remove_menu_page( 'edit-comments.php' );

	remove_submenu_page( 'themes.php', 'themes.php' );
	remove_submenu_page( 'themes.php', 'nav-menus.php' );
	$submenu['themes.php'][6][4] = 'customize-support'; // Fix empty submenu by overriding css. See line ~152 in: ./wp-admin/menu.php

	remove_menu_page( 'plugins.php' );
	remove_menu_page( 'users.php' );
	remove_menu_page( 'tools.php' );
	remove_menu_page( 'options-general.php' );

}

/**
 * Catalog management for Network Admin.
 */

function pressbooks_publisher_admin_scripts($hook) {
    if ( 'sites.php' !== $hook ) {
        return;
    }

    wp_enqueue_script( 'pressbooks-publisher-admin', get_template_directory_uri() . '/js/catalog-admin.js', array('jquery'), '20150527' );
	wp_localize_script( 'pressbooks-publisher-admin', 'PB_Publisher_Admin', array(
		'publisherAdminNonce' => wp_create_nonce( 'pressbooks-publisher-admin' ),
		'catalog_updated' => __( 'Catalog updated.', 'pressbooks' ),
		'catalog_not_updated' => __( 'Sorry, but your catalog was not updated. Please try again.', 'pressbooks' ),
		'dismiss_notice' => __( 'Dismiss this notice.', 'pressbooks' ),
	));
}

add_action( 'admin_enqueue_scripts', 'pressbooks_publisher_admin_scripts' );

function pressbooks_publisher_update_catalog() {
	$blog_id = absint( $_POST['book_id'] );
	$in_catalog = $_POST['in_catalog'];

	if ( current_user_can( 'manage_network' ) && check_ajax_referer( 'pressbooks-publisher-admin' ) ) {
		if ( $in_catalog == 'true' ) {
			update_blog_option( $blog_id, 'pressbooks_publisher_in_catalog', 1 );
		} else {
			delete_blog_option( $blog_id, 'pressbooks_publisher_in_catalog' );
		}
	}
}

add_action( 'wp_ajax_pressbooks_publisher_update_catalog', 'pressbooks_publisher_update_catalog' );

function pressbooks_publisher_catalog_columns( $columns ) {
	$columns[ 'in_catalog' ] = __( 'In Catalog', 'pressbooks' );
	return $columns;
}

add_filter( 'wpmu_blogs_columns', 'pressbooks_publisher_catalog_columns' );

function pressbooks_publisher_catalog_column( $column, $blog_id ) {

	if ( $column == 'in_catalog' && !is_main_site( $blog_id ) ) { ?>
		<input class="in-catalog" type="checkbox" name="in_catalog" value="1" <?php checked( get_blog_option( $blog_id, 'pressbooks_publisher_in_catalog' ), 1 ); ?> />
	<?php }

}

add_action( 'manage_blogs_custom_column', 'pressbooks_publisher_catalog_column', 1, 3 );
add_action( 'manage_sites_custom_column', 'pressbooks_publisher_catalog_column', 1, 3 );

function pressbooks_normalize_site_logo_url( $html ) {
	if ( force_ssl_admin() ) {
		$html = preg_replace( "/http:\/\//iU", "https://", $html );
	}
	return $html;
}

// add_filter( 'jetpack_the_site_logo', 'pressbooks_normalize_site_logo_url' );
