<?php

use Pressbooks\Container;

class FakeContainer extends \Illuminate\Container\Container {
}

class AnotherFakeContainer extends \Illuminate\Container\Container {
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

	public function test_initSetGet() {

		Container::setInstance( new FakeContainer() );
		$this->assertTrue( Container::getInstance() instanceof FakeContainer );

		Container::setInstance( new AnotherFakeContainer() );
		$this->assertTrue( Container::getInstance() instanceof AnotherFakeContainer );
	}

	public function test_getSet() {

		Container::init( new FakeContainer() );

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

	/**
	 * @expectedException \LogicException
	 */
	public function test_getException() {
		$var = Container::get( 'foo' );
	}

	/**
	 * @expectedException \LogicException
	 */
	public function test_setException() {
		Container::set( 'foo', 'bar' );
	}

}
