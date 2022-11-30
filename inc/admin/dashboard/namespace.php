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
		'title' => esc_html__( 'Pressbooks announcements', 'pressbooks' ),
	];
}

/**
 * @param array $options
 * @return bool
 */
function should_display_custom_feed( array $options ): bool {
	if ( ! $options['display_feed'] ) {
		return false;
	}

	if ( has_filter( 'display_custom_feed' ) ) {
		return apply_filters( 'display_custom_feed', $options['url'] );
	}

	return true;
}

/**
 *  Remove unwanted network Dashboard widgets, add our desired widgets.
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

	if ( should_display_custom_feed( $options ) ) {
		add_meta_box( 'pb_dashboard_widget_blog', $options['title'], __NAMESPACE__ . '\display_pressbooks_blog', 'dashboard-network', 'side', 'low' );
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
	add_meta_box( 'pb_dashboard_widget_book', ( $book_name ? $book_name : esc_html__( 'My Book', 'pressbooks' ) ), __NAMESPACE__ . '\display_book_widget', 'dashboard', 'normal', 'high' );
	add_meta_box( 'pb_dashboard_widget_users', esc_html__( 'Users', 'pressbooks' ), __NAMESPACE__ . '\display_users_widget', 'dashboard', 'side', 'high' );
	add_meta_box( 'pb_dashboard_widget_support', esc_html__( 'Need Help?', 'pressbooks' ), __NAMESPACE__ . '\display_support_widget', 'dashboard', 'normal', 'high' );

	// Add our news feed.
	$options = array_map(
		'stripslashes_deep', get_site_option(
			'pressbooks_dashboard_feed', get_rss_defaults()
		)
	);

	if ( should_display_custom_feed( $options ) ) {
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
		esc_html__( 'Book Permissions', 'pressbooks' ),
		__NAMESPACE__ . '\lowly_user_callback',
		'dashboard-user',
		'normal',
		'high'
	);
	add_meta_box( 'pb_dashboard_widget_support', esc_html__( 'Need Help?', 'pressbooks' ), __NAMESPACE__ . '\display_support_widget', 'dashboard-user', 'normal', 'high' );

}

/**
 * Adds pending invitations meta box
 *
 * @param string $screen
 */
function add_pending_invitation_meta_box( $screen ) {
	add_meta_box(
		'pb_dashboard_widget_book_invitations',
		esc_html__( 'Book Invitations', 'pressbooks' ),
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
		$article = preg_match( '/^[aeiou]/i', $metadata['role'] ) ? __( 'an', 'pressbooks' ) : __( 'a', 'pressbooks' );

		echo '
		<div>
			<p>' . sprintf( esc_html__( 'You have been invited to join %1s as %2$s %3$s', 'pressbooks' ),
			sprintf( '<a href="%1$s">%2$s</a>', esc_url( home_url() ), esc_html( get_site_meta( $metadata['blog_id'], 'pb_title', true ) ) ),
		esc_html( $article ), esc_html( $metadata['role'] ) ) . "</p>
			<a class='button button-primary' href='" . esc_url( home_url( '/newbloguser/' . $metadata['key'] ) ) . "'>" . esc_html__( 'Accept', 'pressbooks' ) . '</a>
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
	echo '<p>' . sprintf( esc_html__( 'Welcome to %s', 'pressbooks' ), esc_html( get_bloginfo( 'name', 'display' ) ) ) . '!</p>';
	$user_has_books = count( get_blogs_of_user( get_current_user_id() ) ) > 1;
	if ( ! $user_has_books ) {
		echo '<p>' . esc_html__( 'You do not currently have access to any books on this network.', 'pressbooks' ) . '</p>';
	}
	$contact = \Pressbooks\Utility\main_contact_email();
	// Values can be 'all', 'none', 'blog', or 'user', @see wp-signup.php
	$active_signup = apply_filters( 'wpmu_active_signup', get_site_option( 'registration', 'none' ) );
	if ( in_array( $active_signup, [ 'none', 'user' ], true ) ) {
		echo '<p>' . esc_html__( 'This network does not allow users to create new books. To create a new book, please contact a network manager', 'pressbooks' );
		if ( ! empty( $contact ) && strpos( $contact, '@pressbooks.com' ) === false ) {
			echo ' ' . esc_html__( 'at ', 'pressbooks' ) . sprintf( '<a href="%1$s">%2$s</a>', esc_url( "mailto:{$contact}" ), esc_html( $contact ) );
		}
		echo '.</p>';
	} else {
		$href_create = network_home_url( 'wp-signup.php' );
		$text_create = esc_html__( 'Create a book', 'pressbooks' );
		$href_clone = admin_url( 'admin.php?page=pb_cloner' );
		$text_clone = esc_html__( 'Clone a book', 'pressbooks' );
		echo '<p>' . sprintf( esc_html__( 'Get started on your next publishing project by creating a new book or cloning an existing book. The %1$s includes thousands of openly licensed books available for cloning.', 'pressbooks' ), sprintf( '<a href="https://pressbooks.directory" target="_blank">%s</a>', esc_html__( 'Pressbooks Directory', 'pressbooks' ) ) ) . "</p><p><a class='button button-hero button-primary create-book' href='" . esc_url( $href_create ) . "'>" . esc_html( $text_create ) . "</a><a class='button button-hero button-primary clone-book' href='" . esc_url( $href_clone ) . "'>" . esc_html( $text_clone ) . '</a></p>';
	}
	if ( ! $user_has_books ) {
		echo '<p>' . esc_html__( 'You can also request access to an existing book by contacting your network manager', 'pressbooks' );
		if ( ! empty( $contact ) && strpos( $contact, '@pressbooks.com' ) === false ) {
			echo ' ' . esc_html__( 'at ', 'pressbooks' ) . sprintf( '<a href="%1$s">%2$s</a>', esc_url( "mailto:{$contact}" ), esc_html( $contact ) );
		}
		echo '.</p>';
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
						current_user_can( 'edit_post', $component['ID'] ) ? sprintf( '<a href="%1$s">%2$s</a>', esc_url( admin_url( 'post.php?post=' . $component['ID'] . '&action=edit' ) ), esc_html( $title ) ) : esc_html( $title )
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
						current_user_can( 'edit_post', $part['ID'] ) ? sprintf( '<a href="%1$s">%2$s</a>', esc_url( admin_url( 'post.php?post=' . $part['ID'] . '&action=edit' ) ), esc_html( $title ) ) : esc_html( $title )
					);
				}
				?>
				<ul class='chapters'>
				<?php
				foreach ( $part['chapters'] as $component ) {
					$title = ( ! empty( $component['post_title'] ) ? $component['post_title'] : '&hellip;' );
					printf(
						"<li>%s</li>\n",
						current_user_can( 'edit_post', $component['ID'] ) ? sprintf( '<a href="%1$s">%2$s</a>', esc_url( admin_url( 'post.php?post=' . $component['ID'] . '&action=edit' ) ), esc_html( $title ) ) : esc_html( $title )
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
						current_user_can( 'edit_post', $component['ID'] ) ? sprintf( '<a href="%1$s">%2$s</a>', esc_url( admin_url( 'post.php?post=' . $component['ID'] . '&action=edit' ) ), esc_html( $title ) ) : esc_html( $title )
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
		<a href="post-new.php?post_type=chapter"><?php _e( 'Add', 'pressbooks' ); ?></a> | <a class="organize" href="<?php echo esc_url( admin_url( 'admin.php?page=pb_organize' ) ); ?>"><?php _e( 'Organize', 'pressbooks' ); ?></a>
	</div>
		<?php
	}
}

/**
 * Displays the Pressbooks Blog RSS as a widget
 */
function display_pressbooks_blog() {
	$rss = get_site_transient( 'pb_rss_widget' );

	if ( ! $rss ) {
		$options = array_map(
			'stripslashes_deep', get_site_option(
				'pressbooks_dashboard_feed', get_rss_defaults()
			)
		);

		ob_start();

		wp_widget_rss_output(
			[
				'url' => $options['url'],
				'items' => 3,
				'show_summary' => 1,
				'show_author' => 0,
				'show_date' => 0,
			]
		);

		$rss = ob_get_clean();

		set_site_transient( 'pb_rss_widget', $rss, DAY_IN_SECONDS );
	}
	// @codingStandardsIgnoreLine
	echo $rss;
}

/**
 * Displays a Support widget
 */
function display_support_widget() {
	$contact = \Pressbooks\Utility\main_contact_email();
	echo '<p>' . /* translators: %s: URL to Pressbooks User Guide */ sprintf( esc_html__( 'Consult the %s.', 'pressbooks' ), sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://guide.pressbooks.com' ), esc_html__( 'Pressbooks User Guide', 'pressbooks' ) ) )
			. '</p><p>' . /* translators: %s: URL to Pressbooks YouTube channel */ sprintf( esc_html__( 'Watch tutorials on the %s.', 'pressbooks' ), sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://www.youtube.com/c/Pressbooks/playlists' ), esc_html__( 'Pressbooks YouTube channel', 'pressbooks' ) ) )
			. '</p><p>' . /* translators: %s: URL to Pressbooks training webinars */ sprintf( esc_html__( 'Attend a %s.', 'pressbooks' ), sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://pressbooks.com/webinars/' ), esc_html__( 'live training webinar', 'pressbooks' ) ) )
			. '</p><p>' . /* translators: %s: URL to Pressbooks community forum */ sprintf( esc_html__( 'Participate in the %s.', 'pressbooks' ), sprintf( '<a href="%1$s" target="_blank">%2$s</a>', esc_url( 'https://pressbooks.community' ), esc_html__( 'community forum', 'pressbooks' ) ) ) . '</p>';
	if ( ! empty( $contact ) && strpos( $contact, '@pressbooks.com' ) === false ) {
		echo '<p>' . /* translators: %s: email address for network manager */ sprintf( esc_html__( 'For additional support, contact your network manager at %s.', 'pressbooks' ), sprintf( '<a href="%1$s">%2$s</a>', esc_url( "mailto:$contact" ), esc_html( $contact ) ) ) . '</p>';
	}
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
			echo '<tr><td>' . get_avatar( $user->user_id, 32 ) . '</td><td>' . esc_html( get_userdata( $user->user_id )->display_name ) . ' - ' . esc_html( ucfirst( $capability ) ) . '</td></tr>';
			$displayed++;
		}
	}
	echo '</table>';

	echo '<p>';
	printf( esc_html__( '%1$s total users: ', 'pressbooks' ), count( $users ) );
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
	echo esc_html( \Pressbooks\Utility\oxford_comma( $types_of_totals ) );
	echo '.</p>';

	echo '<div class="part-buttons"> <a href="user-new.php">' . esc_html__( 'Add', 'pressbooks' ) . '</a> | <a class="remove" href="users.php">' . esc_html__( 'Organize', 'pressbooks' ) . '</a></div>';
}

/**
 *
 */
function add_menu() {
	add_submenu_page(
		'settings.php',
		esc_html__( 'Dashboard', 'pressbooks' ),
		esc_html__( 'Dashboard', 'pressbooks' ),
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
			esc_html__( 'Integrations', 'pressbooks-lti-provider' ),
			esc_html__( 'Integrations', 'pressbooks-lti-provider' ),
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
		esc_html__( 'Dashboard Feed', 'pressbooks' ),
		__NAMESPACE__ . '\dashboard_feed_callback',
		$_page
	);

	add_settings_field(
		'display_feed',
		esc_html__( 'Display Feed', 'pressbooks' ),
		__NAMESPACE__ . '\display_feed_callback',
		$_page,
		'dashboard_feed',
		[
			'description' => esc_html__( 'Display an RSS feed widget on the dashboard.', 'pressbooks' ),
		]
	);

	add_settings_field(
		'title',
		esc_html__( 'Feed Title', 'pressbooks' ),
		__NAMESPACE__ . '\title_callback',
		$_page,
		'dashboard_feed'
	);

	add_settings_field(
		'url',
		esc_html__( 'Feed URL', 'pressbooks' ),
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
	<p><?php esc_html__( 'Adjust settings for your dashboard RSS feed widget below.', 'pressbooks' ); ?></p>
	<?php
}

function display_feed_callback( $args ) {
	$options = get_site_option(
		'pressbooks_dashboard_feed', get_rss_defaults()
	);

	echo '<input id="display_feed" name="pressbooks_dashboard_feed[display_feed]" type="checkbox" value="1" ' . checked( $options['display_feed'], 1, false ) . '/><p class="description">' . esc_html( $args['description'] ) . '</p>';
}

function title_callback( $args ) {
	$options = array_map(
		'stripslashes_deep', get_site_option(
			'pressbooks_dashboard_feed', get_rss_defaults()
		)
	);
	echo '<input id="title" name="pressbooks_dashboard_feed[title]" type="text" value="' . esc_url( $options['title'] ) . '" />';
}

function url_callback( $args ) {
	$options = get_site_option(
		'pressbooks_dashboard_feed', get_rss_defaults()
	);
	echo '<input id="url" name="pressbooks_dashboard_feed[url]" type="text" value="' . esc_url( $options['url'] ) . '" />';
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
