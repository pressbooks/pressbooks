<?php

namespace Pressbooks\Support;

class Compatibility
{
    private static ?bool $isCompatible = null;

    public const MINIMUM_PHP_VERSION = '8.1.0';

    public const MINIMUM_WP_VERSION = '6.6.1';

    public function check(): void
    {
        if (! $this->meetsMinimumRequirements()) {
            add_action('admin_notices', [$this, 'displayAdminNotice']);
            add_action('network_admin_notices', [$this, 'displayAdminNotice']);
        }
    }

    public function meetsMinimumRequirements(): bool
    {
        if (self::$isCompatible !== null) {
            return self::$isCompatible;
        }

        include_once ABSPATH.'wp-admin/includes/plugin.php';
        self::$isCompatible = true;

        if (! version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=')) {
            self::$isCompatible = false;
        }

        $wpVersion = get_bloginfo('version');
        if (substr_count($wpVersion, '.') === 1) {
            $wpVersion .= '.0';
        }

        if (! is_multisite() || ! version_compare($wpVersion, self::MINIMUM_WP_VERSION, '>=')) {
            self::$isCompatible = false;
        }

        if (! defined('WP_TESTS_MULTISITE') && ! is_plugin_active('pressbooks/pressbooks.php')) {
            self::$isCompatible = false;
        }

        return self::$isCompatible;
    }

    public function displayAdminNotice(): void
    {
        if (! version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '>=')) {
            $this->printAdminError(sprintf(
                __('Pressbooks requires PHP version %s or greater. Please upgrade PHP.', 'pressbooks'),
                esc_html(self::MINIMUM_PHP_VERSION)
            ));
        } elseif (! version_compare(get_bloginfo('version'), self::MINIMUM_WP_VERSION, '>=')) {
            $this->printAdminError(sprintf(
                __('Pressbooks requires WordPress Multisite version %s or greater. Please upgrade WordPress.', 'pressbooks'),
                esc_html(self::MINIMUM_WP_VERSION)
            ));
        } elseif (! is_plugin_active('pressbooks/pressbooks.php')) {
            $this->printAdminError(__('Pressbooks is inactive. Please activate the plugin.', 'pressbooks'));
        }
    }

    private function printAdminError(string $message): void
    {
        echo '<div id="message" role="alert" class="error fade"><p>'.esc_html($message).'</p></div>';
    }
}
