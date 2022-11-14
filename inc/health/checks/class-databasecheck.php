<?php

namespace Pressbooks\Health\Checks;

use Pressbooks\Health\Check;
use Pressbooks\Health\Result;

class DatabaseCheck extends Check {
	public function __construct() {
		$this->name = 'database';
	}

	public function run(): Result {
		global $wpdb;

		$result = Result::make();

		return $wpdb->check_connection()
			? $result->ok()
			: $result->failed( 'Could not connect to the database' );
	}
}
