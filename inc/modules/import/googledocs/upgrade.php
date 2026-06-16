<?php
/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

function purge_legacy_tokens( string $marker_option_key ): void {
	if ( get_site_option( $marker_option_key ) === '1' ) {
		return;
	}

	global $wpdb;
	$count = $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key = %s",
			'pressbooks_google_docs_token'
		)
	);

	update_site_option( $marker_option_key, '1' );

	if ( function_exists( 'error_log' ) && ! defined( 'WP_TESTS_DOMAIN' ) ) {
		error_log( 'pb.gdocs.tokens_purged count=' . (int) $count );
	}
}
