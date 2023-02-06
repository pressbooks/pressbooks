<?php

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Pressbooks\Admin\Dashboard\UserDashboard;

class Admin_UserDashboardTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @test
	 */
	public function it_renders_home_page(): void {
		ob_start();
		UserDashboard::init()->renderDashboard();
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
		UserDashboard::init()->renderDashboard();
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
