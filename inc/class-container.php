<?php
use Illuminate\Container\Container as LaravelContainer;

class Container {
	public static function get( $key ) {
		return LaravelContainer::get( $key );
	}
}
