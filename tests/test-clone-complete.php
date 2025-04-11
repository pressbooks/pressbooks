<?php

use Pressbooks\CloneComplete;

class CloneCompleteTest extends \WP_UnitTestCase {

	public function setUp(): void
	{
		parent::setUp();
		CloneComplete::install();
	}

	/**
	 * @group cloner
	 * @test
	 */
	public function clone_complete_table_is_being_created(): void
	{
		global $wpdb;
		CloneComplete::createTable();
		$this->assertNotEmpty( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}pressbooks_completed_clones'" ) );
	}

	/**
	 * @group cloner
	 * @test
	 */
	public function cloned_books_are_retrieved_by_current_blog_id(): void
	{
		global $wpdb;
		$wpdb->insert( $wpdb->prefix . 'pressbooks_completed_clones',
			[ 'blog_id' => 1,
				'target_book_name' => 'hello clone',
				'target_book_url' => 'https://hello.com/book',
				'created_at' => time() ] );
		$wpdb->insert( $wpdb->prefix . 'pressbooks_completed_clones',
			[ 'blog_id' => 2,
				'target_book_name' => 'hello clone 2',
				'target_book_url' => 'https://hello.com/book',
				'created_at' => time() ] );
		$wpdb->insert( $wpdb->prefix . 'pressbooks_completed_clones',
			[ 'blog_id' => 3,
				'target_book_name' => 'hello clone 3',
				'target_book_url' => 'https://hello.com/book',
				'created_at' => time() ] );
		switch_to_blog(2);
		$clones = CloneComplete::getCloningStats();
		$this->assertNotEmpty( $clones );
		$this->assertEquals( 2, $clones[0]->blog_id );
		$this->assertEquals( 'hello clone 2', $clones[0]->target_book_name );
	}
}
