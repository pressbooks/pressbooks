<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/plugins/namespace.php' );

class Admin_PluginsTest extends \WP_UnitTestCase {

	public function test_filter_plugins() {
		$plugins = [
			'hello-dolly/hello.php' => [],
			'parsedown-party/parsedownparty.php' => [],
			'pressbooks-textbook/pressbooks-textbook.php' => [],
			'wordpress-seo/wordpress-seo.php' => [],
		];
		$filtered_plugins = \Pressbooks\Admin\Plugins\filter_plugins( $plugins );
		$this->assertArrayHasKey( 'pressbooks-textbook/pressbooks-textbook.php', $filtered_plugins );
		$this->assertArrayHasKey( 'parsedown-party/parsedownparty.php', $filtered_plugins );
		$this->assertArrayNotHasKey( 'hello-dolly/hello.php', $filtered_plugins );
		$this->assertArrayNotHasKey( 'wordpress-seo/wordpress-seo.php', $filtered_plugins );

	}

}
