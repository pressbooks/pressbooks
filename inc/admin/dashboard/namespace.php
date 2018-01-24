<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */

namespace Pressbooks\Admin\Dashboard;

/**
 * @return array
 */
function get_rss_defaults() {
	return [
		'display_feed' => 1,
		'url' => 'https://pressbooks.com/feed/',
		'title' => __( 'Pressbooks News', 'pressbooks' ),
	];
}

/**
 *  Remove unwanted network Dashboard widgets, add our news feed.
 */
function replace_network_dashboard_widgets() {

	global $wp_meta_boxes;

	// Remove unwanted dashboard widgets
	unset( $wp_meta_boxes['dashboard-network']['side']['core']['dashboard_primary'] );

	// Add our news feed.
	$options = array_map(
		'stripslashes_deep', get_site_option(
			'pressbooks_dashboard_feed', get_rss_defaults()
		)
	);
	if ( ! empty( $options['display_feed'] ) ) {
		add_meta_box( 'pb_dashboard_widget_blog', $options['title'], __NAMESPACE__ . '\display_pressbooks_blog', 'dashboard-network', 'side', 'low' );
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
	$options = array_map(
		'stripslashes_deep', get_site_option(
			'pressbooks_dashboard_feed', get_rss_defaults()
		)
	);
	if ( ! empty( $options['display_feed'] ) ) {
		add_meta_box( 'pb_dashboard_widget_blog', $options['title'], __NAMESPACE__ . '\display_pressbooks_blog', 'dashboard', 'side', 'low' );
	}
}


/**
 *  Remove all Dashboard widgets and replace with our own
 */
function replace_dashboard_widgets() {

	global $wp_meta_boxes;
	// Remove all dashboard widgets
	foreach ( $wp_meta_boxes['dashboard'] as $widget_section => $widget_type ) {
		foreach ( $widget_type as $widget_cat => $widget ) {
			foreach ( $widget as $widget_name => $widget_data ) {
				unset( $wp_meta_boxes['dashboard'][ $widget_section ][ $widget_cat ][ $widget_name ] );
			}
		}
	}
	// Replace with our own
	$book_name = get_bloginfo( 'name' );
	add_meta_box( 'pb_dashboard_widget_book', ( $book_name ? $book_name : __( 'My Book', 'pressbooks' ) ), __NAMESPACE__ . '\display_book_widget', 'dashboard', 'normal', 'high' );
	add_meta_box( 'pb_dashboard_widget_users', __( 'Users', 'pressbooks' ), __NAMESPACE__ . '\display_users_widget', 'dashboard', 'side', 'high' );

	// Add our news feed.
	$options = array_map(
		'stripslashes_deep', get_site_option(
			'pressbooks_dashboard_feed', get_rss_defaults()
		)
	);
	if ( ! empty( $options['display_feed'] ) ) {
		add_meta_box( 'pb_dashboard_widget_blog', $options['title'], __NAMESPACE__ . '\display_pressbooks_blog', 'dashboard', 'side', 'low' );
	}

}


/**
 * Displays a Book widget
 */
function display_book_widget() {

	$book_structure = \Pressbooks\Book::getBookStructure(); ?>
	<nav aria-label="<?php _e( 'Table of Contents', 'pressbooks' ); ?>">
		<ul>
			<li><h3><strong><?php _e( 'Front Matter', 'pressbooks' ); ?></strong></h3>
				<ul class='front-matter'>
				<?php
				foreach ( $book_structure['front-matter'] as $component ) {
					$title = ( ! empty( $component['post_title'] ) ? $component['post_title'] : '&hellip;' );
					printf(
						"<li>%s</li>\n",
						current_user_can( 'edit_post', $component['ID'] ) ? sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'post.php?post=' . $component['ID'] . '&action=edit' ), $title ) : $title
					);
				}
				?>
				</ul>
			</li>
			<?php
			foreach ( $book_structure['part'] as $part ) {
			?>
			<li>
				<?php
				$title = ( ! empty( $part['post_title'] ) ? $part['post_title'] : '&hellip;' );
				if ( current_user_can( 'edit_post', $part['ID'] ) ) {
					printf(
						"<h3><strong>%s</strong></h3>\n",
						current_user_can( 'edit_post', $part['ID'] ) ? sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'post.php?post=' . $part['ID'] . '&action=edit' ), $title ) : $title
					);
				}
				?>
				<ul class='chapters'>
				<?php
				foreach ( $part['chapters'] as $component ) {
					$title = ( ! empty( $component['post_title'] ) ? $component['post_title'] : '&hellip;' );
					printf(
						"<li>%s</li>\n",
						current_user_can( 'edit_post', $component['ID'] ) ? sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'post.php?post=' . $component['ID'] . '&action=edit' ), $title ) : $title
					);
				}
				?>
				</ul>
			</li>
			<?php
			}
			?>
			<li><h3><strong><?php _e( 'Back Matter', 'pressbooks' ); ?></strong></h3>
				<ul class='back-matter'>
				<?php
				foreach ( $book_structure['back-matter'] as $component ) {
					$title = ( ! empty( $component['post_title'] ) ? $component['post_title'] : '&hellip;' );
					printf(
						"<li>%s</li>\n",
						current_user_can( 'edit_post', $component['ID'] ) ? sprintf( '<a href="%1$s">%2$s</a>', admin_url( 'post.php?post=' . $component['ID'] . '&action=edit' ), $title ) : $title
					);
				}
				?>
				</ul>
			</li>
		</ul>
	</nav>
	<?php
	if ( current_user_can( 'edit_posts' ) ) {
	?>
	<div class="part-buttons">
		<a href="post-new.php?post_type=chapter"><?php _e( 'Add', 'pressbooks' ); ?></a> | <a class="organize" href="<?php echo admin_url( 'admin.php?page=pb_organize' ); ?>"><?php _e( 'Organize', 'pressbooks' ); ?></a>
	</div>
	<?php
	}
}

/**
 * Displays the Pressbooks Blog RSS as a widget
 */
function display_pressbooks_blog() {
	$rss = get_site_transient( 'pb_rss_widget' );
	if ( empty( $rss ) ) {
		$options = array_map(
			'stripslashes_deep', get_site_option(
				'pressbooks_dashboard_feed', get_rss_defaults()
			)
		);

		ob_start();
		wp_widget_rss_output(
			[
				'url' => $options['url'],
				'items' => 5,
				'show_summary' => 1,
				'show_author' => 0,
				'show_date' => 0,
			]
		);
		$rss = ob_get_clean();

		set_site_transient( 'pb_rss_widget', $rss, DAY_IN_SECONDS );
	}
	echo $rss;
}


/**
 * Displays a Users widget
 */
function display_users_widget() {

	/** @var $wpdb \wpdb */
	global $wpdb;

	$users = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s ", $wpdb->get_blog_prefix( get_current_blog_id() ) . 'capabilities' ) );

	echo '<table>';
	foreach ( $users as $user ) {
		$meta = unserialize( $user->meta_value ); // @codingStandardsIgnoreLine
		if ( is_object( $meta ) ) {
			continue; // Hack attempt?
		}
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
	add_submenu_page(
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
		[
			'description' => __( 'Display an RSS feed widget on the dashboard.', 'pressbooks' ),
		]
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

function dashboard_feed_callback( $args ) {
	?>
	<p><?php __( 'Adjust settings for your dashboard RSS feed widget below.', 'pressbooks' ); ?></p>
<?php
}

function display_feed_callback( $args ) {
	$options = get_site_option(
		'pressbooks_dashboard_feed', get_rss_defaults()
	);

	$html = '<input id="display_feed" name="pressbooks_dashboard_feed[display_feed]" type="checkbox" value="1" ' . checked( $options['display_feed'], 1, false ) . '/>';
	$html .= '<p class="description">' . $args['description'] . '</p>';
	echo $html;
}

function title_callback( $args ) {
	$options = array_map(
		'stripslashes_deep', get_site_option(
			'pressbooks_dashboard_feed', get_rss_defaults()
		)
	);
	$html = '<input id="title" name="pressbooks_dashboard_feed[title]" type="text" value="' . $options['title'] . '" />';
	echo $html;
}

function url_callback( $args ) {
	$options = get_site_option(
		'pressbooks_dashboard_feed', get_rss_defaults()
	);
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
