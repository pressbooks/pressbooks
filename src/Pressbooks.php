<?php

namespace Pressbooks;

final class Pressbooks
{
    public function __construct()
    {
        (new Support\Compatibility)->run();
    }

    public function boot(): void
    {
        (new Support\Boot)->run();
    }

    public function setupHooks()
    {
        // TODO: Add hooks
    }

    public function setupAdminHooks()
    {
        if (is_admin()) {
            // TODO: Add admin hooks
        }
    }
}
