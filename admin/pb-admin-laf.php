<?php
/**
 * Look and feel.
 *
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Admin\Laf;


/**
 * Add a custom message in admin footer
 */
function add_footer_link() {

	printf(
		'<p id="footer-left" class="alignleft"><span id="footer-thankyou">%s <a href="http://pressbooks.com">PressBooks</a></span> &bull; <a href="http://pressbooks.com/about">%s</a> &bull; <a href="http://pressbooks.com/help">%s</a> &bull; <a href="http://pressbooks.com/contact">%s</a></p>',
		__( 'Powered by', 'pressbooks' ),
		__( 'About', 'pressbooks' ),
		__( 'Help', 'pressbooks' ),
		__( 'Contact', 'pressbooks' )
	);

	if ( current_user_can( 'edit_posts' ) ) {
		// Embed the blog_id in the admin interface so we can debug things more easily
		global $blog_id;
		echo "<!-- blog_id: $blog_id -->";
	}
}


/**
 * Add a feedback dialogue to admin header
 */
function add_feedback_dialogue() {

	?>
<div id="myModal" class="modal hide fade">
	<div class="modal-header">
		<a class="close" data-dismiss="modal">&times;</a>

		<h3><?php _e( 'Feedback', 'pressbooks' ); ?></h3>
	</div>
	<div class="modal-body">
		<p>Do you have questions, feedback or comments? You can visit our
		<a href="http://forum.pressbooks.com/" target="_blank">User Forum</a> (sorry, you will have to register there again), or send us an email at
		<a href="mailto:support@pressbooks.com">support@pressbooks.com</a></p>
	</div>
	<div class="modal-footer">
		<a href="#" class="btn" data-dismiss="modal"><?php _e( 'Close', 'pressbooks' ); ?></a>
	</div>
</div>
<a class="admin-feedback-btn" href="#myModal" data-toggle="modal"><?php _e( 'Feedback', 'pressbooks' ); ?></a>
<?php
}

/**
 * Replaces 'WordPress' with 'PressBooks' in titles of admin pages.
 */
function admin_title( $admin_title ) {
	$title = str_replace( 'WordPress', 'PressBooks', $admin_title );
	return $title;
}

/**
 * Removes some default WordPress Admin Sidebar items and adds our own
 */
function replace_book_admin_menu() {

	global $menu, $submenu;

	// Modify $menu and $submenu global arrays to do some tasks, such as adding a new separator,
	// moving items from one menu into another, and reordering sub-menu items.

	$menu[13] = $menu[60]; // Relocate Appearance
	unset( $menu[60] );

	$menu[68] = $menu[10]; // Relocate Media
	unset( $menu[10] );

	$menu[69] = $menu[25]; // Relocate Comments
	unset( $menu[25] );

	// Remove items we don't want the user to see.
	remove_submenu_page( 'options-general.php', 'options-general.php' );
	remove_submenu_page( 'options-general.php', 'options-writing.php' );
	remove_submenu_page( 'options-general.php', 'options-reading.php' );
	remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	remove_submenu_page( 'options-general.php', 'options-media.php' );
	remove_submenu_page( 'options-general.php', 'options-permalink.php' );

	remove_menu_page( "edit.php?post_type=part" );
	remove_menu_page( "edit.php" );
	remove_menu_page( "edit.php?post_type=front-matter" );
	remove_menu_page( "edit.php?post_type=back-matter" );
	remove_menu_page( "edit.php?post_type=metadata" );
	remove_menu_page( "link-manager.php" );
	remove_menu_page( "edit.php?post_type=page" );
	add_theme_page( __( 'Theme Options', 'pressbooks' ), __( 'Theme Options', 'pressbooks' ), 'edit_theme_options', 'pressbooks_theme_options', 'pressbooks_theme_options_display' );
	if ( ! current_user_can( 'import' ) || ! function_exists( 'register_pressbooks_import_page' ) ) {
		remove_menu_page( "tools.php" );
	}
	remove_submenu_page( "tools.php", "tools.php" );
	remove_submenu_page( "tools.php", "import.php" );
	remove_submenu_page( "tools.php", "export.php" );
	remove_submenu_page( "tools.php", "ms-delete-site.php" );
	remove_menu_page( "plugins.php" );
	remove_submenu_page( "edit.php?post_type=chapter", "edit.php?post_type=chapter" );


	// Separator
	// $menu[56] = array( '', 'read', "separator{0}", '', 'wp-menu-separator' );


	// Organize
	$page = add_submenu_page( 'edit.php?post_type=chapter', __( 'Organize', 'pressbooks' ), __( 'Organize', 'pressbooks' ), 'publish_posts', 'pressbooks', __NAMESPACE__ . '\display_organize' );
	add_action( 'admin_enqueue_scripts', function ( $hook ) use ( $page ) {
		if ( $hook == $page ) {
			wp_enqueue_script( 'jquery-blockui' );
			wp_enqueue_script( 'pb-organize' );
			wp_localize_script( 'pb-organize', 'PB_OrganizeToken', array(
				// Ajax nonces
				'orderNonce' => wp_create_nonce( 'pb-update-book-order' ),
				'exportNonce' => wp_create_nonce( 'pb-update-book-export' ),
			) );
		}
	} );
	$add_part = $submenu['edit.php?post_type=part'][10];
	$add_chapter = $submenu['edit.php?post_type=chapter'][10];
	$add_front_matter = $submenu['edit.php?post_type=front-matter'][10];
	$add_back_matter = $submenu['edit.php?post_type=back-matter'][10];
	array_push( $submenu['edit.php?post_type=chapter'], $add_part, $add_chapter, $add_front_matter, $add_back_matter );
	unset( $submenu['edit.php?post_type=chapter'][10] );
	if ( is_super_admin() ) {
		// If network administrator, give the option to see front matter types.
		array_push( $submenu['edit.php?post_type=chapter'], $submenu['edit.php?post_type=front-matter'][15], $submenu['edit.php?post_type=back-matter'][15] );
	}


	// Book Information
	$metadata = new \PressBooks\Metadata();
	$meta = $metadata->getMetaPost();
	if ( ! empty( $meta ) ) {
		$book_info_url = 'post.php?post=' . absint( $meta->ID ) . '&action=edit';
	} else {
		$book_info_url = 'post-new.php?post_type=metadata';
	}
	$page = add_menu_page( __( 'Book Info', 'pressbooks' ), __( 'Book Info', 'pressbooks' ), 'add_users', $book_info_url, '', '', 12 );
	add_action( 'admin_enqueue_scripts', function ( $hook ) use ( $page ) {
		if ( $hook == 'post-new.php' || $hook == 'post.php' ) {
			if ( 'metadata' == get_post_type() ) {
				wp_enqueue_script( 'pb-metadata' );
				wp_localize_script( 'pb-metadata', 'PB_BookInfoToken', array(
					'bookInfoMenuId' => preg_replace( '|[^a-zA-Z0-9_:.]|', '-', $page )
				) );
			}
		}
	} );


	// Separator
	// $menu[14] = array( '', 'read', "separator{0}", '', 'wp-menu-separator' );


	// Export
	$page = add_menu_page( __( 'Export', 'pressbooks' ), __( 'Export', 'pressbooks' ), "add_users", "pb_export", __NAMESPACE__ . '\display_export', '', 14 );
	add_action( 'admin_enqueue_scripts', function ( $hook ) use ( $page ) {
		if ( $hook == $page ) {
			wp_enqueue_script( 'pb-export' );
			wp_localize_script( 'pb-export', 'PB_ExportToken', array(
				'mobiConfirm' => __( 'EPUB is required for MOBI export. Would you like to reenable it?', 'pressbooks' ),
			) );
		}
	} );


	// Privacy
	add_options_page( __( 'Privacy Settings', 'pressbooks' ), __( 'Privacy', 'pressbooks' ), 'manage_options', 'privacy-options', __NAMESPACE__ . '\display_privacy_settings' );

	// Ecommerce
	add_options_page( __( 'Ecommerce Settings', 'pressbooks' ), __( 'Ecommerce', 'pressbooks' ), 'manage_options', 'ecomm-options', __NAMESPACE__ . '\display_ecomm_settings' );

	// Advanced
	add_options_page( __( 'Advanced Settings', 'pressbooks' ), __( 'Advanced', 'pressbooks' ), 'manage_options', 'advanced-options', __NAMESPACE__ . '\display_advanced_settings' );
}


/**
 * Fix extraneous menus on WordPress Admin sidebar
 */
function fix_root_admin_menu() {

	remove_menu_page( "edit.php?post_type=part" );
	remove_menu_page( "edit.php?post_type=chapter" );
	remove_menu_page( "edit.php?post_type=front-matter" );
	remove_menu_page( "edit.php?post_type=back-matter" );
	remove_menu_page( "edit.php?post_type=metadata" );
}


/**
 * Displays the Organize page.
 *
 * @todo Rewrite organize page by extending \WP_List_Table class
 * @see http://wordpress.org/extend/plugins/custom-list-table-example/
 */
function display_organize() {

	require( PB_PLUGIN_DIR . 'admin/templates/organize.php' );
}


/**
 * Displays the Export Admin Page
 */
function display_export() {

	require( PB_PLUGIN_DIR . 'admin/templates/export.php' );
}


/**
 * Replace WP logo in menu bar with PB one and add links to About page, Contact page, and forums
 *
 * @param \WP_Admin_Bar $wp_admin_bar The admin bar object as it currently exists
 */
function replace_menu_bar_branding( $wp_admin_bar ) {

	$wp_admin_bar->remove_menu( 'wp-logo' );
	$wp_admin_bar->remove_menu( 'documentation' );
	$wp_admin_bar->remove_menu( 'feedback' );
	$wp_admin_bar->add_menu( array(
		'id' => 'wp-logo',
		'title' => '<span class="ab-icon"></span>',
		'href' => ( 'http://pressbooks.com/about' ),
		'meta' => array(
			'title' => __( 'About PressBooks', 'pressbooks' ),
		),
	) );

	if ( is_user_logged_in() ) {
		// Add "About WordPress" link
		$wp_admin_bar->add_menu( array(
			'parent' => 'wp-logo',
			'id' => 'about',
			'title' => __( 'About PressBooks', 'pressbooks' ),
			'href' => 'http://pressbooks.com/about',
		) );
	}

	// Add WordPress.org link
	$wp_admin_bar->add_menu( array(
		'parent' => 'wp-logo-external',
		'id' => 'wporg',
		'title' => __( 'PressBooks.com', 'pressbooks' ),
		'href' => 'http://pressbooks.com',
	) );

	// Add forums link
	$wp_admin_bar->add_menu( array(
		'parent' => 'wp-logo-external',
		'id' => 'support-forums',
		'title' => __( 'Support Forums', 'pressbooks' ),
		'href' => 'http://forum.pressbooks.com',
	) );

	// Add feedback link
	$wp_admin_bar->add_menu( array(
		'parent' => 'wp-logo-external',
		'id' => 'contact',
		'title' => __( 'Contact', 'pressbooks' ),
		'href' => 'http://pressbooks.com/contact',
	) );
}

/**
 * Rearrange ordering of Admin bar menu elements for our purposes
 *
 * @param \WP_Admin_Bar $wp_admin_bar
 */
function replace_menu_bar_my_sites( $wp_admin_bar ) {

	$wp_admin_bar->remove_menu( 'my-sites' );

	// Don't show for logged out users or single site mode.
	if ( ! is_user_logged_in() || ! is_multisite() )
		return;

	// Show only when the user has at least one site, or they're a super admin.
	if ( count( $wp_admin_bar->user->blogs ) < 1 && ! is_super_admin() )
		return;

	$wp_admin_bar->add_menu( array(
		'id' => 'my-sites',
		'title' => __( 'My Books', 'pressbooks' ),
		'href' => admin_url( 'my-sites.php' ),
	) );

	if ( is_super_admin() ) {
		$wp_admin_bar->add_group( array(
			'parent' => 'my-sites',
			'id' => 'my-sites-super-admin',
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'my-sites-super-admin',
			'id' => 'network-admin',
			'title' => __( 'Network Admin', 'pressbooks' ),
			'href' => network_admin_url(),
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'network-admin',
			'id' => 'network-admin-d',
			'title' => __( 'Dashboard', 'pressbooks' ),
			'href' => network_admin_url(),
		) );
		$wp_admin_bar->add_menu( array(
			'parent' => 'network-admin',
			'id' => 'network-admin-s',
			'title' => __( 'Sites', 'pressbooks' ),
			'href' => network_admin_url( 'sites.php' ),
		) );
		$wp_admin_bar->add_menu( array(
			'parent' => 'network-admin',
			'id' => 'network-admin-u',
			'title' => __( 'Users', 'pressbooks' ),
			'href' => network_admin_url( 'users.php' ),
		) );
		$wp_admin_bar->add_menu( array(
			'parent' => 'network-admin',
			'id' => 'network-admin-v',
			'title' => __( 'Visit Network', 'pressbooks' ),
			'href' => network_home_url(),
		) );
	}

	// Add site links
	$wp_admin_bar->add_group( array(
		'parent' => 'my-sites',
		'id' => 'my-sites-list',
		'meta' => array(
			'class' => is_super_admin() ? 'ab-sub-secondary' : '',
		),
	) );

	foreach ( (array) $wp_admin_bar->user->blogs as $blog ) {

		// TODO: Replace with some favicon lookup.
		$blavatar = '<img src="' . esc_url( PB_PLUGIN_URL . 'assets/images/pb.png' ) . '" alt="' . esc_attr__( 'Blavatar', 'pressbooks' ) . '" width="16" height="16" class="blavatar"/> ';

		$blogname = empty( $blog->blogname ) ? $blog->domain : $blog->blogname;
		$menu_id = 'blog-' . $blog->userblog_id;

		$wp_admin_bar->add_menu( array(
			'parent' => 'my-sites-list',
			'id' => $menu_id,
			'title' => $blavatar . $blogname,
			'href' => get_admin_url( $blog->userblog_id ),
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => $menu_id,
			'id' => $menu_id . '-d',
			'title' => __( 'Dashboard', 'pressbooks' ),
			'href' => get_admin_url( $blog->userblog_id ),
		) );

		if ( current_user_can_for_blog( $blog->userblog_id, 'edit_posts' ) ) {
			$wp_admin_bar->remove_menu( $menu_id . '-n' );
			$wp_admin_bar->remove_menu( $menu_id . '-c' );
		}

		$wp_admin_bar->add_menu( array(
			'parent' => $menu_id,
			'id' => $menu_id . '-v',
			'title' => __( 'Visit Site', 'pressbooks' ),
			'href' => get_home_url( $blog->userblog_id, '/' ),
		) );
	}
}


/**
 * Remove Updates item from admin menu
 *
 * @param \WP_Admin_Bar $wp_admin_bar
 */
function remove_menu_bar_update( $wp_admin_bar ) {
	$wp_admin_bar->remove_menu( 'updates' );
}


/**
 * Remove New Content item from admin menu
 *
 * @param \WP_Admin_Bar $wp_admin_bar
 */
function remove_menu_bar_new_content( $wp_admin_bar ) {
	$wp_admin_bar->remove_menu( 'new-content' );
}


/**
 * Edit form hacks
 */
function edit_form_hacks() {
	default_meta_checkboxes();
	transform_category_selection_box();
}


/**
 * Default selections for checkboxes created by custom_metadata class.
 */
function default_meta_checkboxes() {

	global $pagenow;
	if ( 'post-new.php' == $pagenow ) {
		?>
<script type="text/javascript">
	jQuery('#pb_export').attr('checked', 'checked');
	jQuery('#pb_show_title').attr('checked', 'checked');
</script>
	<?php
	}
}


/**
 * Transforms the category selection meta box from checkboxes to radio buttons to ensure only one item
 */
function transform_category_selection_box() {

	$base = get_bloginfo( 'url' );

	if ( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] == ( $base . '/wp-admin/post-new.php?post_type=front-matter' ) ) {
		$term = get_term_by( 'slug', 'miscellaneous', 'front-matter-type' );
	} elseif ( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] == ( $base . '/wp-admin/post-new.php?post_type=back-matter' ) ) {
		$term = get_term_by( 'slug', 'miscellaneous', 'back-matter-type' );
	}

	?>
<script type="text/javascript">
	jQuery('.category-tabs, .category-pop').remove();
	jQuery('input:checkbox[id^="in-front-matter-type"]').each(function () {
		jQuery(this).replaceWith(jQuery(this).clone(true).attr('type', 'radio'));
	});
	jQuery('input:checkbox[id^="in-back-matter-type"]').each(function () {
		jQuery(this).replaceWith(jQuery(this).clone(true).attr('type', 'radio'));
	});
		<?php if ( isset( $term ) ): ?>
	jQuery('input:radio[id="in-front-matter-type-<?php echo $term->term_id; ?>"]').attr('checked', 'checked');
	jQuery('input:radio[id="in-back-matter-type-<?php echo $term->term_id; ?>"]').attr('checked', 'checked');
		<?php endif; ?>
</script>
<?php
}


/**
 * Init event called at admin_init
 * Instantiates various sub-classes, remove meta boxes from post pages & registers custom post status.
 */
function init_css_js() {

	global $user_ID, $concatenate_scripts;

	// This is to work around JavaScript dependency errors
	$concatenate_scripts = false;
	if ( ! is_super_admin() ) {
		$restricted = \PressBooks\Admin\Users\get_restricted( get_current_blog_id() );
		$expr = in_array( 'index', $restricted ) ? '/^\/wp-admin\/((' . implode( '|', $restricted ) . ').php)?$/' : '/^\/wp-admin\/(' . implode( '|', $restricted ) . ').php$/';
		$url = parse_url( ( is_ssl() ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_PATH );
		if ( preg_match( $expr, $url ) ) {
			wp_redirect( get_site_url( get_current_blog_id(), '/' ), 401 );
		}

	}

	wp_admin_css_color( 'pb_colors', 'PressBooks', PB_PLUGIN_URL . 'assets/css/colors-pb.css', apply_filters( 'pb_admin_colors', array( '#b40026', '#d4002d', '#e9e9e9', '#dfdfdf' ) ) );
	update_user_option( $user_ID, 'admin_color', 'pb_colors', true );
	wp_deregister_style( 'pressbooks-book' ); // Theme's CSS
	wp_register_style( 'pressbooks-admin', PB_PLUGIN_URL . 'assets/css/pressbooks.css', array(), '1.0', 'screen' );
	wp_enqueue_style( 'pressbooks-admin' );
	wp_register_style( 'colors-fresh', site_url() . '/wp-admin/css/colors-fresh.css', array(), '1.0', 'screen' );
	wp_enqueue_style( 'colors-fresh' );
	wp_register_style( 'bootstrap-admin', PB_PLUGIN_URL . 'symbionts/jquery/bootstrap.min.css', array(), '2.0.1', 'screen' );
	wp_enqueue_style( 'bootstrap-admin' ); // Used by feedback button


	// Don't let other plugins override our scripts
	$badScripts = array( 'jquery-blockui', 'jquery-bootstrap', 'pb-organize', 'pb-feedback', 'pb-export', 'pb-metadata' );
	array_walk( $badScripts, function ( $value, $key ) {
		wp_deregister_script( $value );
	} );

	// Enqueue later, on-the-fly, using action: admin_print_scripts-
	wp_register_script( 'jquery-blockui', PB_PLUGIN_URL . 'symbionts/jquery/jquery.blockUI.js', array( 'jquery', 'jquery-ui-core' ), '2.53' );
	wp_register_script( 'pb-export', PB_PLUGIN_URL . 'assets/js/export.js', array( 'jquery' ), '1.0.1' );
	wp_register_script( 'pb-organize', PB_PLUGIN_URL . 'assets/js/organize.js', array( 'jquery', 'jquery-ui-core', 'jquery-blockui' ), '1.0.1' );
	wp_register_script( 'pb-metadata', PB_PLUGIN_URL . 'assets/js/book-information.js', array( 'jquery' ), '1.0.1' );

	// Enqueue now
	wp_register_script( 'jquery-bootstrap', PB_PLUGIN_URL . 'symbionts/jquery/bootstrap.min.js', array( 'jquery' ), '2.0.1' );
	wp_register_script( 'pb-feedback', PB_PLUGIN_URL . 'assets/js/feedback.js', array( 'jquery' ), '1.0' );

	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-core' );
	wp_enqueue_script( 'jquery-ui-sortable' );
	wp_enqueue_script( 'jquery-bootstrap' );
	wp_enqueue_script( 'pb-feedback' );
}

/* ------------------------------------------------------------------------ *
 * Privacy
 * ------------------------------------------------------------------------ */

/**
 * Privacy settings initialization
 */
function privacy_settings_init() {
	add_settings_section(
		'privacy_settings_section',
		'',
		__NAMESPACE__ . '\privacy_settings_section_callback',
		'privacy_settings'
	);
	add_settings_field(
		'blog_public',
		__( 'Site Visibility', 'pressbooks' ),
		__NAMESPACE__ . '\privacy_blog_public_callback',
		'privacy_settings',
		'privacy_settings_section'
	);
	register_setting(
		'privacy_settings',
		'blog_public',
		__NAMESPACE__ . '\privacy_blog_public_sanitize'
	);

}


/**
 * Privacy settings section callback
 */
function privacy_settings_section_callback() {
	echo '<p>' . __( 'Privacy options', 'pressbooks' ) . '.</p>';
}


/**
 * Privacy settings, blog_public field callback
 *
 * @param $args
 */
function privacy_blog_public_callback( $args ) {
	$blog_public = get_option( 'blog_public' );
	$html = '<input type="radio" id="blog-public" name="blog_public" value="1" ';
	if ( $blog_public ) $html .= 'checked="checked" ';
	$html .= '/>';
	$html .= '<label for="blog-public"> ' . __( 'Public. I would like this book to be visible to everyone.', 'pressbooks' ) . '</label><br />';
	$html .= '<input type="radio" id="blog-public" name="blog_public" value="0" ';
	if ( ! $blog_public ) $html .= 'checked="checked" ';
	$html .= '/>';
	$html .= '<label for="blog-norobots"> ' . __( 'Private. I would like this book to be accessible only to people I invite.', 'pressbooks' ) . '</label>';
	echo $html;
}


/**
 * Privacy settings, blog_public field sanitization
 *
 * @param $input
 * @return string
 */
function privacy_blog_public_sanitize( $input ) {
	return absint( $input );
}


/**
 * Display Privacy settings
 */
function display_privacy_settings() { ?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2>Privacy Settings</h2>
	<!-- Create the form that will be used to render our options -->
	<form method="post" action="options.php">
		<?php settings_fields( 'privacy_settings' );
		do_settings_sections( 'privacy_settings' ); ?>
		<?php submit_button(); ?>
	</form>
</div>

<?php
}

/* ------------------------------------------------------------------------ *
 * Ecommerce
 * ------------------------------------------------------------------------ */


/**
 * Ecommerce settings initialization
 */
function ecomm_settings_init() {
	add_settings_section(
		'ecomm_settings_section',
		'',
		__NAMESPACE__ . '\ecomm_settings_section_callback',
		'ecomm_settings'
	);
	add_settings_field(
		'amazon',
		__( 'Amazon URL', 'pressbooks' ),
		__NAMESPACE__ . '\ecomm_amazon_callback',
		'ecomm_settings',
		'ecomm_settings_section'
	);
	add_settings_field(
		'oreilly',
		__( 'Oreilly URL', 'pressbooks' ),
		__NAMESPACE__ . '\ecomm_oreilly_callback',
		'ecomm_settings',
		'ecomm_settings_section'
	);
	add_settings_field(
		'barnesandnoble',
		__( 'Barnes and Noble URL', 'pressbooks' ),
		__NAMESPACE__ . '\ecomm_barnesandnoble_callback',
		'ecomm_settings',
		'ecomm_settings_section'
	);
	add_settings_field(
		'kobo',
		__( 'Kobo URL', 'pressbooks' ),
		__NAMESPACE__ . '\ecomm_kobo_callback',
		'ecomm_settings',
		'ecomm_settings_section'
	);
	add_settings_field(
		'ibooks',
		__( 'iBooks URL', 'pressbooks' ),
		__NAMESPACE__ . '\ecomm_ibooks_callback',
		'ecomm_settings',
		'ecomm_settings_section'
	);
	add_settings_field(
		'otherservice',
		__( 'Other Service URL', 'pressbooks' ),
		__NAMESPACE__ . '\ecomm_otherservice_callback',
		'ecomm_settings',
		'ecomm_settings_section'
	);
	register_setting(
		'ecomm_settings',
		'pressbooks_ecommerce_links',
		__NAMESPACE__ . '\ecomm_links_sanitize'
	);
}


/**
 * Ecommerce settings section callback
 */
function ecomm_settings_section_callback() {
	echo '<p>' . __( 'Links to ecommerce services', 'pressbooks' ) . '.</p>';
}


/**
 * Ecommerce settings, Amazon field callback
 *
 * @param $args
 */
function ecomm_amazon_callback( $args ) {
	$options = get_option('pressbooks_ecommerce_links');
	$html = '<input type="text" id="amazon" name="pressbooks_ecommerce_links[amazon]" value="' . sanitize_text_field(@$options['amazon']) . '" />';
	echo $html;
}


/**
 * Ecommerce settings, Oreilly field callback
 *
 * @param $args
 */
function ecomm_oreilly_callback( $args ) {
	$options = get_option('pressbooks_ecommerce_links');
	$html = '<input type="text" id="oreilly" name="pressbooks_ecommerce_links[oreilly]" value="' . sanitize_text_field(@$options['oreilly']) . '" />';
	echo $html;
}


/**
 * Ecommerce settings, Barns & Noble field callback
 *
 * @param $args
 */
function ecomm_barnesandnoble_callback( $args ) {
	$options = get_option('pressbooks_ecommerce_links');
	$html = '<input type="text" id="barnesandnoble" name="pressbooks_ecommerce_links[barnesandnoble]" value="' . sanitize_text_field(@$options['barnesandnoble']) . '" />';
	echo $html;
}


/**
 * Ecommerce settings, Kobo field callback
 *
 * @param $args
 */
function ecomm_kobo_callback( $args ) {
	$options = get_option('pressbooks_ecommerce_links');
	$html = '<input type="text" id="kobo" name="pressbooks_ecommerce_links[kobo]" value="' . sanitize_text_field(@$options['kobo']) . '" />';
	echo $html;
}


/**
 * Ecommerce settings, iBooks field callback
 *
 * @param $args
 */
function ecomm_ibooks_callback( $args ) {
	$options = get_option('pressbooks_ecommerce_links');
	$html = '<input type="text" id="ibooks" name="pressbooks_ecommerce_links[ibooks]" value="' . sanitize_text_field(@$options['ibooks']) . '" />';
	echo $html;
}


/**
 * Ecommerce settings, Other Service field callback
 *
 * @param $args
 */
function ecomm_otherservice_callback( $args ) {
	$options = get_option('pressbooks_ecommerce_links');
	$html = '<input type="text" id="otherservice" name="pressbooks_ecommerce_links[otherservice]" value="' . sanitize_text_field(@$options['otherservice']) . '" />';
	echo $html;
}


/**
 * Ecommerce settings, input sanitization
 *
 * @param array $input
 * @return array
 */
function ecomm_links_sanitize( $input ) {
	$options = get_option( 'pressbooks_ecommerce_links' );
	foreach ( $input as $key => $value ) {
		$value = trim( strip_tags( stripslashes( $value ) ) );
		if ( $value ) {
			$options[$key] = \PressBooks\Sanitize\canonicalizeUrl( $value );
		} else {
			$options[$key] = null;
		}

	}

	return $options;
}

/**
 * Display Ecommerce settings
 */
function display_ecomm_settings() { ?>
	<div class="wrap">
		<div id="icon-link" class="icon32"></div>
		<h2>Ecommerce Settings</h2>
		<!-- Create the form that will be used to render our options -->
		<form method="post" action="options.php">
			<?php settings_fields( 'ecomm_settings' );
			do_settings_sections( 'ecomm_settings' ); ?>
			<?php submit_button(); ?>
		</form>
	</div>

<?php
}

/* ------------------------------------------------------------------------ *
 * Advanced
 * ------------------------------------------------------------------------ */

/**
 * Advanced settings initialization
 */
function advanced_settings_init() {
	add_settings_section(
		'advanced_settings_section',
		'',
			__NAMESPACE__ . '\advanced_settings_section_callback',
		'advanced_settings'
	);
	add_settings_field(
		'email_validation_logs',
		__( 'Email me validation error reports on export.', 'pressbooks' ),
			__NAMESPACE__ . '\advanced_email_validation_logs_callback',
		'advanced_settings',
		'advanced_settings_section'
	);
	register_setting(
		'advanced_settings',
		'pressbooks_email_validation_logs',
			__NAMESPACE__ . '\advanced_email_validation_logs_sanitize'
	);

}


/**
 * Advanced settings section callback
 */
function advanced_settings_section_callback() {
	echo '<p>' . __( 'Advanced options', 'pressbooks' ) . '.</p>';
}


/**
 *  Advanced settings, email_validation_logs field callback
 *
 * @param $args
 */
function advanced_email_validation_logs_callback( $args ) {
	$email_validation_logs = get_option( 'pressbooks_email_validation_logs' );
	$html = '<input type="radio" id="yes-validation-logs" name="pressbooks_email_validation_logs" value="0" ';
	if ( ! $email_validation_logs ) $html .= 'checked="checked" ';
	$html .= '/>';
	$html .= '<label for="yes-validation-logs"> ' . __( 'No. Ignore validation errors.', 'pressbooks' ) . '</label><br />';
	$html .= '<input type="radio" id="no-validation-logs" name="pressbooks_email_validation_logs" value="1" ';
	if ( $email_validation_logs ) $html .= 'checked="checked" ';
	$html .= '/>';
	$html .= '<label for="no-validation-logs"> ' . __( 'Yes. Send the logs.', 'pressbooks' ) . '</label>';
	$html .= '<br /><br /><em> ' . __( 'Note: validation error reports (for EPUB, Mobi, and PDF) are technical, and will require some effort to decipher. Unfortunately we cannot provide support for deciphering validation errors, but you could post errors on the <a href="http://forum.pressbooks.com/" target="_blank">PressBooks forum</a>, where we and other PressBooks users can help out as time permits. .', 'pressbooks' ) . '</em>';

	$html .= '</p>';

	echo $html;
}


/**
 * Advanced settings, email_validation_logs field sanitization
 *
 * @param $input
 * @return string
 */
function advanced_email_validation_logs_sanitize( $input ) {
	return absint( $input );
}


/**
 * Display Advanced settings
 */
function display_advanced_settings() { ?>
<div class="wrap">
	<div id="icon-options-general" class="icon32"></div>
	<h2>Advanced Settings</h2>
	<!-- Create the form that will be used to render our options -->
	<form method="post" action="options.php">
		<?php settings_fields( 'advanced_settings' );
		do_settings_sections( 'advanced_settings' ); ?>
		<?php submit_button(); ?>
	</form>
</div>

<?php
}
