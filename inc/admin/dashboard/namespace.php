<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Admin\Dashboard;

use function Pressbooks\Sanitize\safer_unserialize;

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

	// Remove third-party widgets
	remove_meta_box( 'dashboard_rediscache', 'dashboard-network', 'normal' );

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

	// Remove unwanted dashboard widgets
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	remove_meta_box( 'health_check_status', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );

	// Remove third-party widgets
	remove_meta_box( 'dashboard_rediscache', 'dashboard', 'normal' );
	$user = wp_get_current_user();

	if ( \Pressbooks\Utility\get_number_of_invitations( $user ) ) {
		add_pending_invitation_meta_box( 'dashboard' );
	}

	if (
		$user->roles &&
		count( $user->roles ) === 1 &&
		$user->roles[0] === 'subscriber'
	) {
		add_meta_box(
			'pb_dashboard_widget_book_permissions',
			__( 'Book Permissions', 'pressbooks' ),
			__NAMESPACE__ . '\lowly_user_callback',
			'dashboard',
			'normal',
			'high'
		);
		return true;
	}
	// Add our news feed.
	$options = array_map(
		'stripslashes_deep',
		get_site_option(
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

	// Remove third-party widgets
	remove_meta_box( 'dashboard_rediscache', 'dashboard', 'normal' );

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
 * A widget for /wp-admin/user/ in case someone without adequate permissions lands here (SSO, atypical config, ...)
 */
function lowly_user() {
	global $wp_meta_boxes;
	// https://github.com/pressbooks/pressbooks/issues/2041:  Remove health status and primary (WP news) widgets
	if ( array_key_exists( 'dashboard-user', $wp_meta_boxes ) ) {
		if (
			array_key_exists( 'side', $wp_meta_boxes['dashboard-user'] ) &&
			array_key_exists( 'dashboard_primary', $wp_meta_boxes['dashboard-user']['side']['core'] )
		) {
			unset( $wp_meta_boxes['dashboard-user']['side']['core']['dashboard_primary'] );
		}
		if (
			array_key_exists( 'normal', $wp_meta_boxes['dashboard-user'] ) &&
			array_key_exists( 'dashboard_site_health', $wp_meta_boxes['dashboard-user']['normal']['core'] )
		) {
			unset( $wp_meta_boxes['dashboard-user']['normal']['core']['dashboard_site_health'] );
		}
	}

	if ( \Pressbooks\Utility\get_number_of_invitations( wp_get_current_user() ) ) {
		add_pending_invitation_meta_box( 'dashboard-user' );
	}

	add_meta_box(
		'pb_dashboard_widget_book_permissions',
		__( 'Book Permissions', 'pressbooks' ),
		__NAMESPACE__ . '\lowly_user_callback',
		'dashboard-user',
		'normal',
		'high'
	);
}

/**
 * Adds pending invitations meta box
 *
 * @param string $screen
 */
function add_pending_invitation_meta_box( $screen ) {
	add_meta_box(
		'pb_dashboard_widget_book_invitations',
		__( 'Book Invitations', 'pressbooks' ),
		__NAMESPACE__ . '\pending_invitations_callback',
		$screen,
		'normal',
		'high'
	);
}
/**
 * Callback for /wp-admin and /wp-admin/user widget
 *
 * Renders book invitations if user has at least one pending book invitation.
 */
function pending_invitations_callback() {
	global $wpdb;

	$current_blog_id = get_current_blog_id();
	$user_id = get_current_user_id();

	$invitations = $wpdb->get_results( $wpdb->prepare( "SELECT meta_key, meta_value FROM $wpdb->usermeta WHERE meta_key LIKE %s AND user_id = %d", 'new_user_%', $user_id ) );

	foreach ( $invitations as $invitation ) {
		$metadata = maybe_unserialize( $invitation->meta_value );

		switch_to_blog( $metadata['blog_id'] );

		$message = sprintf(
			__( 'You have been invited to join <a href="%1$s">%2$s</a> as %3$s %4$s', 'pressbooks' ),
			home_url(),
			get_site_meta( $metadata['blog_id'], 'pb_title', true ),
			preg_match( '/^[aeiou]/i', $metadata['role'] ) ? 'an' : 'a',
			$metadata['role']
		);

		echo "
        <div>
            <p>$message</p>
            <a class='button button-primary' href='" . home_url( '/newbloguser/' . $metadata['key'] ) . "'>" . __( 'Accept', 'pressbooks' ) . '</a>
        </div>
        <hr/>
        ';
	}

	switch_to_blog( $current_blog_id );
}

/**
 * Callback for /wp-admin/user/ widget
 */
function lowly_user_callback() {
	echo '<p>' . __( 'Welcome to Pressbooks!', 'pressbooks' ) . '</p>';
	$user_has_books = count( get_blogs_of_user( get_current_user_id() ) ) > 1;
	if ( ! $user_has_books ) {
		echo '<p>' . __( 'You do not have access to any books at the moment.', 'pressbooks' ) . '</p>';
	}
	$contact = \Pressbooks\Utility\main_contact_email();
	// Values can be 'all', 'none', 'blog', or 'user', @see wp-signup.php
	$active_signup = apply_filters( 'wpmu_active_signup', get_site_option( 'registration', 'none' ) );
	if ( in_array( $active_signup, [ 'none', 'user' ], true ) ) {
		echo '<p>';
		_e( 'This network does not allow users to create new books. To create a new book, please contact your Pressbooks Network Manager', 'pressbooks' );
		if ( ! empty( $contact ) ) {
			echo ' ' . __( 'at', 'pressbooks' ) . " $contact";
		} else {
			echo '.';
		}
		echo '</p>';
	} else {
		$href_create = network_home_url( 'wp-signup.php' );
		$text_create = __( 'Create a Book', 'pressbooks' );
		$href_clone = admin_url( 'admin.php?page=pb_cloner' );
		$text_clone = __( 'Clone a Book', 'pressbooks' );
		echo "<p><a class='button button-hero button-primary' href='{$href_create}'>{$text_create}</a></p><p><a class='button button-hero button-primary' href='{$href_clone}'>{$text_clone}</a></p>";
	}
	if ( ! $user_has_books ) {
		echo '<p>';
		_e( "You can also request access to an existing book by contacting the book's author or the institution's Pressbooks Network Manager", 'pressbooks' );
		if ( ! empty( $contact ) ) {
			echo ' ' . __( 'at', 'pressbooks' ) . " $contact";
		} else {
			echo '.';
		}
		echo '</p>';
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
	$users = $wpdb->get_results( $wpdb->prepare( "SELECT user_id, meta_value FROM {$wpdb->usermeta} WHERE meta_key = %s", $wpdb->get_blog_prefix( get_current_blog_id() ) . 'capabilities' ) );
	$types_of_users = [];
	$displayed = 0;
	$limit = 50;

	echo '<table>';
	foreach ( $users as $user ) {
		$meta = safer_unserialize( $user->meta_value );
		$capability = key( $meta );
		if ( isset( $types_of_users[ $capability ] ) ) {
			$types_of_users[ $capability ]++;
		} else {
			$types_of_users[ $capability ] = 1;
		}
		if ( $capability === 'subscriber' ) {
			continue; // Hide subscribers
		}
		if ( $capability === 'contributor' ) {
			$capability = 'collaborator';
		}

		if ( $displayed < $limit ) {
			echo '<tr><td>' . get_avatar( $user->user_id, 32 ) . '</td><td>' . get_userdata( $user->user_id )->display_name . ' - ' . ucfirst( $capability ) . '</td></tr>';
			$displayed++;
		}
	}
	echo '</table>';

	echo '<p>';
	printf( __( '%1$s total users: ', 'pressbooks' ), count( $users ) );
	$types_of_totals = [];
	foreach ( $types_of_users as $capability => $count ) {
		if ( $capability === 'contributor' ) {
			$capability = 'collaborator';
		}
		if ( $count > 1 ) {
			$capability .= 's'; // Plural
		}
		$types_of_totals[] = "{$count} {$capability}";
	}
	echo \Pressbooks\Utility\oxford_comma( $types_of_totals );
	echo '.</p>';

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
 * Init pb_network_integrations menu, removes itself from sub-menus
 *
 * @since 5.3.0
 *
 * @return string
 */
function init_network_integrations_menu() {
	$parent_slug = 'pb_network_integrations';
	static $init_pb_network_integrations_menu = false;
	if ( ! $init_pb_network_integrations_menu ) {
		add_menu_page(
			__( 'Integrations', 'pressbooks-lti-provider' ),
			__( 'Integrations', 'pressbooks-lti-provider' ),
			'manage_network',
			$parent_slug,
			'',
			'dashicons-networking'
		);
		add_action(
			'admin_bar_init', function () {
				remove_submenu_page( 'pb_network_integrations', 'pb_network_integrations' );
			}
		);
		$init_pb_network_integrations_menu = true;
	}
	return $parent_slug;
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
