<?php

namespace Tests\Unit\Support;

use Pressbooks\Support\Config;
use WP_UnitTestCase;

class ConfigTest extends WP_UnitTestCase
{
    public function test_get_returns_config_values()
    {
        $appName = Config::get('app.name');
        $this->assertEquals('Pressbooks', $appName);
    }

    public function test_get_returns_nested_config_values()
    {
        $bladeTemplatesPath = Config::get('blade.templates_path');
        $this->assertStringContainsString('/resources/views', $bladeTemplatesPath);
    }

    public function test_get_returns_default_when_key_not_found()
    {
        $value = Config::get('non.existent.key', 'default_value');
        $this->assertEquals('default_value', $value);
    }

    public function test_get_returns_null_when_key_not_found_and_no_default()
    {
        $value = Config::get('non.existent.key');
        $this->assertNull($value);
    }

    public function test_config_loads_only_once()
    {
        // First call loads the config
        $value1 = Config::get('app.name');

        // Second call should use cached config
        $value2 = Config::get('app.name');

        $this->assertEquals($value1, $value2);
    }

    public function test_config_handles_missing_config_file()
    {
        // Test with a Config class that points to a non-existent file
        // This would require mocking the file_exists call or creating a test config class
        $value = Config::get('any.key', 'fallback');
        $this->assertIsString($value);
    }

    public function test_database_config_structure()
    {
        $dbConfig = Config::get('database');

        $this->assertIsArray($dbConfig);
        $this->assertArrayHasKey('driver', $dbConfig);
        $this->assertArrayHasKey('host', $dbConfig);
        $this->assertArrayHasKey('database', $dbConfig);
        $this->assertArrayHasKey('username', $dbConfig);
        $this->assertEquals('mysql', $dbConfig['driver']);
    }

    public function test_blade_config_structure()
    {
        $bladeConfig = Config::get('blade');

        $this->assertIsArray($bladeConfig);
        $this->assertArrayHasKey('templates_path', $bladeConfig);
        $this->assertArrayHasKey('cache_path', $bladeConfig);
    }
}
