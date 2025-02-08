<?php

namespace Pressbooks;

final class Pressbooks
{
    public function boot(): void
    {
        (new Support\Boot)->run();
		$this->setupHooks();
    }

    public function setupHooks()
    {
        dump(app('db')->table('users')->get());
    }

    public function setupAdminHooks()
    {
        if (is_admin()) {
            // TODO: Add admin hooks
        }
    }
}
