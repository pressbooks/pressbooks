<?php

use Pressbooks\Admin\Dashboard\UserDashboard;

class Admin_UserDashboardTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @test
	 */
	public function it_checks_instance(): void {
		$this->assertInstanceOf( UserDashboard::class, UserDashboard::init() );
	}

	/**
	 * @test
	 */
	public function it_checks_hooks(): void {
		global $wp_filter;

		UserDashboard::init()->hooks();

		$this->assertArrayHasKey( 'load-index.php', $wp_filter );
		$this->assertArrayHasKey( 'admin_menu', $wp_filter );
	}

	/**
	 * @test
	 */
	public function it_renders_home_page(): void {
		ob_start();
		UserDashboard::init()->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Welcome to', $output );
		$this->assertStringContainsString( 'Create a book', $output );
		$this->assertStringContainsString( 'Adapt a book', $output );
		$this->assertStringNotContainsString( 'Book Invitations', $output );
	}

	/**
	 * @test
	 */
	public function it_renders_invitations_widget(): void {
		$this->_book();

		$role = [ 'name' => 'author'];
		$key = wp_generate_password( 20, false );
		$user = get_userdata( $this->factory()->user->create() );

		wp_set_current_user( $user->ID );

		$meta_key = 'new_user_' . $key;

		add_option( $meta_key, [
			'user_id' => $user->ID,
			'email' => $user->user_email,
			'role' => $role['name'],
		] );

		do_action( 'invite_user', $user->ID, $role, $key );

		ob_start();
		UserDashboard::init()->render();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Welcome to', $output );
		$this->assertStringContainsString( 'Create a book', $output );
		$this->assertStringContainsString( 'Adapt a book', $output );
		$this->assertStringContainsString( 'Book Invitations', $output );
	}

	/**
	 * @test
	 */
	public function it_redirects_to_the_expected_page(): void {
		$dashboard = $this->getMockBuilder( UserDashboard::class )
			->onlyMethods( [ 'doRedirect' ] )
			->getMock();

		$dashboard
			->expects( $this->exactly( 2 ) )
			->method( 'doRedirect' )
			->willReturn( true );

		set_current_screen( 'dashboard' );

		$this->assertSame( admin_url( 'index.php?page=pb_home_page' ), $dashboard->getUrl() );
		$this->assertTrue( $dashboard->redirect() );

		set_current_screen( 'dashboard-user' );

		$this->assertSame( admin_url( 'index.php?page=pb_home_page' ), $dashboard->getUrl() );
		$this->assertTrue( $dashboard->redirect() );
	}

	/**
	 * @test
	 */
	public function it_does_not_redirect_when_not_the_right_screen(): void {
		$dashboard = $this->getMockBuilder( UserDashboard::class )
			->onlyMethods( [ 'doRedirect' ] )
			->getMock();

		$dashboard
			->expects( $this->never() )
			->method( 'doRedirect' )
			->willReturn( true );

		set_current_screen( 'dashboard-network' );

		$this->assertFalse(
			UserDashboard::init()->redirect()
		);
	}
}
