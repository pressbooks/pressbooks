<?php

namespace Pressbooks\Contracts;

use Illuminate\Container\Container;

interface ServiceProviderInterface
{
    /**
     * Register services in the container.
     */
    public static function register(Container $container): void;

    /**
     * Boot the service when needed.
     */
    public static function boot(Container $container): void;
}
