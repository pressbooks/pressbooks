<?php

namespace Pressbooks\Health\Checks;

use Illuminate\Support\Str;
use Pressbooks\Health\Check;
use Pressbooks\Health\Result;

class CacheCheck extends Check {
	public function __construct() {
		$this->name = 'cache';
	}

	public function run(): Result {
		$result = Result::make();

		return $this->canWriteValuesToCache()
			? $result->ok()
			: $result->failed( 'Could not set or retrieve an application cache value.' );
	}

	protected function canWriteValuesToCache(): bool {
		$key = 'pressbooks-health:cache';
		$expected_value = Str::random( 20 );

		wp_cache_add( $key, $expected_value );

		$actual_value = wp_cache_get( $key );

		wp_cache_delete( $key );

		return $actual_value === $expected_value;
	}
}
