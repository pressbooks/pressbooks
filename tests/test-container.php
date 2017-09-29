<?php

use Pressbooks\Container;

class FakePimpleContainer extends \Illuminate\Container\Container {
}

class AnotherFakePimpleContainer extends \Illuminate\Container\Container {
}


class ContainerTest extends \WP_UnitTestCase {


	/**
	 * WP Unit test framework auto initializes our Container but we don't want this, clear it  before running tests
	 */
	public function setUp() {

		parent::setUp();
		Container::setInstance( null );
	}

	/**
	 * Put back Container to the way it was
	 */
	public function tearDown() {

		Container::init();
		parent::tearDown();
	}

	public function test_initSetGetPimple() {

		Container::setInstance( new FakePimpleContainer() );
		$this->assertTrue( Container::getInstance() instanceof FakePimpleContainer );

		Container::setInstance( new AnotherFakePimpleContainer() );
		$this->assertTrue( Container::getInstance() instanceof AnotherFakePimpleContainer );
	}

	public function test_getSet() {

		Container::init( new FakePimpleContainer() );

		Container::set(
			'test1', function () {
				return 'test1';
			}
		);
		Container::set(
			'test2', function () {
				return 'test2';
			}, 'factory'
		);
		Container::set(
			'test3', function () {
				return 'test3';
			}, 'protect'
		);

		$var1 = Container::get( 'test1' );
		$var2 = Container::get( 'test2' );
		$var3 = Container::get( 'test3' );

		$this->assertTrue( 'test1' == $var1 );

		$this->assertTrue( 'test2' == $var2 );

		$this->assertTrue( is_object( $var3 ) && ( $var3 instanceof Closure ) );
		$this->assertTrue( 'test3' == $var3() );

		// Should not replace
		Container::set(
			'test1', function () {
				return 'test4';
			}
		);
		$var4 = Container::get( 'test1' );
		$this->assertTrue( 'test1' == $var4 );

		// Should replace
		Container::set(
			'test1', function () {
				return 'test4';
			},
			null, true
		);
		$var5 = Container::get( 'test1' );
		$this->assertTrue( 'test4' == $var5 );
	}

	public function test_getException() {

		$this->setExpectedException( '\LogicException' );
		$var = Container::get( 'foo' );
	}

	public function test_setException() {

		$this->setExpectedException( '\LogicException' );
		Container::set( 'foo', 'bar' );
	}

}
