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

	/**
	 * @test
	 */
	public function it_redirects_to_the_expected_page(): void {
		set_current_screen( 'dashboard-network' );

		$dashboard = NetworkDashboard::init();

		$this->assertSame( network_admin_url( 'index.php?page=pb_network_page' ), $dashboard->getRedirectUrl() );
		$this->assertTrue(
			$dashboard->redirectToDashboard()
		);
	}

	/**
	 * @test
	 */
	public function it_does_not_redirect_when_not_the_right_screen(): void {
		set_current_screen( 'dashboard' );

		$this->assertFalse(
			NetworkDashboard::init()->redirectToDashboard()
		);

		set_current_screen( 'dashboard-user' );

		$this->assertFalse(
			NetworkDashboard::init()->redirectToDashboard()
		);
	}
}
