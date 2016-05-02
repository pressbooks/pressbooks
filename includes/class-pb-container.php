<?php

namespace Pressbooks;

/**
 * Cheap wrapper around \Pimple\Container() so that we can use
 * PhpStorm IDE to auto-complete things or hot-swap services for Unit Testing.
 */
class Container {
	/**
	 * @var \Pimple\Container
	 */
	protected static $pimple;


	/**
	 * If you add services, don't forget to also edit config/.phpstorm.meta.php
	 *
	 * @param \Pimple\Container $pimple
	 */
	static function init( $pimple = null ) {
		if ( null === $pimple ) {
			static::$pimple = require( __DIR__ . '/../services.php' );
		}
		else {
			static::$pimple = $pimple;
		}
	}


	/**
	 * @param string $var
	 * @return mixed
	 */
	static function get( $var ) {
		if ( ! static::$pimple ) {
			throw new \LogicException( '\Pimple\Container not set, call init() or setPimple() before using get().' );
		}

		return static::$pimple[$var];
	}


	/**
	 * @param string $key
	 * @param mixed $val
	 * @param string $type (optional)
	 */
	static function set( $key, $val, $type = null ) {
		if ( ! static::$pimple ) {
			throw new \LogicException( '\Pimple\Container not set, call init() or setPimple() before using set().' );
		}

		if ( 'factory' == $type ) {
			static::$pimple[$key] = static::$pimple->factory( $val );
		}
		elseif ( 'protect' == $type ) {
			static::$pimple[$key] = static::$pimple->protect( $val );
		}
		else {
			static::$pimple[$key] = $val;
		}
	}


	/**
	 * @return \Pimple\Container
	 */
	static function getPimple() {
		if ( ! static::$pimple ) {
			throw new \LogicException( '\Pimple\Container not set, call init() or setPimple() before using getPimple().' );
		}

		return static::$pimple;
	}


	/**
	 * @param \Pimple\Container $pimple
	 */
	public static function setPimple( $pimple ) {
		static::$pimple = $pimple;
	}

}