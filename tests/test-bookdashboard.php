<?php

use Pressbooks\Admin\Dashboard\BookDashboard;
use Pressbooks\Metadata;
use function Pressbooks\Admin\Metaboxes\upload_cover_image;

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

	/**
	 * @test
	 */
	public function it_retrieves_default_book_cover(): void {
		$this->_book();

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		ob_start();
		$this->bookDashboard->renderBookDashboard();
		$output = ob_get_clean();

		$this->assertStringContainsString( 'images/default-book-cover-225x0@2x.jpg', $output );
	}

	/**
	 * @test
	 */
	public function it_retrieves_book_cover(): void {
		$this->_book();

		wp_set_current_user( $this->factory()->user->create( [ 'role' => 'administrator' ] ) );

		$_FILES = [
			'pb_cover_image' => [
				'name' => 'image.jpeg',
			],
		];

		copy( __DIR__ . '/data/upload/image.jpeg', __DIR__ . '/data/upload/image_test.jpeg' );

		$image = [
			'file' => __DIR__ . '/data/upload/image_test.jpeg',
			'url' => 'https://pressbooks.test/app/uploads/sites/4/2021/07/image.jpeg',
			'type' => 'image/jpeg',
		];

		$post_id = ( new Metadata )->getMetaPostId();

		upload_cover_image( $post_id, null, $image );

		ob_start();
		$this->bookDashboard->renderBookDashboard();
		$output = ob_get_clean();

		$this->assertStringNotContainsString( 'images/default-book-cover-225x0@2x.jpg', $output );
		$this->assertStringContainsString( 'data/upload/image_test.jpeg', $output );
	}
}
