<?php

namespace Pressbooks\Support;

class Activation
{
    public function run(): void
    {
        // Apply Pressbooks color scheme
        update_user_option(get_current_user_id(), 'admin_color', 'pb_colors', true);

        // Prevent overwriting customizations if Pressbooks has been disabled
        if (! get_site_option('pressbooks-activated')) {

            /**
             * Allow the default description of the root blog to be customized.
             *
             * @since 3.9.7
             *
             * @param  string  $value  Default description ('Simple Book Publishing').
             */
            update_blog_option(1, 'blogdescription', apply_filters('pb_root_description', __('Simple Book Publishing', 'pressbooks')));

            $theme = defined('PB_ROOT_THEME') ? PB_ROOT_THEME : 'pressbooks-aldine';

            if (wp_get_theme($theme)->exists()) {
                $activate = $theme;
            }

            if (! empty($activate)) {
                switch_to_blog(1);
                // Configure root blog theme (PB_ROOT_THEME, usually 'pressbooks-aldine').
                switch_theme($activate);
                // Remove widgets from root blog.
                delete_option('sidebars_widgets');
                restore_current_blog();
            }

            // Add "activated" key to enable check above
            add_site_option('pressbooks-activated', true);

        }
    }
}
