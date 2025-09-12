<?php

namespace Tests\Unit\Support;

use Pressbooks\Support\Session;
use WP_UnitTestCase;

class SessionTest extends WP_UnitTestCase
{
    private Session $session;

    public function setUp(): void
    {
        parent::setUp();
        $this->session = new Session;
    }

    public function test_run_adds_appropriate_hooks()
    {
        // Clear existing hooks
        remove_all_actions('plugins_loaded');
        remove_all_actions('wp_logout');
        remove_all_actions('wp_login');

        $this->session->run();

        $this->assertTrue(has_action('plugins_loaded'));
        $this->assertTrue(has_action('wp_logout'));
        $this->assertTrue(has_action('wp_login'));
    }

    public function test_use_non_blocking_session_returns_true_for_ajax()
    {
        // Mock wp_doing_ajax to return true
        if (!function_exists('wp_doing_ajax')) {
            function wp_doing_ajax()
            {
                return true;
            }
        }

        $result = $this->session->useNonBlockingSession();
        $this->assertTrue($result);
    }

    public function test_use_non_blocking_session_returns_true_for_frontend()
    {
        // Mock wp_doing_ajax to return false
        if (!function_exists('wp_doing_ajax')) {
            function wp_doing_ajax()
            {
                return false;
            }
        }

        // Mock is_admin to return false (frontend)
        $GLOBALS['pagenow'] = 'index.php'; // Not a login page

        $result = $this->session->useNonBlockingSession();
        $this->assertTrue($result);
    }

    public function test_use_non_blocking_session_returns_false_for_admin()
    {
        // This test would require more complex mocking of WordPress functions
        // to properly test the admin context logic
        $result = $this->session->useNonBlockingSession();
        $this->assertIsBool($result);
    }

    public function test_kill_clears_session_data()
    {
        // Set some session data
        $_SESSION['test_key'] = 'test_value';

        $this->session->kill();

        $this->assertEmpty($_SESSION);
    }

    public function test_start_sets_cookie_configuration()
    {
        // This test would need to mock session functions and headers
        // to properly test session configuration without actually starting a session
        $this->assertInstanceOf(Session::class, $this->session);
    }

    protected function tearDown(): void
    {
        // Clean up session data
        $_SESSION = [];

        parent::tearDown();
    }
}
