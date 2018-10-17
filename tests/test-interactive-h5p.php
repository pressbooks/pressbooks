<?php

class Interactive_H5PTest extends \WP_UnitTestCase {

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

	public function test_replaceShortcode() {
		$result = $this->h5p->replaceShortcode( [] );
		$this->assertContains( '<div ', $result );
		$this->assertContains( 'excluded from this version of the text', $result );
		$result = $this->h5p->replaceShortcode( [ 'slug' => 'foo' ] );
		$this->assertContains( '<div ', $result );
		$this->assertContains( 'excluded from this version of the text', $result );
		$result = $this->h5p->replaceShortcode( [ 'id' => 999 ] );
		$this->assertContains( '<div ', $result );
		$this->assertContains( 'excluded from this version of the text', $result );
	}

	public function test_override() {
		global $shortcode_tags;
		$this->h5p->override();
		$this->assertArrayHasKey( 'h5p', $shortcode_tags );
	}

	public function test_replaceCloneable() {
		$content = '[h5p id="1"]';
		$result = $this->h5p->replaceCloneable( $content );
		$this->assertNotContains( '[h5p ', $result );
		$this->assertContains( 'The original version of this chapter contained H5P content', $result );
	}

	public function test_setCloneableWarning() {
		$this->h5p->setCloneableWarning();
		$this->h5p->setCloneableWarning();
		$this->h5p->setCloneableWarning();
		$this->asserttrue( count( $_SESSION['pb_notices'] ) === 1 );
		$this->assertContains( 'This book contains H5P content that cannot be cloned', $_SESSION['pb_notices'][0] );
		unset( $_SESSION['pb_notices'] );
	}

}
