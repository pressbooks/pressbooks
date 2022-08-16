<?php

namespace Pressbooks;

use Roots\Acorn\Application as LaravelContainer;

/**
 * Application Container for Pressbooks
 */
class Container {
	/**
	 * @throws \Psr\Container\ContainerExceptionInterface
	 * @throws \Psr\Container\NotFoundExceptionInterface
	 */
	public static function get( $key ) {
		echo 'getting ' . $key . ' from the container<br>';
		return LaravelContainer::getInstance()->get( $key );
	}

	public static function set( $key, $val, $type = null, $replace = false ): LaravelContainer {
		if ( $replace ) {
			LaravelContainer::getInstance()->forgetInstance( $key );
			LaravelContainer::getInstance()->offsetSet( $key, $val );
		}

		if ( ! LaravelContainer::getInstance()->bound( $key ) ) {
			if ( in_array( $type, [ 'factory', 'bind' ], true ) ) {
				LaravelContainer::getInstance()->bind( $key, $val );
			} elseif ( in_array( $type, [ 'protect', 'instance' ], true ) ) {
				LaravelContainer::getInstance()->instance( $key, $val );
			} else {
				LaravelContainer::getInstance()->singleton( $key, $val );
			}
		}

		return LaravelContainer::getInstance();
	}

	public static function getInstance(): LaravelContainer {
		return LaravelContainer::getInstance();
	}
}
