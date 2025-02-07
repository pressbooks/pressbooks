<?php

namespace Pressbooks\Core;

use Illuminate\Container\Container as LaravelContainer;

/**
 * Application Container for Pressbooks
 * @method static mixed get(string $key)
 */
class Container extends LaravelContainer
{
    public static function set(string $key, $val, ?string $type = null, bool $replace = false): LaravelContainer
    {
        $container = static::getInstance();

        if ($replace) {
            $container->forgetInstance($key);
            $container->offsetSet($key, $val);
        }

        match ($type) {
            'factory', 'bind' => $container->bind($key, $val),
            'protect', 'instance' => $container->instance($key, $val),
            default => $container->singleton($key, $val),
        };

        return $container;
    }
}
