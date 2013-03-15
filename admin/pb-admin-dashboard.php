<?php
/**
 * @author  PressBooks <code@pressbooks.org>
 * @license GPLv2 (or any later version)
 */
namespace PressBooks\Admin\Dashboard;


/**
 *  Remove unwanted root Dashboard widgets, add our news feed.
 */
function replace_root_dashboard_widgets() {

	global $wp_meta_boxes;

	// Remove unwanted dashboard widgets
	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_primary'] );
	unset( $wp_meta_boxes['dashboard']['side']['core']['dashboard_secondary'] );

	// Add our news feed.
	add_meta_box( 'pb_dashboard_widget_blog', __( 'PressBooks News', 'pressbooks' ), __NAMESPACE__ . '\display_pressbooks_blog', 'dashboard', 'side', 'low' );
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
	add_meta_box( 'pb_dashboard_widget_book', get_bloginfo( 'name' ), __NAMESPACE__ . '\display_book_widget', 'dashboard', 'normal', 'high' );
	add_meta_box( 'pb_dashboard_widget_metadata', __( 'PressBooks News', 'pressbooks' ), __NAMESPACE__ . '\display_pressbooks_blog', 'dashboard', 'side', 'high' );
	add_meta_box( 'pb_dashboard_widget_users', __( 'Users', 'pressbooks' ), __NAMESPACE__ . '\display_users_widget', 'dashboard', 'side', 'high' );
}


/**
 * Displays a Book widget
 */
function display_book_widget() {

	$book_structure = \PressBooks\Book::getBookStructure();

	// front-matter
	echo "<ul><li><h4>" . __( 'Front Matter', 'pressbooks' ) . "</h4></li><ul>";
	foreach ( $book_structure['front-matter'] as $fm ) {
		echo "<li style='margin-left:10px;'> <a href='post.php?post=" . $fm['ID'] . "&action=edit'>" . $fm['post_title'] . "</a></li>\n";
	}
	echo "</ul>";

	// parts
	foreach ( $book_structure['part'] as $part ) {
		echo "<ul><li><h4><a href='post.php?post=" . $part['ID'] . "&action=edit'>" . $part['post_title'] . "</a></h4></li><ul>\n";
		// chapters
		foreach ( $part['chapters'] as $chapter ) {
			echo "<li style='margin-left:10px;'> <a href='post.php?post=" . $chapter['ID'] . "&action=edit'>" . $chapter['post_title'] . "</a></li>\n";
		}
		echo "</ul>\n";
	}

	// back-matter
	echo "<li><h4>" . __( 'Back Matter', 'pressbooks' ) . "</h4></li><ul>";
	foreach ( $book_structure['back-matter'] as $bm ) {
		echo "<li style='margin-left:10px;'> <a href='post.php?post=" . $bm['ID'] . "&action=edit'>" . $bm['post_title'] . "</a></li>\n";
	}
	echo "</ul>";

	// add, organize
	echo "</ul>\n";
	echo '<div class="part-buttons"><a href="post-new.php?post_type=chapter">' . __( 'Add', 'pressbooks' ) . '</a> | <a class="remove" href="admin.php?page=pressbooks">' . __( 'Organize', 'pressbooks' ) . '</a></div>';
}


/**
 * Displays the PressBooks Blog RSS as a widget
 */
function display_pressbooks_blog() {

	wp_widget_rss_output( array(
		'url' => 'http://blog.pressbooks.com/?feed=rss2',
		'title' => __( 'PressBooks News', 'pressbooks' ),
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