<?php

use Pressbooks\Admin\Dashboard\NetworkDashboard;

class Admin_NetworkDashboardTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @test
	 */
	public function it_checks_instance(): void {
		$this->assertInstanceOf( NetworkDashboard::class, NetworkDashboard::init() );
	}

	/**
	 * @test
	 */
	public function it_checks_hooks(): void {
		global $wp_filter;

		NetworkDashboard::init()->hooks();

		$this->assertArrayHasKey( 'load-index.php', $wp_filter );
		$this->assertArrayHasKey( 'admin_head', $wp_filter );
		$this->assertArrayHasKey( 'network_admin_menu', $wp_filter );
	}

	/**
	 * @test
	 */
	public function it_renders_home_page(): void {
		ob_start();
		NetworkDashboard::init()->renderDashboard();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Welcome to', $output );
		$this->assertStringContainsString( 'Update your home page', $output );
		$this->assertStringContainsString( 'Administer your network', $output );
		$this->assertStringContainsString( 'Support Resources', $output );
	}
}
