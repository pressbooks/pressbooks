<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/plugins/namespace.php' );

class Admin_PluginsTest extends \WP_UnitTestCase {

	/**
	 * @group plugins
	 */
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

	/**
	 * @group plugins
	 */
	public function test_hide_gutenberg() {
		$plugins = [
			'hello-dolly/hello.php' => [
				'Name' => 'Hello Dolly',
				'PluginURI' => 'http://wordpress.org/extend/plugins/hello-dolly/',
				'Version' => '1.6',
				'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.',
				'Author' => 'Matt Mullenweg',
				'AuthorURI' => 'http://ma.tt/',
				'Title' => 'Hello Dolly',
				'AuthorName' => 'Matt Mullenweg',
			],
			'gutenberg/gutenberg.php' => [
				'Name' => 'Gutenberg',
				'Version' => ' 3.9.0',
				'Description' => 'Printing since 1440. This is the development plugin for the new block editor in core',
				'Author' => 'Gutenberg Teame',
				'TextDomain' => 'gutenberg',
			],
		];
		$plugins = \Pressbooks\Admin\Plugins\hide_gutenberg( $plugins );
		$this->assertArrayNotHasKey( 'gutenberg/gutenberg.php', $plugins );
	}

	/**
	 * @group plugins
	 */
	public function test_disable_h5p_security() {
		$allcaps = [];
		$allcaps = \Pressbooks\Admin\Plugins\disable_h5p_security( $allcaps, [], [ 'do_nothing' ] );
		$this->assertEmpty( $allcaps );
		$allcaps = \Pressbooks\Admin\Plugins\disable_h5p_security( $allcaps, [], [ 'disable_h5p_security' ] );
		$this->assertFalse( $allcaps['disable_h5p_security'] );
	}

	/**
	 * @group plugins
	 */
	public function test_disable_h5p_security_superadmin() {
		$caps = [];
		$caps = \Pressbooks\Admin\Plugins\disable_h5p_security_superadmin( $caps, '' );
		$this->assertEmpty( $caps );
		$caps = \Pressbooks\Admin\Plugins\disable_h5p_security_superadmin( $caps, 'disable_h5p_security' );
		$this->assertContains( 'do_not_allow', $caps );
	}

	/**
	 * @group plugins
	 */
	public function test_quicklatex_svg_warning() {
		\Pressbooks\Admin\Plugins\quicklatex_svg_warning( 'do/nothing.php' );
		$notices = \Pressbooks\get_all_notices();
		$this->assertEmpty( $notices );

		\Pressbooks\Admin\Plugins\quicklatex_svg_warning( 'wp-quicklatex/wp-quicklatex.php' );
		$notices = \Pressbooks\get_all_notices();
		$this->assertContains( 'a format that may carry a higher security risk', $notices[0] );
	}

}
