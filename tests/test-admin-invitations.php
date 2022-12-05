<?php

use Pressbooks\Admin\Dashboard\Invitations;

class Admin_InvitationsTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @test
	 */
	public function it_retrieves_pending_invitations(): void {
		$this->_book();

		$role = [ 'name' => 'author'];
		$key = wp_generate_password( 20, false );
		$user = get_userdata( $this->factory()->user->create() );

		wp_set_current_user( $user->ID );

		$meta_key = 'new_user_' . $key;

		$this->assertEmpty( Invitations::getPendingInvitations() );

		add_option( $meta_key, [
			'user_id' => $user->ID,
			'email' => $user->user_email,
			'role' => $role['name'],
		] );

		do_action( 'invite_user', $user->ID, $role, $key );

		$invitations = Invitations::getPendingInvitations();

		$this->assertNotEmpty( $invitations );

		$invitation = $invitations->first();

		$this->assertArrayHasKey( 'accept_link', $invitation );
		$this->assertArrayHasKey( 'role', $invitation );
		$this->assertArrayHasKey( 'book_url', $invitation );

		$this->assertEquals( 'an author', $invitation['role'] );
		$this->assertStringContainsString( home_url( "/newbloguser/{$key}" ), $invitation['accept_link'] );
		$this->assertStringContainsString( home_url(), $invitation['book_url'] );
	}
}
