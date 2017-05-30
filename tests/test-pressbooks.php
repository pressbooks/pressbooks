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

	public function test_allowedBookThemes() {
		$result = $this->pb->allowedBookThemes( [ 'pressbooks-mcluhan' ] );
		$this->assertTrue( is_array( $result ) );
	}

	public function test_allowedRootThemes() {
		$result = $this->pb->allowedRootThemes( [ 'pressbooks-librarian' ] );
		$this->assertTrue( is_array( $result ) );
	}
}
