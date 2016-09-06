<?php


class Shortcodes_Generics extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Shortcodes\Generics\Generics
	 */
	protected $generics;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();

		$this->generics = $this->getMockBuilder( '\Pressbooks\Shortcodes\Generics\Generics' )
			->setMethods( null )// pass null to setMethods() to avoid mocking any method
			->disableOriginalConstructor()// disable private constructor
			->getMock();
	}

	/**
	 * @covers \Pressbooks\Shortcodes\Generics\Generics::getInstance()
	 */
	public function test_getInstance() {
		$val = $this->generics->getInstance();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Generics\Generics );

	}

}
