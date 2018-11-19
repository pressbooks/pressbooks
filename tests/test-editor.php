<?php

class EditorTest extends \WP_UnitTestCase {

	use utilsTrait;

	public function test_mce_valid_word_elements() {

		$array = Pressbooks\Editor\mce_valid_word_elements( [] );

		$this->assertArrayHasKey( 'paste_word_valid_elements', $array );
	}

	public function test_update_editor_style() {
		$this->_book( 'pressbooks-clarke' );
		Pressbooks\Editor\update_editor_style();

		global $blog_id;

		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/sites/' . $blog_id . '/pressbooks/css/editor.css' );
	}

	public function test_add_editor_style() {
		$this->_book( 'pressbooks-clarke' );

		$file = \Pressbooks\Container::get( 'Sass' )->pathToUserGeneratedCss() . '/editor.css';
		if ( file_exists( $file ) ) {
			@unlink( $file );
		}
		$result = Pressbooks\Editor\add_editor_style();
		$this->assertFalse( $result, "Test failed. Maybe delete ($file) and try again?" );

		Pressbooks\Editor\update_editor_style();
		$result = Pressbooks\Editor\add_editor_style();
		$this->assertTrue( $result );
	}

	public function test_add_languages() {

		$array = Pressbooks\Editor\add_languages( [] );

		$this->assertContains( PB_PLUGIN_DIR . 'languages/tinymce.php', $array );
	}

	public function test_mce_buttons_2() {

		$buttons = Pressbooks\Editor\mce_buttons_2( [ 'formatselect' ] );

		$this->assertContains( 'styleselect', $buttons );
	}


	public function test_mce_buttons_3() {

		$buttons = Pressbooks\Editor\mce_buttons_3( [] );

		$this->assertContains( 'anchor', $buttons );
		$this->assertContains( 'footnote', $buttons );
		$this->assertContains( 'ftnref_convert', $buttons );
		$this->assertContains( 'glossary', $buttons );
		$this->assertContains( 'glossary_all', $buttons );
		$this->assertContains( 'wp_code', $buttons );
	}

	public function test_admin_enqueue_scripts() {
		\Pressbooks\Editor\admin_enqueue_scripts( 'post.php' );
		$this->assertTrue( wp_script_is( 'my_custom_quicktags', 'queue' ) );
		$this->assertTrue( wp_script_is( 'wp-api', 'queue' ) );
	}

	public function test_mce_button_scripts() {

		$x = Pressbooks\Editor\mce_button_scripts( [] );

		$this->assertArrayHasKey( 'table', $x );
		$this->assertArrayHasKey( 'footnote', $x );
		$this->assertArrayHasKey( 'ftnref_convert', $x );
		$this->assertArrayHasKey( 'glossary', $x );
	}

	public function test_mce_before_init_insert_formats() {

		$x = Pressbooks\Editor\mce_before_init_insert_formats( [] );

		$this->assertArrayHasKey( 'style_formats', $x );
	}

	public function test_metadata_manager_default_editor_args() {

		$x = Pressbooks\Editor\metadata_manager_default_editor_args( [] );

		$this->assertArrayHasKey( 'tinymce', $x );
	}

	public function test_mce_table_editor_options() {

		$x = Pressbooks\Editor\mce_table_editor_options( [] );

		$this->assertArrayHasKey( 'table_class_list', $x );
		$this->assertArrayHasKey( 'table_cell_class_list', $x );
		$this->assertArrayHasKey( 'table_row_class_list', $x );
		$this->assertFalse( $x['table_advtab'] );
		$this->assertFalse( $x['table_cell_advtab'] );
		$this->assertFalse( $x['table_row_advtab'] );
		$this->assertTrue( $x['table_responsive_width'] );
		$this->assertTrue( $x['table_appearance_options'] );
	}

	public function test_customize_wp_link_query_args() {

		$x = \Pressbooks\Editor\customize_wp_link_query_args( [ 'post_type' => [ 'post' ] ] );

		$this->assertFalse( in_array( 'post', $x['post_type'] ) );
		$this->assertTrue( in_array( 'chapter', $x['post_type'] ) );
	}

	public function test_add_anchors_to_wp_link_query() {

		$post_title = 'Chapter With Anchor: ' . rand();
		$new_post = [
			'post_title' => $post_title,
			'post_type' => 'chapter',
			'post_status' => 'publish',
			'post_content' => "<a id='anchor1'></a>Anchor's away! <a id='anchor2'></a>Anchors going my way?",
		];
		$post_id = $this->factory()->post->create_object( $new_post );

		$results[] = [ 'ID' => $post_id, 'title' => $post_title, 'permalink' => 'https://pressbooks.test/book/chapter/anchor/', 'info' => 'Chapter' ];
		$results[] = [ 'ID' => $post_id + 2, 'title' => 'Appendix', 'permalink' => 'https://pressbooks.test/book/back-matter/appendix/', 'info' => 'Back Matter' ];
		$results[] = [ 'ID' => $post_id + 3, 'title' => 'Introduction', 'permalink' => 'https://pressbooks.test/book/front-matter/introduction/', 'info' => 'Front Matter' ];
		$results[] = [ 'ID' => $post_id + 4, 'title' => 'Main Body', 'permalink' => 'https://pressbooks.test/book/part/main-body/', 'info' => 'Part' ];
		$results[] = [ 'ID' => $post_id + 5, 'title' => 'Chapter 1', 'permalink' => 'https://pressbooks.test/book/chapter/chapter-1/', 'info' => 'Chapter' ];


		/*
		// Not used
		// Looks something like this if the code were to change
		Array (
			[post_type] => Array
			(
				[0] => part
				[1] => chapter
				[2] => front-matter
				[3] => back-matter
			)
			[suppress_filters] => 1
			[update_post_term_cache] =>
			[update_post_meta_cache] =>
			[post_status] => publish
			[posts_per_page] => 20
			[offset] => 0
		)
		*/
		$query = [];


		// Relative to self
		$_SERVER['HTTP_REFERER'] = "https://pressbooks.test/book/wp-admin/post.php?post={$post_id}&action=edit";
		$new_results = \Pressbooks\Editor\add_anchors_to_wp_link_query( $results, $query );
		$this->assertEquals( $new_results[1]['permalink'], '#anchor1' );
		$this->assertEquals( $new_results[2]['permalink'], '#anchor2' );

		// Link to another chapter
		$_SERVER['HTTP_REFERER'] = "https://pressbooks.test/book/wp-admin/post.php?post=" . ( $post_id + 999 ) . "&action=edit";
		$new_results = \Pressbooks\Editor\add_anchors_to_wp_link_query( $results, $query );
		$this->assertEquals( $new_results[1]['permalink'], 'https://pressbooks.test/book/chapter/anchor#anchor1' );
		$this->assertEquals( $new_results[2]['permalink'], 'https://pressbooks.test/book/chapter/anchor#anchor2' );

		// An empty results array should not be modified
		$new_results = \Pressbooks\Editor\add_anchors_to_wp_link_query( [], $query );
		$this->assertEmpty( $new_results );
	}

	public function test_show_kitchen_sink() {
		$result = \Pressbooks\Editor\show_kitchen_sink( [] );
		$this->assertFalse( $result['wordpress_adv_hidden'] );
	}

	public function test_force_classic_editor_mode() {
		update_option( 'classic-editor-replace', 'no-replace' );
		$this->assertEquals( 'no-replace', get_option( 'classic-editor-replace' ) );
		\Pressbooks\Editor\hide_gutenberg();
		$this->assertTrue( has_filter( 'use_block_editor_for_post_type' ) );
		$this->assertFalse( is_plugin_active( 'gutenberg/gutenberg.php' ) );
		$this->assertEquals( 'replace', get_option( 'classic-editor-replace' ) );
	}

}
