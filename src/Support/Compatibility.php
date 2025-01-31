<?php

namespace Pressbooks\Support;

class Compatibility
{
    public function run()
    {
        $this->checkRequirements();
    }

    public function checkRequirements()
    {

        // TODO: Check requirements
        if (true) {
            add_action(
                'admin_notices', function () {
                    echo '<div id="message" role="alert" class="error fade"><p>'.esc_html__('Cannot find Pressbooks install.', 'pressbooks').'</p></div>';
                }
            );
        }

    }
}
