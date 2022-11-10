<?php

namespace Pressbooks\Health\Checks;

use Pressbooks\Health\Check;
use RedisCachePro\Diagnostics\Diagnostics;

class CacheCheck extends Check {
	public function __construct() {
		$this->name = 'cache';
	}

	public function run(): array {
		global $wp_object_cache;

		$has_issue = false;

		if ( ! is_plugin_active_for_network( 'object-cache-pro/object-cache-pro.php' ) ) {
			// TODO: how to handle non object cache pro cache?
			return [
				'status' => 'Unknown',
				'has_issue' => false,
			];
		}

		$diagnostics = ( new Diagnostics( $wp_object_cache ) )
			->withFilesystemAccess()
			->toArray();

		/** @var Diagnostic $status */
		$status = $diagnostics[ Diagnostics::GENERAL ]['status'];

		/** @var Diagnostic $license */
		$license = $diagnostics[ Diagnostics::GENERAL ]['license'];

		/** @var Diagnostic $filesystem */
		$filesystem = $diagnostics[ Diagnostics::GENERAL ]['filesystem'];

		$errors = $diagnostics[ Diagnostics::ERRORS ];

		if ( $status->hasIssue() || $license->hasIssue() || $license->hasIssue() || ! empty( $errors ) ) {
			$has_issue = true;
		}

		return [
			'status' => (string) $status->withComment(),
			'license' => (string) $license->withComment(),
			'filesystem' => (string) $filesystem->withComment(),
			'has_issue' => $has_issue,
			'errors' => $errors,
		];
	}
}
