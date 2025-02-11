<?php

namespace Pressbooks\Support;

class Config
{
    private static array $config = [];

    /**
     * Load the configuration file into memory once.
     */
    private static function load(): void
    {
        if (empty(self::$config)) {
            $filePath = dirname(__DIR__, 2) . '/config/app.php';
            if (file_exists($filePath)) {
                self::$config = require $filePath;
            } else {
                self::$config = []; // Default to empty array if config file is missing
            }
        }
    }

    /**
     * Get a config value using dot notation.
     */
    public static function get(string $key, $default = null)
    {
        self::load();
        return self::getNestedValue(self::$config, explode('.', $key), $default);
    }

    /**
     * Recursively search for nested config values using dot notation.
     */
    private static function getNestedValue(array $config, array $keys, $default)
    {
        foreach ($keys as $key) {
            if (!isset($config[$key])) {
                return $default;
            }
            $config = $config[$key];
        }
        return $config;
    }
}
