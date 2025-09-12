<?php

namespace Pressbooks;

final class Pressbooks
{
    public function boot(): void
    {
        (new Support\Boot)->run();
        $this->setupCore();
        $this->setupHooks();
        $this->loadHookFiles();
    }

    private function setupCore(): void
    {
        /**
         * Memcached Object Cache v2.0.2 doesn't like when we loop on switch_to_blog()
         * We "fix" this by storing our cached items in global group 'pb'
         */
        wp_cache_add_global_groups(['pb']);

        $this->registerThemeDirectories();

        // Fire loaded actions
        do_action('pb_loaded');
    }

    private function setupHooks(): void
    {
        // Core hooks will be loaded from hook files
        // This method can be used for programmatic hooks if needed
    }

    private function setupAdminHooks(): void
    {
        if (is_admin()) {
            // Admin-specific hooks will be loaded from hooks-admin.php
        }
    }

    private function registerThemeDirectories(): void
    {
        /**
         * Register additional theme directories.
         */
        do_action('pressbooks_register_theme_directory');

        if (is_admin()) {
            // We need Book class for this, will be implemented after Book migration
            // For now, we'll load this in the hook files
        }
    }

    private function loadHookFiles(): void
    {
        // TODO: Load hook files once we have resolved all dependencies
        // For now, we'll load essential hooks manually to get PB working

        $this->loadEssentialHooks();
    }

    private function loadEssentialHooks(): void
    {
        // Load basic WordPress customizations
        add_action('login_head', function () {
            // Basic favicon for login
            echo '<link rel="icon" type="image/x-icon" href="' . plugins_url('pressbooks') . '/assets/dist/images/favicon.ico">';
        });

        // Languages
        add_action('init', function () {
            load_plugin_textdomain('pressbooks', false, dirname(plugin_basename(__FILE__)) . '/languages/');
        });

        // Basic admin customization
        if (is_admin()) {
            add_action('admin_head', function () {
                echo '<link rel="icon" type="image/x-icon" href="' . plugins_url('pressbooks') . '/assets/dist/images/favicon.ico">';
            });
        }
    }
}
