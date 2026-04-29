<?php

use Pressbooks\Book;

class MsSiteCheckTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @group ms_site_check
	 */
	public function test_non_book_site_returns_null() {
		$callback = $this->getMsSiteCheckCallback();

		restore_current_blog();

		$this->assertNull( $callback() );
	}

	/**
	 * @group ms_site_check
	 */
	public function test_archived_public_book_allows_access() {
		$this->_book();
		$book_id = get_current_blog_id();

		$this->archiveBook( $book_id );
		update_blog_details( $book_id, [ 'public' => 1 ] );

		$callback = $this->getMsSiteCheckCallback();
		$this->assertTrue( $callback() );
	}

	/**
	 * @group ms_site_check
	 */
	public function test_archived_private_book_allows_access_for_logged_in_member() {
		$this->_book();
		$book_id = get_current_blog_id();

		$this->archiveBook( $book_id );
		update_blog_details( $book_id, [ 'public' => 0 ] );

		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		$callback = $this->getMsSiteCheckCallback();
		$this->assertTrue( $callback() );
	}

	/**
	 * @group ms_site_check
	 */
	public function test_archived_private_book_denies_access_for_non_member() {
		$this->_book();
		$book_id = get_current_blog_id();

		$this->archiveBook( $book_id );
		update_blog_details( $book_id, [ 'public' => 0 ] );

		$other_blog_id = $this->factory()->blog->create();
		$user_id = $this->factory()->user->create();
		global $wpdb;
		delete_user_meta( $user_id, $wpdb->get_blog_prefix() . 'capabilities' );
		add_user_to_blog( $other_blog_id, $user_id, 'editor' );
		wp_set_current_user( $user_id );

		$callback = $this->getMsSiteCheckCallback();
		$this->assertNull( $callback() );
	}

	/**
	 * @group ms_site_check
	 */
	public function test_archived_private_book_denies_access_for_logged_out_user() {
		$this->_book();
		$book_id = get_current_blog_id();

		$this->archiveBook( $book_id );
		update_blog_details( $book_id, [ 'public' => 0 ] );

		wp_set_current_user( 0 );

		$callback = $this->getMsSiteCheckCallback();
		$this->assertNull( $callback() );
	}

	/**
	 * @group ms_site_check
	 */
	public function test_non_archived_book_returns_null() {
		$this->_book();

		$callback = $this->getMsSiteCheckCallback();
		$this->assertNull( $callback() );
	}

	private function getMsSiteCheckCallback(): callable {
		global $wp_filter;
		$callbacks = $wp_filter['ms_site_check']->callbacks;
		foreach ( $callbacks as $priority => $hooks ) {
			foreach ( $hooks as $hook ) {
				if ( $priority === 1 && $hook['function'] instanceof Closure ) {
					return $hook['function'];
				}
			}
		}
		$this->fail( 'Could not find ms_site_check closure at priority 1' );
	}

	private function archiveBook( int $book_id ): void {
		global $wpdb;
		$wpdb->update(
			$wpdb->blogs,
			[ 'archived' => '1' ],
			[ 'blog_id' => $book_id ],
			[ '%s' ],
			[ '%d' ]
		);
		clean_blog_cache( $book_id );
	}
}
