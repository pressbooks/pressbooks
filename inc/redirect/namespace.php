<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Redirect;

/**
 * Fail-safe Location: redirection
 *
 * @param string $href a uniform resource locator (URL)
 */
function location( $href ) {
	$href = filter_var( $href, FILTER_SANITIZE_URL );
	if ( defined( 'WP_TESTS_MULTISITE' ) ) {
		$GLOBALS['_pb_redirect_location'] = $href; // PHPUnit, Mock, ...
	} else {
		if ( ! headers_sent() ) {
			header( "Location: $href" );
		} else {
			// Javascript hack
			$href = str_replace( "'", "\'", $href );
			echo "
			<script type='text/javascript'>
			// <![CDATA[
			window.location = '{$href}';
			// ]]>
			</script>
			";
		}
		exit; // Quit script
	}
}


/**
 * Centralize flush_rewrite_rules() in one single function so that rule does not kill the other
 */
function flusher() {
	$number = 3; // Increment this number when you need to re-run flush_rewrite_rules() on next code deployment
	if ( absint( get_option( 'pressbooks_flusher', 1 ) ) < $number ) {
		flush_rewrite_rules( false );
		update_option( 'pressbooks_flusher', $number );
	}
}

/**
 * Migrate generated content to namespaced folder
 *
 * @since 5.0.0
 *
 * @see \Pressbooks\Utility\get_generated_content_path
 */
function migrate_generated_content() {
	if ( ! \Pressbooks\Book::isBook() ) {
		return;
	}

	$option_name = 'pressbooks_migrated_generated_content';
	$is_migrated = get_option( $option_name, false );
	if ( $is_migrated === false ) {
		$move = [
			'/cache',
			'/css',
			'/custom-css',
			'/exports',
			'/lock',
			'/scss',
			'/scss-debug',
		];
		$source_dir = untrailingslashit( wp_upload_dir()['basedir'] );
		$dest_dir = untrailingslashit( \Pressbooks\Utility\get_generated_content_path() );

		foreach ( $move as $suffix ) {
			if ( is_dir( "{$source_dir}/{$suffix}" ) ) {
				$ok = @rename( "{$source_dir}/{$suffix}", "{$dest_dir}/{$suffix}" ); // @codingStandardsIgnoreLine
				if ( ! $ok ) {
					\Pressbooks\Utility\debug_error_log( "Failed to migrate: {$source_dir}/{$suffix}" );
				}
			}
		}
		update_option( $option_name, 1 );
	}
}

/**
 * Add a rewrite rule for the keyword "format"
 *
 * @see flusher()
 */
function rewrite_rules_for_format() {

	add_rewrite_endpoint( 'format', EP_ROOT );
	add_filter( 'template_redirect', __NAMESPACE__ . '\do_format', 0 );
}


/**
 * Display book in a custom format.
 */
function do_format() {

	if ( ! array_key_exists( 'format', $GLOBALS['wp_query']->query_vars ) ) {
		// Don't do anything and return
		return;
	}

	$format = get_query_var( 'format' );

	if ( 'xhtml' === $format ) {

		$args = [];
		add_filter( 'pre_determine_locale', '\Pressbooks\L10n\get_locale' );
		$switched_locale = switch_to_locale( \Pressbooks\Modules\Export\Export::locale() );
		$foo = new \Pressbooks\Modules\Export\Xhtml\Xhtml11( $args );
		$foo->transform();
		if ( $switched_locale ) {
			restore_previous_locale();
		}
		exit;
	}

	if ( 'htmlbook' === $format ) {

		$args = [];
		add_filter( 'pre_determine_locale', '\Pressbooks\L10n\get_locale' );
		$switched_locale = switch_to_locale( \Pressbooks\Modules\Export\Export::locale() );
		$foo = new \Pressbooks\Modules\Export\HTMLBook\HTMLBook( $args );
		$foo->transform();
		if ( $switched_locale ) {
			restore_previous_locale();
		}
		exit;
	}

	/**
	 * @since 5.3.0
	 *
	 * @param string $format
	 */
	do_action( 'pb_do_format', $format );

	wp_die( __( 'Error: Unknown export format.', 'pressbooks' ) );
}


/**
 * Add a rewrite rule for the keyword "catalog" (Changed in Pressbooks 4.2)
 *
 * @since 4.2
 * @see flusher()
 */
function rewrite_rules_for_catalog() {
	global $wp;
	$wp->add_query_var( 'pb_catalog_user' );
	add_rewrite_rule( '^catalog/([A-Za-z0-9@\.\-\_]+)$', 'index.php?pagename=pb_catalog&pb_catalog_user=$matches[1]', 'top' );
	add_filter( 'template_include', __NAMESPACE__ . '\do_catalog', 999 ); // Must come after \Roots\Sage\Wrapper\SageWrapping (to override)
}

/**
 * Display catalog
 *
 * @param string $template
 *
 * @return string
 */
function do_catalog( $template ) {
	if ( get_query_var( 'pagename' ) === 'pb_catalog' ) {
		$user = get_user_by( 'login', get_query_var( 'pb_catalog_user' ) );
		if ( $user !== false && is_user_spammy( $user ) === false ) {
			status_header( 200 );
			return \Pressbooks\Catalog::getTemplatePath();
		}
	}
	return $template;
}


/**
 * Add a rewrite rule for sitemap xml
 *
 * @see flusher()
 */
function rewrite_rules_for_sitemap() {

	add_feed( 'sitemap.xml', '\Pressbooks\Utility\do_sitemap' );
}

/**
 * Add a rewrite rule for the keyword "open"
 *
 * @author Brad Payne <brad@bradpayne.ca>
 * @copyright 2014 Brad Payne
 * @since 3.8.0
 * @see flusher()
 */
function rewrite_rules_for_open() {

	add_rewrite_endpoint( 'open', EP_ROOT );
	add_filter( 'template_redirect', function () {
		do_open( function ( $filepath ) {
			force_download( $filepath );
		} );
	}, 0 );
}

/**
 * Handle URL request for download of a publicly available file.
 *
 * @author Brad Payne <brad@bradpayne.ca>
 * @copyright 2014 Brad Payne
 * @since 3.8.0
 */
function do_open( $do_download = null ) {

	if ( ! array_key_exists( 'open', $GLOBALS['wp_query']->query_vars ) ) {
		// Don't do anything and return
		return;
	}

	$action = get_query_var( 'open' );

	if ( 'download' === $action && ! empty( $_GET['type'] ) ) {
		// Download
		$format = $_GET['type'];
		$files = \Pressbooks\Utility\latest_exports();

		if ( isset( $files[ $format ] ) ) {
			do_action( 'store_download_data', $format );

			$filepath = \Pressbooks\Modules\Export\Export::getExportFolder() . $files[ $format ];
			is_callable( $do_download ) && $do_download( $filepath );
			return;
		}
	}

	wp_die( __( 'Error: Unknown export format.', 'pressbooks' ) );
}


/**
 * Force download
 *
 * @param string $filepath fullpath to a file
 * @param bool (optional) $inline
 * @param string $download_filename (optional) change basename the user gets prompted with in their browser
 */
function force_download( $filepath, $inline = false, $download_filename = '' ) {
	if ( empty( $download_filename ) ) {
		$download_filename = basename( $filepath );
	}
	sanitize_file_name( $download_filename );

	if ( ! is_readable( $filepath ) ) {
		// Cannot read file
		wp_die(
			__( 'File not found', 'pressbooks' ) . ": $download_filename", '', [
				'response' => 404,
			]
		);
	}

	// Force download
	// @codingStandardsIgnoreStart
	/**
	 * Maximum execution time, in seconds. If set to zero, no time limit
	 * Overrides PHP's max_execution_time of a Nginx->PHP-FPM->PHP configuration
	 * See also request_terminate_timeout (PHP-FPM) and fastcgi_read_timeout (Nginx)
	 *
	 * @since 5.6.0
	 *
	 * @param int $seconds
	 * @param string $some_action
	 *
	 * @return int
	 */
	@set_time_limit( apply_filters( 'pb_set_time_limit', 0, 'download' ) );
	header( 'Content-Description: File Transfer' );
	header( 'Content-Type: ' . \Pressbooks\Media\mime_type( $filepath ) );
	if ( $inline ) {
		header( 'Content-Disposition: inline; filename="' . $download_filename . '"' );
	} else {
		header( 'Content-Disposition: attachment; filename="' . $download_filename . '"' );
	}
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Pragma: public' );
	header( 'Content-Length: ' . filesize( $filepath ) );
	@ob_clean();
	flush();
	while ( @ob_end_flush() ) {
		// Fix out-of-memory problem
	}
	readfile( $filepath );
	// @codingStandardsIgnoreEnd
}

/**
 * Callback function that strips whitespace characters
 *
 * @see array_filter()
 *
 * @param string $value
 */
function trim_value( &$value ) {
	$value = trim( $value );
}

/**
 * Redirect away from (what we consider) bad WordPress admin pages
 */
function redirect_away_from_bad_urls() {

	if ( wp_doing_ajax() || is_super_admin() ) {
		return; // Do nothing
	}

	$current_blog_id = get_current_blog_id();
	$check_against_url = wp_parse_url( ( is_ssl() ? 'http://' : 'https://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], PHP_URL_PATH );
	$dashboard_url = get_site_url( $current_blog_id, '/wp-admin/' );
	$pb_organize_url = get_site_url( $current_blog_id, '/wp-admin/admin.php?page=pb_organize' );
	$pb_trash_url = get_site_url( $current_blog_id, '/wp-admin/admin.php?page=pb_trash' );

	// ---------------------------------------------------------------------------------------------------------------
	// Don't let users dig through the default trash interface

	if ( preg_match( '~/wp-admin/edit\.php$~', $check_against_url ) ) {
		if ( isset( $_REQUEST['post_status'] ) && $_REQUEST['post_status'] === 'trash' ) {
			// Redirect to the PB trash URL
			location( $pb_trash_url );
		}
		if ( ! empty( $_GET['trashed'] ) ) {
			// User clicked "Move to Trash" in the post editor, redirect to PB organize page
			location( $pb_organize_url );
		}
	}

	// ---------------------------------------------------------------------------------------------------------------
	// If user is on post-new.php, check for valid post_type

	if ( preg_match( '~/wp-admin/post-new\.php$~', $check_against_url ) ) {
		if ( isset( $_REQUEST['post_type'] ) && ! in_array( $_REQUEST['post_type'], \Pressbooks\PostType\list_post_types(), true ) ) {
			$_SESSION['pb_notices'][] = __( 'Unsupported post type.', 'pressbooks' );
			location( $dashboard_url );
		}
	}

	// ---------------------------------------------------------------------------------------------------------------
	// If user is on edit-tags.php, check for valid taxonomy.
	// Non-super admin users should only be able to edit contributors (third-party taxonomies are allowed).

	if ( preg_match( '~/wp-admin/edit-tags\.php$~', $check_against_url ) ) {

		if ( isset( $_REQUEST['taxonomy'] ) && in_array(
			$_REQUEST['taxonomy'],
			/**
			 * Add taxonomies to the blacklist array if you want to prevent non-super admin users from editing them.
			 *
			 * @since 5.3.0
			 */
			apply_filters(
				'pb_taxonomy_blacklist', [
					'category',
					'post_tag',
					'nav_menu',
					'link_category',
					'post_format',
					'front-matter-type',
					'back-matter-type',
					'chapter-type',
					'glossary-type',
					'license',
				]
			),
			true
		) ) {
			$_SESSION['pb_notices'][] = __( 'Unsupported taxonomy.', 'pressbooks' );
			location( $dashboard_url );
		}
	}

	// ---------------------------------------------------------------------------------------------------------------
	// Don't let user go to any of these pages, under any circumstance

	$restricted = [
		'export',
		'import',
		'link-(manager|add)',
		'nav-menus',
		'options-(discussion|media|permalink|reading|writing)',
		'plugin-(install|editor)',
		'theme-editor',
		'update-core',
		'widgets',
	];

	// Todo: Fine grained control over: options-general.php

	$expr = '~/wp-admin/(' . implode( '|', $restricted ) . ')\.php$~';
	if ( preg_match( $expr, $check_against_url ) ) {
		$_SESSION['pb_notices'][] = __( 'You do not have sufficient permissions to access that URL.', 'pressbooks' );
		location( $dashboard_url );
	}
}

/**
 * Programmatically logs a user in
 *
 * @since 5.3.0
 *
 * @param string $username
 *
 * @return bool True if the login was successful; false if it wasn't
 */
function programmatic_login( $username ) {
	if ( is_user_logged_in() ) {
		wp_logout();
	}

	$credentials = [
		'user_login' => $username,
	];

	// In before 20!
	// Hook in earlier than other callbacks to short-circuit them [ @see wp-includes/default-filters.php ]
	add_filter( 'authenticate', __NAMESPACE__ . '\allow_programmatic_login', 10, 3 );
	$user = wp_signon( $credentials );
	remove_filter( 'authenticate', __NAMESPACE__ . '\allow_programmatic_login', 10 );

	if ( is_a( $user, 'WP_User' ) ) {
		wp_set_current_user( $user->ID, $user->user_login );
		if ( is_user_logged_in() ) {
			return true;
		}
	}

	return false;
}

/**
 * An 'authenticate' filter callback that authenticates the user using only the username.
 *
 * To avoid potential security vulnerabilities, this should only be used in the context of a programmatic login,
 * and unhooked immediately after it fires.
 *
 * @param \WP_User $user
 * @param string $username
 * @param string $password
 *
 * @return bool|\WP_User a WP_User object if the username matched an existing user, or false if it didn't
 */
function allow_programmatic_login( $user, $username, $password ) {
	$user = get_user_by( 'login', $username );
	if ( $user !== false && is_user_spammy( $user ) === false ) {
		return $user;
	}
	return false;
}

/**
 * Break Password Redirect Key Loop (WordPress bug)
 *
 * @see https://core.trac.wordpress.org/ticket/45367
 *
 * @param string $redirect_to The redirect destination URL.
 * @param string $requested_redirect_to The requested redirect destination URL passed as a parameter.
 * @param \WP_User|\WP_Error $user
 *
 * @return string
 */
function break_reset_password_loop( $redirect_to, $requested_redirect_to, $user ) {
	if ( $user && $user instanceof \WP_User ) {
		$parsed_url = wp_parse_url( $redirect_to );
		if ( $parsed_url === false ) {
			return $redirect_to;
		}
		if ( strpos( $parsed_url['path'] ?? '', 'wp-login.php' ) === false ) {
			return $redirect_to;
		}
		parse_str( $parsed_url['query'] ?? '', $parsed_query );
		if ( isset( $parsed_query['action'] ) && ( $parsed_query['action'] === 'resetpass' || $parsed_query['action'] === 'rp' ) ) {
			// Break the loop
			return admin_url();
		}
	}
	return $redirect_to;
}
