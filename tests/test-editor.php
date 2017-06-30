<?php

use Pressbooks\Editor;

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

		$this->assertFileExists( WP_CONTENT_DIR . '/uploads/sites/' . $blog_id . '/css/editor.css' );
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
	}

	public function test_mce_button_scripts() {

		$x = Pressbooks\Editor\mce_button_scripts( [] );

		$this->assertArrayHasKey( 'table', $x );
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
	}

	public function test_customize_wp_link_query_args() {

		$x = \Pressbooks\Editor\customize_wp_link_query_args( [ 'post_type' => [ 'post' ] ] );

		$this->assertFalse( in_array( 'post', $x['post_type'] ) );
		$this->assertTrue( in_array( 'chapter', $x['post_type'] ) );
	}

}
