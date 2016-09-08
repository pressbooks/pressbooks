<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv2 (or any later version)
 */
namespace Pressbooks\Redirect;


/**
 * Fail-safe Location: redirection
 *
 * @param string $href a uniform resource locator (URL)
 */
function location( $href ) {

	$href = filter_var( $href, FILTER_SANITIZE_URL );

	if ( ! headers_sent() ) {
		header( "Location: $href" );
	} else {
		// Javascript hack
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

	$pull_the_lever = false;

	// @see \Pressbooks\PostType\register_post_types
	$set = get_option( 'pressbooks_flushed_post_type' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks_flushed_post_type', true );
	}

	// @see rewrite_rules_for_format()
	$set = get_option( 'pressbooks_flushed_format' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks_flushed_format', true );
	}

	// @see rewrite_rules_for_catalog()
	$set = get_option( 'pressbooks_flushed_catalog' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks_flushed_catalog', true );
	}

	// @see rewrite_rules_for_sitemap()
	$set = get_option( 'pressbooks_flushed_sitemap' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks_flushed_sitemap', true );
	}

	// @see \Pressbooks\VIP\Upgrade\rewrite_rules_for_upgrade()
	$set = get_option( 'pressbooks-vip_flushed_upgrade' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks-vip_flushed_upgrade', true );
	}

	$set = get_option( 'pressbooks_flushed_api' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks_flushed_api', true );
	}

	$set = get_option( 'pressbooks_flushed_open' );
	if ( ! $set ) {
		$pull_the_lever = true;
		update_option( 'pressbooks_flushed_open', true );
	}

	if ( $pull_the_lever ) {
		flush_rewrite_rules( false );
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

	if ( 'xhtml' == $format ) {

		$args = array();
		$foo = new \Pressbooks\Modules\Export\Xhtml\Xhtml11( $args );
		$foo->transform();
		exit;
	}

	if ( 'wxr' == $format ) {

		$args = array();
		$foo = new \Pressbooks\Modules\Export\WordPress\Wxr( $args );
		$foo->transform();
		exit;
	}

	wp_die( __( 'Error: Unknown export format.', 'pressbooks' ) );
}


/**
 * Add a rewrite rule for the keyword "catalog"
 *
 * @see flusher()
 */
function rewrite_rules_for_catalog() {

	add_rewrite_endpoint( 'catalog', EP_ROOT );
	add_filter( 'template_redirect', __NAMESPACE__ . '\do_catalog', 0 );
}


/**
 * Display catalog
 */
function do_catalog() {

	if ( ! array_key_exists( 'catalog', $GLOBALS['wp_query']->query_vars ) ) {
		// Don't do anything and return
		return;
	}

	$user_login = get_query_var( 'catalog' );
	if ( ! is_main_site() ) {
		// Hard redirect
		location( network_site_url( "/catalog/$user_login" ) );
	}

	$user = get_user_by( 'login', $user_login );
	if ( false == $user ) {
		$msg = __( 'No catalog was found for user', 'pressbooks' ) . ": $user_login";
		$args = array( 'response' => '404' );
		wp_die( $msg, '', $args );
	}

	\Pressbooks\Catalog::loadTemplate( $user->ID );
	exit;
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
 * Adding a rewrite rule for Book API
 */
function rewrite_rules_for_api() {
	add_rewrite_endpoint( 'api', EP_ROOT );
	add_action( 'template_redirect', __NAMESPACE__ . '\do_api', 0 );
}

/**
 * Expects the pattern `api/v1/books/{id}`
 *
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
	$variations = array();

	if ( 'v1' !== $version ) {
		\Pressbooks\Modules\Api_v1\Api::apiErrors( 'version' );
	}

	// Filter user input
	if ( is_array( $_GET ) ) {

		$args = array(
		    'titles' => array(
			'filter' => FILTER_SANITIZE_STRING,
			'flags' => FILTER_FLAG_STRIP_HIGH
		    ),
		    'offset' => FILTER_SANITIZE_NUMBER_INT,
		    'limit' => FILTER_SANITIZE_NUMBER_INT,
		    'json' => FILTER_SANITIZE_NUMBER_INT,
		    'xml' => FILTER_SANITIZE_NUMBER_INT,
		    'subjects' => array(
			'filter' => FILTER_SANITIZE_STRING,
			'flags' => FILTER_FLAG_STRIP_LOW
		    ),
		    'authors' => array(
			'filter' => FILTER_SANITIZE_STRING,
			'flags' => FILTER_FLAG_STRIP_LOW
		    ),
		    'licenses' => array(
			'filter' => FILTER_SANITIZE_STRING,
			'flags' => FILTER_FLAG_STRIP_HIGH
		    ),
		    'keywords' => array(
			'filter' => FILTER_SANITIZE_STRING,
			'flags' => FILTER_FLAG_STRIP_LOW
		    ),
		);

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
			require( PB_PLUGIN_DIR . 'includes/modules/api_v1/docs/api-documentation.php');
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

	if ( 'download' == $action ) {
		// Download
		if ( ! empty( $_GET['filename'] ) && ! empty( $_GET['type'] ) ) {
			$filename = sanitize_file_name( $_GET['filename'] );

			switch ( $_GET['type'] ) {
				case 'xhtml':
					$ext = 'html';
					break;
				case 'wxr':
					$ext = 'xml';
					break;
				case 'epub3':
					$ext = '_3.epub';
					break;
				case 'vanillawxr':
					$ext = '_vanilla.xml';
					break;
				case 'mpdf':
					$ext = '_oss.pdf';
					break;
				default:
					$ext = $_GET['type'];
					break;
			}

			$filename = $filename . '.' . $ext;
			download_open_export_file( $filename );
		}
	}

	wp_die( __( 'Error: Unknown export format.', 'pressbooks' ) );
}


/**
 * Handle download of a publicly available file.
 *
 * @author Brad Payne <brad@bradpayne.ca>
 * @copyright 2014 Brad Payne
 * @since 3.8.0
 */
function download_open_export_file( $filename ) {

	$filepath = \Pressbooks\Modules\Export\Export::getExportFolder() . $filename;
	if ( ! is_readable( $filepath ) ) {
		// Cannot read file
		wp_die( __( 'File not found', 'pressbooks' ) . ": $filename", '', array( 'response' => 404 ) );
	}

	// Force download
	set_time_limit( 0 );
	header( 'Content-Description: File Transfer' );
	header( 'Content-Type: ' . \Pressbooks\Modules\Export\Export::mimeType( $filepath ) );
	header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
	header( 'Content-Transfer-Encoding: binary' );
	header( 'Expires: 0' );
	header( 'Cache-Control: must-revalidate, post-check=0, pre-check=0' );
	header( 'Pragma: public' );
	header( 'Content-Length: ' . filesize( $filepath ) );
	@ob_clean();
	flush();
	while ( @ob_end_flush() ); // Fix out-of-memory problem
	readfile( $filepath );

	exit;
}

/**
 * Callback function that strips whitespace characters
 *
 * @see array_filter()
 * @param array $value
 */
function trim_value( &$value ) {
	$value = trim( $value );
}
