<?php

namespace Pressbooks\Health\Checks;

use Pressbooks\Health\Check;

class DatabaseCheck extends Check {
	public function __construct() {
		$this->name = 'database';
	}

	public function run(): array {
		global $wpdb;

		$has_issue = false;

		if ( ! $wpdb->check_connection() ) {
			$has_issue = true;
		}

		return [
			'status' => $has_issue ? 'Not connected' : 'Connected',
			'has_issue' => $has_issue,
		];
	}
}
