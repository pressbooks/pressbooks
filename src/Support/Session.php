<?php

namespace Pressbooks\Support;

class Session
{
    public function run()
    {
        add_action('plugins_loaded', [$this, 'start'], 1);
        add_action('wp_logout', [$this, 'kill']);
        add_action('wp_login', [$this, 'kill']);
    }

    public function start(): void
    {
        if (! session_id()) {
            if (! headers_sent()) {
                ini_set('session.use_only_cookies', true);
                ini_set('session.cookie_domain', COOKIE_DOMAIN);
                if (is_ssl()) {
                    ini_set('session.cookie_secure', true);
                }

                $options = [];
                if ($this->useNonBlockingSession()) {
                    // PHP Sessions are allowed but they are "READ ONLY" for ajax and webbook.
                    // It reads the session data and immediately releases the lock so other scripts won't block on it.
                    $options['read_and_close'] = true;
                }

                /**
                 * Adjust session configuration as needed.
                 *
                 * @since 5.5.0
                 *
                 * @param  array  $options
                 */
                $override_options = apply_filters('pb_session_configuration', $options);
                if (is_array($override_options)) {
                    $options = $override_options;
                }
                // @codingStandardsIgnoreStart
                $session_ok = @\session_start($options);
                if (! $session_ok) {
                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_regenerate_id(true);
                    } else {
                        $session_name = session_name();
                        unset($_COOKIE[$session_name], $_GET[$session_name]);
                    }
                    @\session_start($options);
                }
                // @codingStandardsIgnoreEnd
            } else {
                error_log('There was a problem with \Pressbooks\session_start(), headers already sent!'); // @codingStandardsIgnoreLine
            }
        }
    }

    private function useNonBlockingSession()
    {
        if (wp_doing_ajax()) {
            return true;
        }
        if (is_admin() === false && in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php', 'wp-signup.php'], true) === false) {
            return true;
        }

        return false;
    }

    public function kill()
    {
        $_SESSION = [];
        @session_destroy(); // @codingStandardsIgnoreLine
    }
}
