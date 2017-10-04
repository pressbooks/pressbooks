<?php

namespace Pressbooks;

/**
 * Redundant wrapper around \Illuminate\Container\Container() for backwards compatibility
 */
class Container extends \Illuminate\Container\Container {

	/**
	 * If you add services, don't forget to also edit config/.phpstorm.meta.php
	 *
	 * @param \Illuminate\Contracts\Container\Container $c
	 */
	static function init( $c = null ) {
		if ( is_null( $c ) ) {
			$c = require( __DIR__ . '/../services.php' );
		}
		static::setInstance( $c );
	}

	/**
	 * @param string $var
	 *
	 * @return mixed
	 */
	static function get( $var ) {
		if ( is_null( static::$instance ) ) {
			throw new \LogicException( 'Container not set, call init() or setInstance() before using get().' );
		}
		return static::$instance[ $var ];
	}

	/**
	 * @param string $key
	 * @param mixed $val
	 * @param string $type (optional)
	 * @param bool $replace (optional)
	 */
	static function set( $key, $val, $type = null, $replace = false ) {
		if ( is_null( static::$instance ) ) {
			throw new \LogicException( 'Container not set, call init() or setInstance() before using set().' );
		}

		if ( $replace ) {
			unset( static::$instance[ $key ] );
		}

		if ( ! static::$instance->bound( $key ) ) {
			if ( in_array( $type, [ 'factory', 'bind' ], true ) ) {
				static::$instance->bind( $key, $val );
			} elseif ( in_array( $type, [ 'protect', 'instance' ], true ) ) {
				static::$instance->instance( $key, $val );
			} else {
				static::$instance->singleton( $key, $val );
			}
		}
	}
}
