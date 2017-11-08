<?php

class Shortcodes_H5P extends \WP_UnitTestCase {

	public function test_isActive() {
		$h5p = new \Pressbooks\Shortcodes\H5P\H5P();
		$this->assertTrue( is_bool( $h5p->isActive() ) );
	}

	public function test_shortcodeHandler() {
		$h5p = new \Pressbooks\Shortcodes\H5P\H5P();
		$this->assertContains("<p class='h5p'>", $h5p->shortcodeHandler( [] ) );
		$this->assertContains("<p class='h5p'>", $h5p->shortcodeHandler( [ 'slug' => 'foo' ] ) );
		$this->assertContains("<p class='h5p'>", $h5p->shortcodeHandler( [ 'id' => 999 ] ) );
	}

	public function test_override() {
		global $shortcode_tags;

		$h5p = new \Pressbooks\Shortcodes\H5P\H5P();
		$h5p->override();
		$this->assertArrayHasKey( 'h5p', $shortcode_tags );
	}

}