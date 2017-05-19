<?php

class PressbooksTest extends \WP_UnitTestCase {


	/**
	 * @var \Pressbooks\Pressbooks()
	 */
	protected $pb;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->pb = new \Pressbooks\Pressbooks();
	}

	/**
	 * @covers \Pressbooks\Pressbooks::allowedBookThemes
	 */
	public function test_allowedBookThemes() {
		$result = $this->pb->allowedBookThemes( array( 'pressbooks-mcluhan' ) );
		$this->assertTrue( is_array( $result ) );
	}

	/**
	 * @covers \Pressbooks\Pressbooks::allowedRootThemes
	 */
	public function test_allowedRootThemes() {
		$result = $this->pb->allowedRootThemes( array( 'pressbooks-librarian' ) );
		$this->assertTrue( is_array( $result ) );
	}
}
