<?php

class Interactive_H5P_Test extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Interactive\H5P
	 */
	protected $h5p;

	public function setUp() {
		parent::setUp();
		$blade = \Pressbooks\Container::get( 'Blade' );
		$this->h5p = new \Pressbooks\Interactive\H5P( $blade );
	}


	public function test_isActive() {
		$this->assertTrue( is_bool( $this->h5p->isActive() ) );
	}

	public function test_shortcodeHandler() {
		$this->assertContains( '<div class="pb-interactive-content">', $this->h5p->shortcodeHandler( [] ) );
		$this->assertContains( '<div class="pb-interactive-content">', $this->h5p->shortcodeHandler( [ 'slug' => 'foo' ] ) );
		$this->assertContains( '<div class="pb-interactive-content">', $this->h5p->shortcodeHandler( [ 'id' => 999 ] ) );
	}

	public function test_override() {
		global $shortcode_tags;

		$this->h5p->override();
		$this->assertArrayHasKey( 'h5p', $shortcode_tags );
	}

}