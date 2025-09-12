<?php

namespace Pressbooks\Support;

// Note: use_non_blocking_session is in global namespace

class Notices
{
    protected string $key = 'pb_notices';

    protected string $errors = 'pb_errors';

    public function add(string $msg): void
    {
        $this->append($msg);
    }

    public function addError(string $msg): void
    {
        $this->append($msg, $this->errors);
    }

    public function getAll(string $key): array
    {
        $messages = $_SESSION[$key] ?? [];
        $messages = is_array($messages) ? $messages : [$messages];
        $transient = get_site_transient($key.get_current_user_id());
        $transient = is_array($transient) ? $transient : [$transient];

        return array_merge($messages, $transient);
    }

    public function getAllNotices(): array
    {
        return $this->getAll($this->key);
    }

    public function getAllErrors(): array
    {
        return $this->getAll($this->errors);
    }

    public function flushNotices(): void
    {
        $this->flush();
    }

    public function flushErrors(): void
    {
        $this->flush($this->errors);
    }

    private function append($msg, $key = null): void
    {
        $key = $key ?? $this->key;
        // TODO: WPNewEra This would be available as a Service Session in the container
        /**
         * Example
         * $use_non_blocking_session = app('session')->useNonBlockingSession();
         */
        $use_non_blocking_session = \use_non_blocking_session();
        $current_user_id = get_current_user_id();
        $messages = $use_non_blocking_session
            ? get_site_transient("{$key}{$current_user_id}")
            : ($_SESSION[$key] ?? []);

        $messages = is_array($messages) ? $messages : [$messages];
        $messages[] = $msg;

        if ($use_non_blocking_session) {
            set_site_transient("{$key}{$current_user_id}", $messages, 15 * MINUTE_IN_SECONDS);
        } else {
            $_SESSION[$key] = $messages;
        }
    }

    private function flush($key = null): void
    {
        $key = $key ?? $this->key;
        unset($_SESSION[$key]);
        delete_site_transient($key.get_current_user_id());
    }
}
