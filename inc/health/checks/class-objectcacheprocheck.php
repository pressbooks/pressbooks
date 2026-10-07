<?php

namespace Pressbooks\Health\Checks;

use Pressbooks\Health\Check;
use Pressbooks\Health\Result;
use RedisCachePro\Diagnostics\Diagnostics;

class ObjectCacheProCheck extends Check {
	public function run(): Result {
		$result = Result::make();

		if ( ! is_plugin_active_for_network( 'object-cache-pro/object-cache-pro.php' ) ) {
			return $result->ok( __( 'Object Cache Pro plugin is either inactive or not installed.', 'pressbooks' ) );
		}

		global $wp_object_cache;

		$diagnostics = ( new Diagnostics( $wp_object_cache ) )->toArray();

		/** @var Diagnostic $status */
		$status = $diagnostics[ Diagnostics::GENERAL ]['status'];

		/** @var Diagnostic $license */
		$license = $diagnostics[ Diagnostics::GENERAL ]['license'];

		if ( $status->hasIssue() ) {
			return $result->failed( __( 'Could not connect to Redis cache.', 'pressbooks' ) );
		}

		if ( $license->hasIssue() ) {
			return $result->failed( __( 'License token is not valid or it\'s missing.', 'pressbooks' ) );
		}

		return $result->ok();
	}
}
