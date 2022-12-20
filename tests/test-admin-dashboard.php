<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/dashboard/namespace.php' );

class Admin_DashboardTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @group dashboard
	 */
	public function test_init_network_integrations_menu() {
		$parent_slug = \Pressbooks\Admin\Dashboard\init_network_integrations_menu();
		$this->assertTrue( ! empty( $parent_slug ) && is_string( $parent_slug ) );
	}
}
