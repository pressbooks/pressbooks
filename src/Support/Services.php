<?php

namespace Pressbooks\Support;

use Pressbooks\Container;

/**
 * Service registry and utilities for accessing container services.
 */
class Services
{
    /**
     * Get a service from the container.
     */
    public static function get(string $abstract, array $parameters = []): mixed
    {
        return Container::getInstance()->make($abstract, $parameters);
    }

    /**
     * Check if a service is bound in the container.
     */
    public static function has(string $abstract): bool
    {
        return Container::getInstance()->bound($abstract);
    }

    /**
     * Get all bound services.
     */
    public static function getBindings(): array
    {
        return Container::getInstance()->getBindings();
    }

    /**
     * Register a service in the container.
     */
    public static function bind(string $abstract, $concrete = null, bool $shared = false): void
    {
        Container::getInstance()->bind($abstract, $concrete, $shared);
    }

    /**
     * Register a shared service in the container.
     */
    public static function singleton(string $abstract, $concrete = null): void
    {
        Container::getInstance()->singleton($abstract, $concrete);
    }
}
