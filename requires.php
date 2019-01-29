<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once( __DIR__ . '/inc/admin/branding/namespace.php' );
require_once( __DIR__ . '/inc/analytics/namespace.php' );
require_once( __DIR__ . '/inc/api/namespace.php' );
require_once( __DIR__ . '/inc/editor/namespace.php' );
require_once( __DIR__ . '/inc/image/namespace.php' );
require_once( __DIR__ . '/inc/l10n/namespace.php' );
require_once( __DIR__ . '/inc/media/namespace.php' );
require_once( __DIR__ . '/inc/metadata/namespace.php' );
require_once( __DIR__ . '/inc/modules/export/namespace.php' );
require_once( __DIR__ . '/inc/posttype/namespace.php' );
require_once( __DIR__ . '/inc/redirect/namespace.php' );
require_once( __DIR__ . '/inc/registration/namespace.php' );
require_once( __DIR__ . '/inc/sanitize/namespace.php' );
require_once( __DIR__ . '/inc/theme/namespace.php' );
require_once( __DIR__ . '/inc/utility/namespace.php' );

// H5P
if ( is_file( WP_PLUGIN_DIR . '/h5p/autoloader.php' ) ) {
	require_once( WP_PLUGIN_DIR . '/h5p/autoloader.php' );
}
