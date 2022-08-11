<?php

class PressbooksTest extends \WP_UnitTestCase {
	/**
	 * @var \Pressbooks\Pressbooks()
	 * @group plugin
	 */
	protected $pb;

	/**
	 * @group plugin
	 */
	public function set_up() {
		parent::set_up();
		$this->pb = new \Pressbooks\Pressbooks();
	}

	/**
	 * @group plugin
	 */
	public function test_allowedBookThemes() {
		$result = $this->pb->allowedBookThemes( [ 'pressbooks-book' => true, 'pressbooks-clarke' => true, 'pressbooks-fake' => true, 'twentyseventeen' => true ] );
		$this->assertTrue( is_array( $result ) );
		$this->assertCount( 2, $result );
		$this->assertArrayHasKey( 'pressbooks-book', $result );
		$this->assertArrayHasKey( 'pressbooks-clarke', $result );
	}

	/**
	 * @group plugin
	 */
	public function test_allowedRootThemes() {
		$result = $this->pb->allowedRootThemes( [ 'pressbooks-book' => true, 'pressbooks-clarke' => true, 'pressbooks-fake' => true, 'twentytwenty' => true ] );
		$this->assertTrue( is_array( $result ) );
		$this->assertCount( 1, $result );
		$this->assertArrayHasKey( 'twentytwenty', $result );
		// TODO: Travis CI doesn't download (git clone) the root theme so we can't test it yet
		// @see: https://github.com/pressbooks/pressbooks/blob/dev/bin/install-wp-tests.sh
	}
}
