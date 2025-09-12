<?php

namespace Pressbooks\Providers;

use Illuminate\Container\Container;
use Pressbooks\Contracts\ServiceProviderInterface;
use Pressbooks\Support\Session;

class SessionServiceProvider implements ServiceProviderInterface
{
    /**
     * Register the Session service but do not boot it yet.
     */
    public static function register(Container $container): void
    {
        $container->singleton('session', fn () => new Session);
    }

    /**
     * Boot Session service.
     */
    public static function boot(Container $container): void
    {
        if ($container->bound('session')) {
            return;
        }

        self::register($container);
    }
}
