<?php

class Interactive_H5PTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Interactive\H5P
	 * @group interactivecontent
	 */
	protected $h5p;


	/**
	 * @group interactivecontent
	 */
	public function setUp() {
		parent::setUp();
		$blade = \Pressbooks\Container::get( 'Blade' );
		$this->h5p = new \Pressbooks\Interactive\H5P( $blade );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_isActive() {
		$this->assertTrue( is_bool( $this->h5p->isActive() ) );
	}

	/**
	 * @group interactivecontent
	 */
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

	/**
	 * @group interactivecontent
	 */
	public function test_override() {
		global $shortcode_tags;
		$this->h5p->override();
		$this->assertArrayHasKey( 'h5p', $shortcode_tags );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_replaceCloneable() {
		$content = '[h5p id="1"][h5p id=\'2\' something="else"][h5p id=3]';
		$result = $this->h5p->replaceUncloneable( $content );
		$this->assertNotContains( '[h5p ', $result );
		$this->assertContains( 'The original version of this chapter contained H5P content', $result );

		$content = '[h5p id="1"][h5p id=\'2\' something="else"][h5p id=3]';
		$result = $this->h5p->replaceUncloneable( $content, [ 1, "2" ] );
		$this->assertNotContains( '[h5p id="1', $result );
		$this->assertNotContains( '[h5p id=\'2', $result );
		$this->assertContains( '[h5p id=3]', $result );
		$this->assertContains( 'The original version of this chapter contained H5P content', $result );

		$content = '[h5p id="1"][h5p id=\'2\' something="else"][h5p id=3]';
		$result = $this->h5p->replaceUncloneable( $content, 3 );
		$this->assertNotContains( '[h5p id=3', $result );
		$this->assertContains( '[h5p id="1', $result );
		$this->assertContains( '[h5p id=\'2', $result );
		$this->assertContains( 'The original version of this chapter contained H5P content', $result );
	}

}
