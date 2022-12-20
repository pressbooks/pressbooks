<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Pressbooks\Admin\Dashboard\NewUserDashboard;

class Admin_NewUserDashboardTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @test
	 */
	public function it_adds_expected_hooks(): void {
		$this->assertEmpty( $this->filterHookList( 'load-index.php', 'redirectToHomePage' ) );
		$this->assertEmpty( $this->filterHookList( 'admin_head', 'removeDefaultHomePage' ) );
		$this->assertEmpty( $this->filterHookList( 'admin_menu', 'addPressbooksHomePage' ) );

		NewUserDashboard::init();

		$this->assertNotEmpty( $this->filterHookList( 'load-index.php', 'redirectToHomePage' ) );
		$this->assertNotEmpty( $this->filterHookList( 'admin_head', 'removeDefaultHomePage' ) );
		$this->assertNotEmpty( $this->filterHookList( 'admin_menu', 'addPressbooksHomePage' ) );
	}

	/**
	 * @test
	 */
	public function it_renders_home_page(): void {
		ob_start();
		NewUserDashboard::init()->renderHomePage();
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
		NewUserDashboard::init()->renderHomePage();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'Welcome to', $output );
		$this->assertStringContainsString( 'Create a book', $output );
		$this->assertStringContainsString( 'Adapt a book', $output );
		$this->assertStringContainsString( 'Book Invitations', $output );
	}

	protected function filterHookList( string $filter, string $method ): Collection {
		global $wp_filter;

		$hook = $wp_filter[ $filter ] ?? null;

		if ( ! $hook ) {
			return collect();
		}

		$hooks = array_keys( $hook->callbacks[10] );

		return collect( $hooks )->filter(
			fn( string $hook ) => Str::contains( $hook, $method )
		);
	}
}
