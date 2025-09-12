<?php

namespace Tests\Unit;

use Pressbooks\Pressbooks;
use WP_UnitTestCase;

class PressbooksTest extends WP_UnitTestCase
{
    public function test_pressbooks_initializes_correctly()
    {
        $pressbooks = new Pressbooks;
        
        $this->assertInstanceOf(Pressbooks::class, $pressbooks);
    }

    public function test_pressbooks_boot_sets_up_environment()
    {
        $pressbooks = new Pressbooks;
        $pressbooks->boot();
        
        // Check that constants are defined
        $this->assertTrue(defined('PB_PLUGIN_DIR'));
        $this->assertTrue(defined('PB_PLUGIN_URL'));
        
        // Check that plugin dir points to correct location
        $this->assertStringContainsString('pressbooks', PB_PLUGIN_DIR);
        $this->assertStringEndsWith('/', PB_PLUGIN_DIR);
    }

    public function test_pressbooks_loads_essential_hooks()
    {
        $pressbooks = new Pressbooks;
        $pressbooks->boot();
        
        // Check that essential hooks are registered
        $this->assertTrue(has_action('login_head'));
        $this->assertTrue(has_action('init'));
        
        if (is_admin()) {
            $this->assertTrue(has_action('admin_head'));
        }
    }

    public function test_pressbooks_fires_loaded_actions()
    {
        $pb_loaded_fired = false;
        $pressbooks_loaded_fired = false;
        
        // Add temporary hooks to capture the actions
        add_action('pb_loaded', function () use (&$pb_loaded_fired) {
            $pb_loaded_fired = true;
        });
        
        add_action('pressbooks_loaded', function () use (&$pressbooks_loaded_fired) {
            $pressbooks_loaded_fired = true;
        });
        
        $pressbooks = new Pressbooks;
        $pressbooks->boot();
        
        $this->assertTrue($pb_loaded_fired, 'pb_loaded action should fire');
        $this->assertTrue($pressbooks_loaded_fired, 'pressbooks_loaded action should fire');
    }

    public function test_service_container_is_available()
    {
        // Test that our app() helper function works
        $container = app();
        $this->assertNotNull($container);
        
        // Test that we can bind and resolve services
        app()->singleton('test.service', fn () => 'test_value');
        $this->assertEquals('test_value', app('test.service'));
    }
}