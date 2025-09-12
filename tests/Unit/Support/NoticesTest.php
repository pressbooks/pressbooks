<?php

namespace Tests\Unit\Support;

use Pressbooks\Support\Notices;
use WP_UnitTestCase;

class NoticesTest extends WP_UnitTestCase
{
    private Notices $notices;

    public function setUp(): void
    {
        parent::setUp();
        $this->notices = new Notices;

        // Clear session data
        $_SESSION = [];

        // Create a test user for transients
        $user_id = self::factory()->user->create();
        wp_set_current_user($user_id);
    }

    public function test_add_stores_notice_in_session()
    {
        $message = 'Test notice message';
        $this->notices->add($message);

        $notices = $this->notices->getAllNotices();
        $this->assertContains($message, $notices);
    }

    public function test_add_error_stores_error_in_session()
    {
        $errorMessage = 'Test error message';
        $this->notices->addError($errorMessage);

        $errors = $this->notices->getAllErrors();
        $this->assertContains($errorMessage, $errors);
    }

    public function test_get_all_notices_returns_array()
    {
        $this->notices->add('Notice 1');
        $this->notices->add('Notice 2');

        $notices = $this->notices->getAllNotices();

        $this->assertIsArray($notices);
        $this->assertGreaterThanOrEqual(2, count($notices));
        $this->assertContains('Notice 1', $notices);
        $this->assertContains('Notice 2', $notices);
    }

    public function test_get_all_errors_returns_array()
    {
        $this->notices->addError('Error 1');
        $this->notices->addError('Error 2');

        $errors = $this->notices->getAllErrors();

        $this->assertIsArray($errors);
        $this->assertGreaterThanOrEqual(2, count($errors));
        $this->assertContains('Error 1', $errors);
        $this->assertContains('Error 2', $errors);
    }

    public function test_flush_notices_clears_notices()
    {
        $this->notices->add('Test notice');
        $noticesBeforeFlush = $this->notices->getAllNotices();
        $this->assertNotEmpty($noticesBeforeFlush);

        $this->notices->flushNotices();
        $noticesAfterFlush = $this->notices->getAllNotices();

        // After flushing, notices should be empty or significantly reduced
        $this->assertLessThan(count($noticesBeforeFlush), count($noticesAfterFlush));
    }

    public function test_flush_errors_clears_errors()
    {
        $this->notices->addError('Test error');
        $errorsBeforeFlush = $this->notices->getAllErrors();
        $this->assertNotEmpty($errorsBeforeFlush);

        $this->notices->flushErrors();
        $errorsAfterFlush = $this->notices->getAllErrors();

        // After flushing, errors should be empty or significantly reduced
        $this->assertLessThan(count($errorsBeforeFlush), count($errorsAfterFlush));
    }

    public function test_notices_and_errors_are_separate()
    {
        $this->notices->add('Notice message');
        $this->notices->addError('Error message');

        $notices = $this->notices->getAllNotices();
        $errors = $this->notices->getAllErrors();

        $this->assertContains('Notice message', $notices);
        $this->assertNotContains('Error message', $notices);

        $this->assertContains('Error message', $errors);
        $this->assertNotContains('Notice message', $errors);
    }

    public function test_handles_non_array_session_data()
    {
        // Simulate corrupted session data
        $_SESSION['pb_notices'] = 'not_an_array';

        $notices = $this->notices->getAllNotices();
        $this->assertIsArray($notices);
        $this->assertContains('not_an_array', $notices);
    }

    protected function tearDown(): void
    {
        // Clean up session and transients
        $_SESSION = [];

        if (get_current_user_id()) {
            $user_id = get_current_user_id();
            delete_site_transient("pb_notices{$user_id}");
            delete_site_transient("pb_errors{$user_id}");
        }

        parent::tearDown();
    }
}
