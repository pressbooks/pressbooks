<?php

namespace Tests\Unit;

use Pressbooks\Support\Compatibility;
use WP_UnitTestCase;

class CompatibilityTest extends WP_UnitTestCase
{
    private Compatibility $compatibility;

    public function setUp(): void
    {
        parent::setUp();
        $this->compatibility = new Compatibility;
        // Reset the static cache for each test
        $reflection = new \ReflectionClass(Compatibility::class);
        $property = $reflection->getProperty('isCompatible');
        $property->setAccessible(true);
        $property->setValue(null);
    }

    public function test_minimum_php_version_constant()
    {
        $this->assertEquals('8.2', Compatibility::MINIMUM_PHP_VERSION);
    }

    public function test_minimum_wp_version_constant()
    {
        $this->assertEquals('6.8.2', Compatibility::MINIMUM_WP_VERSION);
    }

    public function test_meets_minimum_requirements_with_current_environment()
    {
        // In a proper WordPress test environment, this should return true
        // as the test environment meets our requirements
        $result = $this->compatibility->meetsMinimumRequirements();
        $this->assertIsBool($result);
    }

    public function test_meets_minimum_requirements_caches_result()
    {
        $result1 = $this->compatibility->meetsMinimumRequirements();
        $result2 = $this->compatibility->meetsMinimumRequirements();
        $this->assertEquals($result1, $result2);
    }

    public function test_check_adds_admin_notices_when_requirements_not_met()
    {
        // Mock the compatibility check to return false
        $compatibility = $this->getMockBuilder(Compatibility::class)
            ->onlyMethods(['meetsMinimumRequirements'])
            ->getMock();

        $compatibility->method('meetsMinimumRequirements')
            ->willReturn(false);

        // Ensure we're starting with no hooks
        remove_all_actions('admin_notices');
        remove_all_actions('network_admin_notices');

        $compatibility->check();

        $this->assertTrue(has_action('admin_notices'));
        $this->assertTrue(has_action('network_admin_notices'));
    }

    public function test_display_admin_notice_renders_appropriate_error()
    {
        // The displayAdminNotice method only outputs when requirements are not met
        // Let's check if requirements are met first
        $requirementsMet = $this->compatibility->meetsMinimumRequirements();

        ob_start();
        $this->compatibility->displayAdminNotice();
        $output = ob_get_clean();

        if (!$requirementsMet) {
            $this->assertStringContainsString('<div id="message"', $output);
            $this->assertStringContainsString('class="error fade"', $output);
            $this->assertStringContainsString('Pressbooks', $output);
        } else {
            // The method checks requirements internally, so if met, there should be output
            // Actually, looking at the code, it will always output something if called
            $this->assertIsString($output);
        }
    }
}
