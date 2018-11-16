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
 * Change redirect upon login to user's My Catalog page
 *
 * @param string $redirect_to
 * @param string $request_redirect_to
 * @param \WP_User $user
 *
 * @return string
 */
function login( $redirect_to, $request_redirect_to, $user ) {

	if ( false === is_a( $user, 'WP_User' ) ) {
		// Unknown user, bail with default
		return $redirect_to;
	}

	if ( is_super_admin( $user->ID ) ) {
		// This is an admin, don't mess
		return $redirect_to;
	}

	$blogs = get_blogs_of_user( $user->ID );
	if ( array_key_exists( get_current_blog_id(), $blogs ) ) {
		// Yes, user has access to this blog
		return $redirect_to;
	}

	if ( $user->primary_blog ) {
		// Force redirect the user to their blog or, if they have more than one, to their catalog, bypass wp_safe_redirect()
		if ( count( $blogs ) > 1 ) {
			$redirect = get_blogaddress_by_id( $user->primary_blog ) . 'wp-admin/index.php?page=pb_catalog';
		} else {
			$redirect = get_blogaddress_by_id( $user->primary_blog ) . 'wp-admin/';
		}
		location( $redirect );
	}

	// User has no primary_blog? Make them sign-up for one
	return network_site_url( '/wp-signup.php' );
}


/**
 * Centralize flush_rewrite_rules() in one single function so that rule does not kill the other
 */
function flusher() {
	$number = 3; // Increment this number when you need to re-run flush_rewrite_rules()
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
		$foo = new \Pressbooks\Modules\Export\Xhtml\Xhtml11( $args );
		$foo->transform();
		exit;
	}

	if ( 'htmlbook' === $format ) {

		$args = [];
		$foo = new \Pressbooks\Modules\Export\HTMLBook\HTMLBook( $args );
		$foo->transform();
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
 * PB API v1
 * Adding a rewrite rule for Book API
 *
 * @see https://github.com/pressbooks/pb-api
 * @deprecated
 */
function rewrite_rules_for_api() {
	add_rewrite_endpoint( 'api', EP_ROOT );
	add_action( 'template_redirect', __NAMESPACE__ . '\do_api', 0 );
}

/**
 * PB API v1
 * Expects the pattern `api/v1/books/{id}`
 *
 * @see https://github.com/pressbooks/pb-api
 * @deprecated
 */
function do_api() {
	// Don't do anything and return if `api` isn't part of the URL
	if ( ! array_key_exists( 'api', $GLOBALS['wp_query']->query_vars ) ) {
		return;
	}

	// Support only GET requests for now
	if ( 'GET' !== $_SERVER['REQUEST_METHOD'] ) {
		\Pressbooks\Modules\Api_v1\Api::apiErrors( 'method' );
	}

	// Deal with the rest of the URL
	$nouns = get_query_var( 'api' );
	if ( '' === trim( $nouns, '/' ) || empty( $nouns ) ) {
		\Pressbooks\Modules\Api_v1\Api::apiErrors( 'resource' );
	}

	// parse url, at minimum we need `v1` and `books`
	$parts = explode( '/', $nouns );

	// required 'v1'
	$version = array_shift( $parts );

	// required 'books'
	$resource = array_shift( $parts );

	// optional 'id'
	$books_id = ( isset( $parts[0] ) ) ? $parts[0] : '';
	$variations = [];

	if ( 'v1' !== $version ) {
		\Pressbooks\Modules\Api_v1\Api::apiErrors( 'version' );
	}

	// Filter user input
	if ( is_array( $_GET ) ) {

		$args = [
			'titles' => [
				'filter' => FILTER_SANITIZE_STRING,
				'flags' => FILTER_FLAG_STRIP_HIGH,
			],
			'offset' => FILTER_SANITIZE_NUMBER_INT,
			'limit' => FILTER_SANITIZE_NUMBER_INT,
			'json' => FILTER_SANITIZE_NUMBER_INT,
			'xml' => FILTER_SANITIZE_NUMBER_INT,
			'subjects' => [
				'filter' => FILTER_SANITIZE_STRING,
				'flags' => FILTER_FLAG_STRIP_LOW,
			],
			'authors' => [
				'filter' => FILTER_SANITIZE_STRING,
				'flags' => FILTER_FLAG_STRIP_LOW,
			],
			'licenses' => [
				'filter' => FILTER_SANITIZE_STRING,
				'flags' => FILTER_FLAG_STRIP_HIGH,
			],
			'keywords' => [
				'filter' => FILTER_SANITIZE_STRING,
				'flags' => FILTER_FLAG_STRIP_LOW,
			],
		];

		$variations = filter_input_array( INPUT_GET, $args, false );

		if ( $variations ) {
			// Trim whitespace
			array_filter( $variations, __NAMESPACE__ . '\trim_value' );
		}
	}

	switch ( $resource ) {
		case 'books':
			try {
				new \Pressbooks\Modules\Api_v1\Books\BooksApi( $books_id, $variations );
			} catch ( \Exception $e ) {
				echo $e->getMessage();
			}
			break;
		case 'docs':
			$docs = [
				PB_PLUGIN_DIR . 'vendor/pressbooks/pb-api/includes/modules/api_v1/docs/api-documentation.php', // Packaged
				WP_CONTENT_DIR . '/../../vendor/pressbooks/pb-api/includes/modules/api_v1/docs/api-documentation.php', // Bedrock
				WP_CONTENT_DIR . '/vendor/pressbooks/pb-api/includes/modules/api_v1/docs/api-documentation.php', // Maybe here?
			];
			foreach ( $docs as $path ) {
				if ( file_exists( $path ) ) {
					require( $path );
					break;
				}
			}
			break;
		default:
			\Pressbooks\Modules\Api_v1\Api::apiErrors( 'resource' );
			break;
	}

	exit;
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
	add_filter( 'template_redirect', __NAMESPACE__ . '\do_open', 0 );
}

/**
 * Handle URL request for download of a publicly available file.
 *
 * @author Brad Payne <brad@bradpayne.ca>
 * @copyright 2014 Brad Payne
 * @since 3.8.0
 */
function do_open() {

	if ( ! array_key_exists( 'open', $GLOBALS['wp_query']->query_vars ) ) {
		// Don't do anything and return
		return;
	}

	$action = get_query_var( 'open' );

	if ( 'download' === $action ) {
		// Download
		if ( ! empty( $_GET['type'] ) ) {
			$files = \Pressbooks\Utility\latest_exports();
			if ( isset( $files[ $_GET['type'] ] ) ) {
				$filepath = \Pressbooks\Modules\Export\Export::getExportFolder() . $files[ $_GET['type'] ];
				force_download( $filepath );
				exit;
			}
		}
	}

	wp_die( __( 'Error: Unknown export format.', 'pressbooks' ) );
}


/**
 * Force download
 *
 * @param string $filepath fullpath to a file
 * @param bool $inline
 */
function force_download( $filepath, $inline = false ) {
	$filename = basename( $filepath );
	if ( ! is_readable( $filepath ) ) {
		// Cannot read file
		wp_die(
			__( 'File not found', 'pressbooks' ) . ": $filename", '', [
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
		header( 'Content-Disposition: inline; filename="' . $filename . '"' );
	} else {
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
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
