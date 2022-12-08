<?php

use Pressbooks\Admin\Dashboard\BookDashboard;

/**
 * @group book-dashboard
 */
class BookDashboardTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var BookDashboard
	 */
	protected $bookDashboard;

	/**
	 * Test setup
	 */
	public function set_up() {
		parent::set_up();

		$this->bookDashboard = new BookDashboard();
	}

	/**
	 * @test
	 */
	public function it_checks_instance(): void {
		$this->assertInstanceOf( '\Pressbooks\Admin\Dashboard\BookDashboard', $this->bookDashboard->init() );
	}

	/**
	 * @test
	 */
	public function it_checks_hooks(): void {
		global $wp_filter;
		$this->bookDashboard->init();
		$this->bookDashboard->hooks();
		$this->assertArrayHasKey( 'load-index.php', $wp_filter );
		$this->assertArrayHasKey( 'admin_head', $wp_filter );
		$this->assertArrayHasKey( 'admin_menu', $wp_filter );
	}

	/**
	 * @test
	 */
	public function it_renders_subscribers_book_dashboard(): void {
		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		$this->bookDashboard->renderBookDashboard();
		$this->expectOutputRegex( '/<div class="book-dash wrap">/' );
		$this->expectOutputRegex( '/^((?!<div class="pb-book-cover">).)*$/s' );
		$this->expectOutputRegex( '/^((?!<div class="pb-dashboard-action">).)*$/s' );
	}

	/**
	 * @test
	 */
	public function it_renders_editor_book_dashboard(): void {
		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		$this->bookDashboard->renderBookDashboard();
		$this->expectOutputRegex( '/<div class="book-dash wrap">/' );
		$this->expectOutputRegex( '/<div class="pb-book-cover">/' );
		$this->expectOutputRegex( '/<div class="pb-dashboard-action">/' );
		$this->expectOutputRegex( '/^((?!<li id="delete">).)*$/s' );
	}

	/**
	 * @test
	 */
	public function it_renders_administrator_book_dashboard(): void {
		$this->_book();
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );

		$this->bookDashboard->renderBookDashboard();
		$this->expectOutputRegex( '/<div class="book-dash wrap">/' );
		$this->expectOutputRegex( '/<div class="pb-book-cover">/' );
		$this->expectOutputRegex( '/<div class="pb-dashboard-action">/' );
		$this->expectOutputRegex( '/<li id="delete">/' );
	}
}
