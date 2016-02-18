<?php

use PressBooks\Container;

class FakePimpleContainer extends \Pimple\Container {
}

class AnotherFakePimpleContainer extends \Pimple\Container {
}


class ContainerTest extends \WP_UnitTestCase {


	/**
	 * WP Unit test framework auto initializes our Container but we don't want this, clear it  before running tests
	 */
	public function setUp() {

		parent::setUp();
		Container::setPimple( null );
	}

	/**
	 * Put back Container to the way it was
	 */
	public function tearDown() {

		Container::init();
		parent::tearDown();
	}


	/**
	 * @covers \PressBooks\Container::init
	 * @covers \PressBooks\Container::setPimple
	 * @covers \PressBooks\Container::getPimple
	 */
	public function test_initSetGetPimple() {

		Container::init( new FakePimpleContainer() );
		$this->assertTrue( Container::getPimple() instanceof FakePimpleContainer );

		Container::setPimple( new AnotherFakePimpleContainer() );
		$this->assertTrue( Container::getPimple() instanceof AnotherFakePimpleContainer );
	}


	/**
	 * @covers \PressBooks\Container::getPimple
	 */
	public function test_getPimpleException() {
		
		$this->setExpectedException( '\LogicException' );
		$p = Container::getPimple();
	}



	/**
	 * @covers \PressBooks\Container::get
	 * @covers \PressBooks\Container::set
	 */
	public function test_getSet() {

		Container::init( new FakePimpleContainer() );

		Container::set( 'test1', function () { return 'test1'; } );
		Container::set( 'test2', function () { return 'test2'; }, 'factory' );
		Container::set( 'test3', function () { return 'test3'; }, 'protect' );

		$var1 = Container::get( 'test1' );
		$var2 = Container::get( 'test2' );
		$var3 = Container::get( 'test3' );

		$this->assertTrue( 'test1' == $var1 );

		$this->assertTrue( 'test2' == $var2 );

		$this->assertTrue( is_object( $var3 ) && ( $var3 instanceof Closure ) );
		$this->assertTrue( 'test3' == $var3() );

	}


	/**
	 * @covers \PressBooks\Container::get
	 */
	public function test_getException() {

		$this->setExpectedException( '\LogicException' );
		$var = Container::get( 'foo' );
	}


	/**
	 * @covers \PressBooks\Container::set
	 */
	public function test_setException() {

		$this->setExpectedException( '\LogicException' );
		Container::set( 'foo', 'bar' );
	}

}
