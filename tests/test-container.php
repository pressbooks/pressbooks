<?php

use Pressbooks\Container;
use Pressbooks\ServiceProvider;

class FakeContainer extends \Illuminate\Container\Container {
}

class AnotherFakeContainer extends \Illuminate\Container\Container {
}


class ContainerTest extends \WP_UnitTestCase {
	/**
	 * @group container
	 * WP Unit test framework auto initializes our Container but we don't want this, clear it  before running tests
	 */
	public function set_up() {
		parent::set_up();

		Container::getInstance()->setInstance( null );
	}

	/**
	 * @group container
	 * Put back Container to the way it was
	 */
	public function tear_down() {
		ServiceProvider::init();

		parent::tear_down();
	}

	/**
	 * @group container
	 */
	public function test_initSetGet() {
		Container::getInstance()->setInstance( new FakeContainer() );

		$this->assertInstanceOf( FakeContainer::class, Container::getInstance() );

		Container::getInstance()->setInstance( new AnotherFakeContainer() );

		$this->assertInstanceOf( AnotherFakeContainer::class, Container::getInstance() );
	}

	/**
	 * @group container
	 */
	public function test_getSet() {
		Container::getInstance()->setInstance( new FakeContainer() );

		Container::set( 'test1', function () {
			return 'test1';
		} );
		Container::set( 'test2', function () {
			return 'test2';
		}, 'factory' );
		Container::set( 'test3', function () {
			return 'test3';
		}, 'protect' );

		$test3 = Container::get( 'test3' );

		$this->assertEquals( 'test1',Container::get( 'test1' ) );
		$this->assertEquals( 'test2', Container::get( 'test2' ) );
		$this->assertInstanceOf( Closure::class, $test3 );
		$this->assertEquals( 'test3', $test3() );

		// Should not replace
		Container::set( 'test1', function () {
			return 'test4';
		} );

		$this->assertEquals( 'test1', Container::get( 'test1' ) );

		// Should replace
		Container::set( 'test1', function () {
			return 'test4';
		}, null, true );

		$this->assertEquals( 'test4', Container::get( 'test1' ) );
	}

	/**
	 * @group container
	 */
	public function test_getException() {
		$this->expectException(\Illuminate\Container\EntryNotFoundException::class);

		$var = Container::get( 'foo' );
	}

	/**
	 * @test
	 * @group container
	 */
	public function it_adds_namespace_to_blade_view_finder() {
		ServiceProvider::init();

		$blade = Container::get( 'Blade' );

		$this->expectException( InvalidArgumentException::class );

		$blade->render( 'Foo::template', [ 'name' => 'World'] );

		$blade->addNamespace( 'Foo', __DIR__ . '/data' );

		$this->assertEquals( '<div>Hello, World!</div>', $blade->render( 'Foo::template', [ 'name' => 'World'] ) );
	}
}
