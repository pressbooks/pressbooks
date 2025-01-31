<?php

namespace Pressbooks\Support;

class Boot
{
    public function run(): void
    {
        if (! defined('PB_PLUGIN_DIR')) {
            define('PB_PLUGIN_DIR', (is_link(WP_PLUGIN_DIR.'/pressbooks') ? trailingslashit(WP_PLUGIN_DIR.'/pressbooks') : trailingslashit(__DIR__))); // Must have trailing slash!
        }

        if (! defined('PB_PLUGIN_URL')) {
            define('PB_PLUGIN_URL', trailingslashit(plugins_url('pressbooks'))); // Must have trailing slash!
        }
        $this->setupTheme();
        $this->setupLocale();
        $this->setupSession();
    }

    private function setupTheme(): void
    {
        add_action('setup_theme', [$this, 'setDefaultBooksTheme']);
    }

    private function setupLocale(): void
    {
        /**
         * Set locale to UTF8 so escapeshellcmd() doesn't strip valid characters
         *
         * @since 4.3.5
         * @see https://bugs.php.net/bug.php?id=54391
         *
         * @param  string  $pb_lc_ctype
         * @return string
         */
        $pb_lc_ctype = apply_filters('pb_lc_ctype', 'en_US.UTF-8');
        setlocale(LC_CTYPE, 'UTF8', $pb_lc_ctype);
        putenv("LC_CTYPE={$pb_lc_ctype}");

    }

    private function setupSession(): void
    {
        (new Session)->run();
    }

    private function setDefaultBooksTheme(): void
    {
        if (! defined('WP_DEFAULT_THEME')) {
            define('WP_DEFAULT_THEME', defined('PB_BOOK_THEME') ? PB_BOOK_THEME : get_site_option('pressbooks_default_book_theme', 'pressbooks-book'));
        }
    }
}
