<?php

class Admin_DeleteBookTest extends \WP_UnitTestCase {
	/**
	 * @group deletebook
	 */
	public function test_init() {
		$class = \Pressbooks\Admin\Delete\Book::init();
		$this->assertInstanceOf( '\Pressbooks\Admin\Delete\Book', $class );
	}

	/**
	 * @group deletebook
	 */
	public function test_deleteBookEmailContent() {
		$delete_book = new \Pressbooks\Admin\Delete\Book();
		$content = $delete_book->deleteBookEmailContent( 'WordPress' );
		$this->assertStringNotContainsString( 'WordPress,', $content );
		$this->assertStringContainsString( 'Pressbooks', $content );
	}

	/**
	 * @group deletebook
	 */
	public function test_addMenu() {
		$delete_book = new \Pressbooks\Admin\Delete\Book();
		require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
		$admin_bar = new \WP_Admin_Bar();
		$delete_book->addMenu( $admin_bar );
		$node = $admin_bar->get_node( 'delete-book' );
		$this->assertTrue( is_object( $node ) );
		$this->assertEquals( $node->id, 'delete-book' );
		$this->assertEquals( $node->parent, 'site-name' );
	}

	/**
	 * @group deletebook
	 */
	public function test_addCurrentUserToDeleteEmail_ignores_non_delete_emails() {
		$delete_book = new \Pressbooks\Admin\Delete\Book();
		$args = [
			'to' => 'admin@example.com',
			'subject' => 'Some Other Email',
			'message' => 'Hello',
			'headers' => '',
			'attachments' => [],
		];
		$result = $delete_book->addCurrentUserToDeleteEmail( $args );
		$this->assertEquals( 'admin@example.com', $result['to'] );
	}

	/**
	 * @group deletebook
	 */
	public function test_addCurrentUserToDeleteEmail_adds_current_user() {
		$delete_book = new \Pressbooks\Admin\Delete\Book();

		$user_id = $this->factory()->user->create( [
			'user_email' => 'requester@example.com',
			'role' => 'administrator',
		] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		update_option( 'admin_email', 'bookadmin@example.com' );
		$subject = sprintf( '[%s] Delete My Site', wp_specialchars_decode( get_option( 'blogname' ) ) );

		$args = [
			'to' => 'bookadmin@example.com',
			'subject' => $subject,
			'message' => 'Delete confirmation',
			'headers' => '',
			'attachments' => [],
		];

		$result = $delete_book->addCurrentUserToDeleteEmail( $args );
		$this->assertIsArray( $result['to'] );
		$this->assertContains( 'bookadmin@example.com', $result['to'] );
		$this->assertContains( 'requester@example.com', $result['to'] );
	}

	/**
	 * @group deletebook
	 */
	public function test_addCurrentUserToDeleteEmail_skips_when_same_email() {
		$delete_book = new \Pressbooks\Admin\Delete\Book();

		$user_id = $this->factory()->user->create( [
			'user_email' => 'bookadmin@example.com',
			'role' => 'administrator',
		] );
		grant_super_admin( $user_id );
		wp_set_current_user( $user_id );

		update_option( 'admin_email', 'bookadmin@example.com' );
		$subject = sprintf( '[%s] Delete My Site', wp_specialchars_decode( get_option( 'blogname' ) ) );

		$args = [
			'to' => 'bookadmin@example.com',
			'subject' => $subject,
			'message' => 'Delete confirmation',
			'headers' => '',
			'attachments' => [],
		];

		$result = $delete_book->addCurrentUserToDeleteEmail( $args );
		$this->assertEquals( 'bookadmin@example.com', $result['to'] );
	}
}
