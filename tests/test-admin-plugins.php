<?php

require_once( PB_PLUGIN_DIR . 'includes/admin/pb-plugins.php' );

class Admin_PluginsTest extends \WP_UnitTestCase {

	/**
	 * @covers \Pressbooks\Admin\Plugins\filter_plugins
	 */
	public function test_filter_plugins() {
		$plugins = array(
			'hello-dolly/hello.php' => array(
        'Name' => 'Hello Dolly',
        'PluginURI' => 'http://wordpress.org/extend/plugins/hello-dolly/',
        'Version' => '1.6',
        'Description' => 'This is not just a plugin, it symbolizes the hope and enthusiasm of an entire generation summed up in two words sung most famously by Louis Armstrong: Hello, Dolly. When activated you will randomly see a lyric from <cite>Hello, Dolly</cite> in the upper right of your admin screen on every page.',
        'Author' => 'Matt Mullenweg',
        'AuthorURI' => 'http://ma.tt/',
        'Title' => 'Hello Dolly',
        'AuthorName' => 'Matt Mullenweg'
			),
			'pressbooks-textbook/pressbooks-textbook.php' => array(
				'Name' => 'Pressbooks Textbook',
				'Version' => '2.1.2',
				'Description' => 'A plugin that extends Pressbooks for textbook authoring',
				'Author' => 'Brad Payne',
				'AuthorURI' => 'http://bradpayne.ca',
				'TextDomain' => 'pressbooks-textbook',
				'DomainPath' => '/languages',
				'Title' => 'Pressbooks Textbook',
				'AuthorName' => 'Brad Payne'
			)
		);
		$filtered_plugins = \Pressbooks\Admin\Plugins\filter_plugins( $plugins );
		$this->assertArrayHasKey('pressbooks-textbook/pressbooks-textbook.php', $filtered_plugins);
	}

	/**
	 * @covers \Pressbooks\Admin\Plugins\filter_install_plugins_tabs
	 */
	public function test_filter_install_plugins_tabs() {
		$tabs = array();
		$tabs = \Pressbooks\Admin\Plugins\filter_install_plugins_tabs( $tabs );
		$this->assertArrayHasKey('pressbooks', $tabs);
	}

	/**
	 * @covers \Pressbooks\Admin\Plugins\install_plugins_table_api_args_pressbooks
	 */
	public function test_install_plugins_table_api_args_pressbooks() {
		$args = array();
		$args = \Pressbooks\Admin\Plugins\install_plugins_table_api_args_pressbooks( $args );
		$this->assertEquals('pressbooks', $args['tag']);
	}

}
