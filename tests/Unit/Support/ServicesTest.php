<?php

namespace Tests\Unit\Support;

use Pressbooks\Support\Services;
use Pressbooks\Container;
use WP_UnitTestCase;

class ServicesTest extends WP_UnitTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        // Note: Container uses singleton pattern, so we can't easily reset it
        // Tests should use unique service names to avoid conflicts
    }

    public function test_get_retrieves_service_from_container()
    {
        // Bind a test service with unique name
        Services::bind('test.service.get', function () {
            return 'test_value';
        });

        $result = Services::get('test.service.get');
        $this->assertEquals('test_value', $result);
    }

    public function test_get_with_parameters()
    {
        Services::bind('test.parameterized.unique', function ($app, $parameters) {
            return $parameters['message'] ?? 'default';
        });

        $result = Services::get('test.parameterized.unique', ['message' => 'custom_message']);
        $this->assertEquals('custom_message', $result);
    }

    public function test_has_returns_true_for_bound_service()
    {
        Services::bind('test.bound.unique', 'value');

        $this->assertTrue(Services::has('test.bound.unique'));
        $this->assertFalse(Services::has('test.unbound.unique'));
    }

    public function test_get_bindings_returns_array()
    {
        Services::bind('test1.unique', 'value1');
        Services::bind('test2.unique', 'value2');

        $bindings = Services::getBindings();

        $this->assertIsArray($bindings);
        $this->assertArrayHasKey('test1.unique', $bindings);
        $this->assertArrayHasKey('test2.unique', $bindings);
    }

    public function test_bind_registers_service()
    {
        Services::bind('test.bind.unique', function () {
            return 'bound_value';
        });

        $this->assertTrue(Services::has('test.bind.unique'));
        $this->assertEquals('bound_value', Services::get('test.bind.unique'));
    }

    public function test_bind_with_shared_false_creates_new_instances()
    {
        Services::bind('test.instance.unique', function () {
            return new \stdClass;
        }, false);

        $instance1 = Services::get('test.instance.unique');
        $instance2 = Services::get('test.instance.unique');

        $this->assertNotSame($instance1, $instance2);
    }

    public function test_singleton_creates_shared_instance()
    {
        Services::singleton('test.singleton.unique', function () {
            return new \stdClass;
        });

        $instance1 = Services::get('test.singleton.unique');
        $instance2 = Services::get('test.singleton.unique');

        $this->assertSame($instance1, $instance2);
    }

    public function test_singleton_with_string_concrete()
    {
        Services::singleton('test.string.unique', \stdClass::class);

        $instance1 = Services::get('test.string.unique');
        $instance2 = Services::get('test.string.unique');

        $this->assertInstanceOf(\stdClass::class, $instance1);
        $this->assertSame($instance1, $instance2);
    }

    protected function tearDown(): void
    {
        // Container uses singleton pattern, can't easily reset
        // Tests should clean up after themselves if needed

        parent::tearDown();
    }
}
