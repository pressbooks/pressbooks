<?php

namespace Pressbooks;

use Illuminate\Container\Container as LaravelContainer;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class Container
{
    private static ?LaravelContainer $instance = null;

    public static function getInstance(): LaravelContainer
    {
        return self::$instance ??= LaravelContainer::getInstance();
    }

    /**
     * Proxy for resolving dependencies.
     *
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public static function get(string $key)
    {
        return self::getInstance()->get($key);
    }

    /**
     * Proxy for binding services.
     */
    public static function set(string $key, $val, ?string $type = null, bool $replace = false): LaravelContainer
    {
        $container = self::getInstance();

        if ($replace) {
            $container->forgetInstance($key);
            $container->offsetSet($key, $val);
        }

        switch ($type) {
            case 'factory':
            case 'bind':
                $container->bind($key, $val);
                break;
            case 'protect':
            case 'instance':
                $container->instance($key, $val);
                break;
            default:
                $container->singleton($key, $val);
                break;
        }

        return $container;
    }
}
