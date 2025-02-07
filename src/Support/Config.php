<?php

namespace Pressbooks\Support;

// TODO: WP-new-era Replace function calls with proper Notice and Session class methods and remove this file when done
use function Pressbooks\Utility\get_cache_path;

class Config
{
    public static function get(string $key, $default = null)
    {
        $config = [
            'sass' => [],
            'global_typography' => [],
            'styles' => [],
            'blade' => [
                'templates_path' => dirname(__DIR__) . '/templates', // TODO: double-check this path
                'cache_path' => get_cache_path(),
            ],
            'database' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', DB_HOST),
                'database' => env('DB_NAME', DB_NAME),
                'username' => env('DB_USER', DB_USER),
                'password' => env('DB_PASSWORD', DB_PASSWORD),
                'charset' => DB_CHARSET,
                'collation' => DB_COLLATE,
                'prefix' => DB_PREFIX,
            ],
        ];

        return $config[$key] ?? $default;
    }
}
