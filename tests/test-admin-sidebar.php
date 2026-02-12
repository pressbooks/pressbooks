<?php

use Pressbooks\Admin\Menus\SideBar;

/**
 * @group sidebar-menu
 */
class testAdminSidebar extends \WP_UnitTestCase
{
	use utilsTrait;

	/**
	 * @test
	 */
	public function it_adds_hooks_for_book_context(): void
	{
		$this->_book();

		global $wp_filter;

		SideBar::init();

		$this->assertArrayHasKey('admin_menu', $wp_filter);
		$this->assertArrayHasKey('admin_init', $wp_filter);
	}

	/**
	 * @test
	 */
	public function it_removes_patterns_submenu_item(): void
	{
		global $submenu;

		$submenu['themes.php'] = [
			[
				'Patterns',
				'edit_theme_options',
				'edit.php?post_type=wp_block',
			],
			[
				'Theme Options',
				'edit_theme_options',
				'themes.php?page=pressbooks_theme_options',
			]
		];

		(new SideBar)->removePatternsSubMenuItem();

		$this->assertCount(1, $submenu['themes.php']);
		$this->assertNotContains('edit.php?post_type=wp_block', $submenu['themes.php'][1]);
	}

	/**
	 * @test
	 */
	public function it_restricts_patterns_page_access(): void {
		global $pagenow;
		$pagenow = 'edit.php';
		$_GET['post_type'] = 'wp_block';

		try {
			(new SideBar)->restrictPatternsPageAccess();
		} catch (WPDieException $e) {
			$this->assertEquals('Sorry, you are not allowed to access this page.', $e->getMessage());
		}
	}

	/**
	 * @test
	 */
	public function it_does_not_restrict_other_edit_pages(): void {
		global $pagenow;
		$pagenow = 'edit.php';
		$_GET['post_type'] = 'another_post_type';

		try {
			(new SideBar)->restrictPatternsPageAccess();
			$this->assertTrue(true);
		} catch (WPDieException) {
			$this->fail('Should not restrict access to other edit pages');
		}
	}

	/**
	 * @test
	 */
	public function it_restricts_posts_page_access_with_no_post_type(): void {
		global $pagenow;
		$pagenow = 'edit.php';
		unset($_GET['post_type']);

		try {
			(new SideBar)->restrictPostsPageAccess();
			$this->fail('Should have restricted access to edit.php with no post_type');
		} catch (WPDieException $e) {
			$this->assertEquals('Sorry, you are not allowed to access this page.', $e->getMessage());
		}
	}

	/**
	 * @test
	 */
	public function it_restricts_posts_page_access_with_post_type_post(): void {
		global $pagenow;
		$pagenow = 'edit.php';
		$_GET['post_type'] = 'post';

		try {
			(new SideBar)->restrictPostsPageAccess();
			$this->fail('Should have restricted access to edit.php?post_type=post');
		} catch (WPDieException $e) {
			$this->assertEquals('Sorry, you are not allowed to access this page.', $e->getMessage());
		}
	}

	/**
	 * @test
	 */
	public function it_restricts_new_post_page_access_with_no_post_type(): void {
		global $pagenow;
		$pagenow = 'post-new.php';
		unset($_GET['post_type']);

		try {
			(new SideBar)->restrictPostsPageAccess();
			$this->fail('Should have restricted access to post-new.php with no post_type');
		} catch (WPDieException $e) {
			$this->assertEquals('Sorry, you are not allowed to access this page.', $e->getMessage());
		}
	}

	/**
	 * @test
	 */
	public function it_restricts_new_post_page_access_with_post_type_post(): void {
		global $pagenow;
		$pagenow = 'post-new.php';
		$_GET['post_type'] = 'post';

		try {
			(new SideBar)->restrictPostsPageAccess();
			$this->fail('Should have restricted access to post-new.php?post_type=post');
		} catch (WPDieException $e) {
			$this->assertEquals('Sorry, you are not allowed to access this page.', $e->getMessage());
		}
	}

	/**
	 * @test
	 */
	public function it_allows_access_to_book_post_types(): void {
		global $pagenow;
		
		// Test chapter
		$pagenow = 'edit.php';
		$_GET['post_type'] = 'chapter';
		try {
			(new SideBar)->restrictPostsPageAccess();
			$this->assertTrue(true);
		} catch (WPDieException) {
			$this->fail('Should allow access to edit.php?post_type=chapter');
		}

		// Test front-matter
		$_GET['post_type'] = 'front-matter';
		try {
			(new SideBar)->restrictPostsPageAccess();
			$this->assertTrue(true);
		} catch (WPDieException) {
			$this->fail('Should allow access to edit.php?post_type=front-matter');
		}

		// Test back-matter
		$_GET['post_type'] = 'back-matter';
		try {
			(new SideBar)->restrictPostsPageAccess();
			$this->assertTrue(true);
		} catch (WPDieException) {
			$this->fail('Should allow access to edit.php?post_type=back-matter');
		}
	}
}
