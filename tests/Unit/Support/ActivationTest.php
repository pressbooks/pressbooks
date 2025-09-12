<?php

namespace Tests\Unit\Support;

use Pressbooks\Support\Activation;
use WP_UnitTestCase;

class ActivationTest extends WP_UnitTestCase
{
    private Activation $activation;

    public function setUp(): void
    {
        parent::setUp();
        $this->activation = new Activation;
    }

    public function test_run_sets_admin_color_scheme()
    {
        $user_id = self::factory()->user->create();
        wp_set_current_user($user_id);

        $this->activation->run();

        $admin_color = get_user_option('admin_color', $user_id);
        $this->assertEquals('pb_colors', $admin_color);
    }

    public function test_run_sets_blog_description_on_first_activation()
    {
        // Remove the activated flag to simulate first activation
        delete_site_option('pressbooks-activated');

        $this->activation->run();

        $description = get_blog_option(1, 'blogdescription');
        $this->assertEquals('Simple Book Publishing', $description);

        // Check that activated flag is set
        $this->assertTrue(get_site_option('pressbooks-activated'));
    }

    public function test_run_does_not_overwrite_customizations_if_already_activated()
    {
        // Set the activated flag
        add_site_option('pressbooks-activated', true);

        // Set a custom description
        update_blog_option(1, 'blogdescription', 'Custom Description');

        $this->activation->run();

        // Description should remain unchanged
        $description = get_blog_option(1, 'blogdescription');
        $this->assertEquals('Custom Description', $description);
    }

    public function test_run_respects_pb_root_description_filter()
    {
        delete_site_option('pressbooks-activated');

        add_filter('pb_root_description', function () {
            return 'Filtered Description';
        });

        $this->activation->run();

        $description = get_blog_option(1, 'blogdescription');
        $this->assertEquals('Filtered Description', $description);
    }

    public function tearDown(): void
    {
        // Clean up filters
        remove_all_filters('pb_root_description');
        parent::tearDown();
    }
}
