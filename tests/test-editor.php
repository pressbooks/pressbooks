<?php

use Pressbooks\Editor;

class EditorTest extends \WP_UnitTestCase {

	public function test_mce_valid_word_elements() {

		$array = Pressbooks\Editor\mce_valid_word_elements( [] );

		$this->assertArrayHasKey( 'paste_word_valid_elements', $array );
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

}
