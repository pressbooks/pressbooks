<?php

namespace Pressbooks\Core;

use Pressbooks\Container;
use Pressbooks\Contracts\ServiceProviderInterface;

/**
 * Service Provider
 * Automatically boot all service providers in the Providers directory.
 */
class ServiceProvider
{
    public static function init(): void
    {
        $container = Container::getInstance();
        $providersPath = dirname(__DIR__) . '/Providers';

        foreach (scandir($providersPath) as $file) {
            if (str_ends_with($file, 'ServiceProvider.php')) {
                $class = "Pressbooks\\Providers\\" . pathinfo($file, PATHINFO_FILENAME);
                if (class_exists($class) && is_subclass_of($class, ServiceProviderInterface::class)) {
                    $class::boot($container);
                }
            }
        }
    }
}
