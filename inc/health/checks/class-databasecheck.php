<?php

namespace Pressbooks\Health\Checks;

use Pressbooks\Health\Check;
use Pressbooks\Health\Result;

class DatabaseCheck extends Check {
	public function run(): Result {
		$result = Result::make();

		return $this->checkConnection()
			? $result->ok()
			: $result->failed( 'Could not connect to the database.' );
	}

	protected function checkConnection(): bool {
		global $wpdb;

		return $wpdb->check_connection();
	}
}
