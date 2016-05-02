<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Admin\Dashboard;

/**
 *  Remove unwanted network Dashboard widgets, add our news feed.
 */
function replace_network_dashboard_widgets() {

	global $wp_meta_boxes;

	// Remove unwanted dashboard widgets
	unset( $wp_meta_boxes['dashboard-network']['side']['core']['dashboard_primary'] );

	// Add our news feed.
	$options = array_map( 'stripslashes_deep', get_site_option( 'pressbooks_dashboard_feed', [
		'display_feed' => 1,
		'url' => 'https://pressbooks.com/feed/',
		'title' => 'Pressbooks News'
	] ) );
	if ( $options['display_feed'] == 1 ) {
		add_meta_box( 'pb_dashboard_widget_blog', __( $options['title'], 'pressbooks' ), __NAMESPACE__ . '\display_pressbooks_blog', 'dashboard-network', 'side', 'low' );
	}
}


/**
 *  Remove unwanted root Dashboard widgets, add our news feed.
 */
function replace_root_dashboard_widgets() {

	global $wp_meta_boxes;

	// Remove unwanted dashboard widgets
	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_primary'] );
	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary'] );

	// Add our news feed.
	$options = array_map( 'stripslashes_deep', get_site_option( 'pressbooks_dashboard_feed', [
		'display_feed' => 1,
		'url' => 'https://pressbooks.com/feed/',
		'title' => 'Pressbooks News'
	] ) );
	if ( $options['display_feed'] == 1 ) {
		add_meta_box( 'pb_dashboard_widget_blog', __( $options['title'], 'pressbooks' ), __NAMESPACE__ . '\display_pressbooks_blog', 'dashboard', 'side', 'low' );
	}
}


/**
 *  Remove all Dashboard widgets and replace with our own
 */
function replace_dashboard_widgets() {

	global $wp_meta_boxes;
	// Remove all dashboard widgets
	foreach ( $wp_meta_boxes['dashboard'] as $widgetSection => $widgetType ) {
		foreach ( $widgetType as $widgetCat => $widget ) {
			foreach ( $widget as $widgetName => $widgetData ) {
				unset( $wp_meta_boxes['dashboard'][$widgetSection][$widgetCat][$widgetName] );
			}
		}
	}
	// Replace with our own
	$book_name = get_bloginfo( 'name' );
	add_meta_box( 'pb_dashboard_widget_book', ( $book_name ? $book_name : __( 'My Book', 'pressbooks' ) ), __NAMESPACE__ . '\display_book_widget', 'dashboard', 'normal', 'high' );
	add_meta_box( 'pb_dashboard_widget_users', __( 'Users', 'pressbooks' ), __NAMESPACE__ . '\display_users_widget', 'dashboard', 'side', 'high' );

	// Add our news feed.
	$options = array_map( 'stripslashes_deep', get_site_option( 'pressbooks_dashboard_feed', [
		'display_feed' => 1,
		'url' => 'https://pressbooks.com/feed/',
		'title' => 'Pressbooks News'
	] ) );
	if ( $options['display_feed'] == 1 ) {
		add_meta_box( 'pb_dashboard_widget_blog', __( $options['title'], 'pressbooks' ), __NAMESPACE__ . '\display_pressbooks_blog', 'dashboard', 'side', 'low' );
	}

}


/**
 * Displays a Book widget
 */
function display_book_widget() {

	$book_structure = \Pressbooks\Book::getBookStructure();

	// front-matter
	echo "<ul><li><h4>" . __( 'Front Matter', 'pressbooks' ) . "</h4></li><ul>";
	foreach ( $book_structure['front-matter'] as $fm ) {
		$title = ( ! empty( $fm['post_title'] ) ? $fm['post_title'] : '&hellip;' );
		echo "<li class='front-matter'><a href='post.php?post=" . $fm['ID'] . "&action=edit'>" . $title . "</a></li>\n";
	}
	echo "</ul>";

	// parts
	foreach ( $book_structure['part'] as $part ) {
		$title = ( ! empty( $part['post_title'] ) ? $part['post_title'] : '&hellip;' );
		echo "<ul><li><h4><a href='post.php?post=" . $part['ID'] . "&action=edit'>" . $title . "</a></h4></li><ul>\n";
		// chapters
		foreach ( $part['chapters'] as $chapter ) {
			$title = ( ! empty( $chapter['post_title'] ) ? $chapter['post_title'] : '&hellip;' );
			echo "<li class='chapter'><a href='post.php?post=" . $chapter['ID'] . "&action=edit'>" . $title . "</a></li>\n";
		}
		echo "</ul>\n";
	}

	// back-matter
	echo "<li><h4>" . __( 'Back Matter', 'pressbooks' ) . "</h4></li><ul>";
	foreach ( $book_structure['back-matter'] as $bm ) {
		$title = ( ! empty( $bm['post_title'] ) ? $bm['post_title'] : '&hellip;' );
		echo "<li class='back-matter'><a href='post.php?post=" . $bm['ID'] . "&action=edit'>" . $title . "</a></li>\n";
	}
	echo "</ul>";

	// add, organize
	echo "</ul>\n";
	echo '<div class="part-buttons"><a href="post-new.php?post_type=chapter">' . __( 'Add', 'pressbooks' ) . '</a> | <a class="remove" href="admin.php?page=pressbooks">' . __( 'Organize', 'pressbooks' ) . '</a></div>';
}


/**
 * Displays the Pressbooks Blog RSS as a widget
 */
function display_pressbooks_blog() {

	$options = array_map( 'stripslashes_deep', get_site_option( 'pressbooks_dashboard_feed', [
		'display_feed' => 1,
		'url' => 'https://pressbooks.com/feed/',
		'title' => 'Pressbooks News'
	] ) );

	wp_widget_rss_output( array(
		'url' => $options['url'],
		'items' => 5,
		'show_summary' => 1,
		'show_author' => 0,
		'show_date' => 1,
	) );
}


/**
 * Displays a Users widget
 */
function display_users_widget() {

	/** @var $wpdb \wpdb */
	global $wpdb;

	$sql = "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s ";
	$users = $wpdb->get_results( $wpdb->prepare( $sql, $wpdb->get_blog_prefix( get_current_blog_id() ) . 'capabilities' ) );

	echo '<table>';
	foreach ( $users as $user ) {
		$meta = unserialize( $user->meta_value );
		$u = get_userdata( $user->user_id );
		echo '<tr><td>' . get_avatar( $user->user_id, 32 ) . '</td><td>' . $u->display_name . ' - ' . ucfirst( key( $meta ) ) . '</td></tr>';
	}
	echo '</table>';

	echo '<div class="part-buttons"> <a href="user-new.php">' . __( 'Add', 'pressbooks' ) . '</a> | <a class="remove" href="users.php">' . __( 'Organize', 'pressbooks' ) . '</a></div>';
}

/**
 *
 */
function add_menu() {
	$page = add_submenu_page(
		'settings.php',
		__( 'Dashboard', 'pressbooks' ),
		__( 'Dashboard', 'pressbooks' ),
		'manage_network',
		'pb_dashboard',
		__NAMESPACE__ . '\options'
	);
}

/**
 *
 */
function options() {
	require( PB_PLUGIN_DIR . 'templates/admin/dashboard.php' );
}

function dashboard_options_init() {

    $_page = 'pb_dashboard';
    $_option = 'pressbooks_dashboard_feed';

    add_settings_section(
        'dashboard_feed',
        __( 'Dashboard Feed', 'pressbooks' ),
        __NAMESPACE__ . '\dashboard_feed_callback',
        $_page
    );

    add_settings_field(
        'display_feed',
        __( 'Display Feed', 'pressbooks' ),
        __NAMESPACE__ . '\display_feed_callback',
        $_page,
        'dashboard_feed',
        array(
          'description' => __( 'Display an RSS feed widget on the dashboard.', 'pressbooks' )
        )
    );

		add_settings_field(
        'title',
        __( 'Feed Title', 'pressbooks' ),
        __NAMESPACE__ . '\title_callback',
        $_page,
        'dashboard_feed'
    );

		add_settings_field(
        'url',
        __( 'Feed URL', 'pressbooks' ),
        __NAMESPACE__ . '\url_callback',
        $_page,
        'dashboard_feed'
    );

    register_setting(
        $_page,
        'display_feed',
        __NAMESPACE__ . '\display_feed_sanitize'
    );

		register_setting(
        $_page,
        'title',
        __NAMESPACE__ . '\title_sanitize'
    );

		register_setting(
        $_page,
        'url',
        __NAMESPACE__ . '\url_sanitize'
    );
}

function dashboard_feed_callback( $args ) { ?>
    <p><?php __( 'Adjust settings for your dashboard RSS feed widget below.', 'pressbooks' ); ?></p>
<?php }

function display_feed_callback( $args ) {
	$options = get_site_option( 'pressbooks_dashboard_feed', [
		'display_feed' => 1,
		'url' => 'https://pressbooks.com/feed/',
		'title' => 'Pressbooks News'
	] );

	$html = '<input id="display_feed" name="pressbooks_dashboard_feed[display_feed]" type="checkbox" value="1" ' . checked( $options['display_feed'], 1, false ) . '/>';
	$html .= '<p class="description">' . $args['description'] . '</p>';
	echo $html;
}

function title_callback( $args ) {
	$options = array_map( 'stripslashes_deep', get_site_option( 'pressbooks_dashboard_feed', [
		'display_feed' => 1,
		'url' => 'https://pressbooks.com/feed/',
		'title' => 'Pressbooks News'
	] ) );
  $html = '<input id="title" name="pressbooks_dashboard_feed[title]" type="text" value="' . $options['title'] . '" />';
  echo $html;
}

function url_callback( $args ) {
	$options = get_site_option( 'pressbooks_dashboard_feed', [
		'display_feed' => 1,
		'url' => 'https://pressbooks.com/feed/',
		'title' => 'Pressbooks News'
	] );
  $html = '<input id="url" name="pressbooks_dashboard_feed[url]" type="text" value="' . $options['url'] . '" />';
  echo $html;
}

function display_feed_sanitize( $input ) {
    return absint( $input );
}

function title_sanitize( $input ) {
    return wp_kses_post( $input );
}

function url_sanitize( $input ) {
    return esc_url( $input );
}
