<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/dashboard/namespace.php' );

class Admin_DashboardTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function test_get_rss_defaults() {
		$result = \Pressbooks\Admin\Dashboard\get_rss_defaults();
		$this->assertArrayHasKey( 'display_feed', $result );
		$this->assertArrayHasKey( 'url', $result );
		$this->assertArrayHasKey( 'title', $result );
	}

	public function test_replace_network_dashboard_widgets() {
		global $wp_meta_boxes;
		\Pressbooks\Admin\Dashboard\replace_network_dashboard_widgets();
		$this->assertArrayHasKey( 'dashboard-network', $wp_meta_boxes );
		$this->assertTrue( isset( $wp_meta_boxes['dashboard-network']['side']['low']['pb_dashboard_widget_blog'] ) );
	}


	public function test_replace_root_dashboard_widgets() {
		global $wp_meta_boxes;
		\Pressbooks\Admin\Dashboard\replace_root_dashboard_widgets();
		$this->assertArrayHasKey( 'dashboard', $wp_meta_boxes );
		$this->assertTrue( isset( $wp_meta_boxes['dashboard']['side']['low']['pb_dashboard_widget_blog'] ) );
	}

	public function test_replace_dashboard_widgets() {
		global $wp_meta_boxes;
		\Pressbooks\Admin\Dashboard\replace_dashboard_widgets();
		$this->assertArrayHasKey( 'dashboard', $wp_meta_boxes );
		$this->assertTrue( isset( $wp_meta_boxes['dashboard']['normal']['high']['pb_dashboard_widget_book'] ) );
		$this->assertTrue( isset( $wp_meta_boxes['dashboard']['side']['high']['pb_dashboard_widget_users'] ) );
		$this->assertTrue( isset( $wp_meta_boxes['dashboard']['side']['low']['pb_dashboard_widget_blog'] ) );
	}

	public function test_display_book_widget() {
		$this->_book();
		ob_start();
		\Pressbooks\Admin\Dashboard\display_book_widget();
		$buffer = ob_get_clean();
		$this->assertContains( "<ul class='front-matter'>", $buffer );
		$this->assertContains( "<ul class='chapters'>", $buffer );
		$this->assertContains( "<ul class='back-matter'>", $buffer );
	}

	public function test_display_pressbooks_blog() {
		// No cache
		delete_site_transient( 'pb_rss_widget' );
		ob_start();
		\Pressbooks\Admin\Dashboard\display_pressbooks_blog();
		$buffer = ob_get_clean();
		if ( empty( $buffer ) ) {
			$this->markTestIncomplete( 'Unable to fetch Pressbooks RSS' );
			return;
		}
		$this->assertContains( "class='rsswidget'", $buffer );

		// Cache
		ob_start();
		\Pressbooks\Admin\Dashboard\display_pressbooks_blog();
		$buffer = ob_get_clean();
		$this->assertContains( "class='rsswidget'", $buffer );
	}

	public function test_display_users_widget() {
		ob_start();
		\Pressbooks\Admin\Dashboard\display_users_widget();
		$buffer = ob_get_clean();
		$this->assertContains( '</table>', $buffer );
	}

	public function test_dashboard_options_init() {
		global $wp_settings_sections;
		\Pressbooks\Admin\Dashboard\dashboard_options_init();
		$this->assertArrayHasKey( 'pb_dashboard', $wp_settings_sections );
	}

}
