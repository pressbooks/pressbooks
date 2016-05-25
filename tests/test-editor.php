<?php

use Pressbooks\Editor;

class EditorTest extends \WP_UnitTestCase {

	/**
	 * @covers \Pressbooks\Editor\mce_valid_word_elements
	 */
	public function test_mce_valid_word_elements() {

		$array = Pressbooks\Editor\mce_valid_word_elements( [] );

		$this->assertArrayHasKey( 'paste_word_valid_elements', $array );

	}

	/**
	 * @covers \Pressbooks\Editor\add_languages
	 */
	public function test_add_languages() {

		$array = Pressbooks\Editor\add_languages( [] );

		$this->assertContains( PB_PLUGIN_DIR . 'languages/tinymce.php', $array );

	}

	/**
	 * @covers \Pressbooks\Editor\mce_buttons_2
	 */
	public function test_mce_buttons_2() {

		$buttons = Pressbooks\Editor\mce_buttons_2( [ 'formatselect' ] );

		$this->assertContains( 'styleselect', $buttons );

	}

	/**
	 * @covers \Pressbooks\Editor\mce_buttons_3
	 */
	public function test_mce_buttons_3() {

		$buttons = Pressbooks\Editor\mce_buttons_3( [] );

		$this->assertContains( 'anchor', $buttons );

	}

}
