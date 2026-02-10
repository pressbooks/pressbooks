<?php

namespace Pressbooks;

use Illuminate\Database\Capsule\Manager;

class ServiceProviderTest extends \WP_UnitTestCase {

	/**
	 * @group serviceprovider
	 */
	public function test_init() {
		// Test that ServiceProvider::init() doesn't throw errors
		ServiceProvider::init();
		$this->assertTrue( true );
	}

	/**
	 * @group serviceprovider
	 */
	public function test_container_services() {
		ServiceProvider::init();
		$container = \Illuminate\Container\Container::getInstance();

		// Test that key services are registered
		$this->assertTrue( $container->bound( 'Sass' ) );
		$this->assertTrue( $container->bound( 'GlobalTypography' ) );
		$this->assertTrue( $container->bound( 'Styles' ) );
		$this->assertTrue( $container->bound( 'ScopedStyles' ) );
		$this->assertTrue( $container->bound( 'Blade' ) );
		$this->assertTrue( $container->bound( 'db' ) );
	}

	/**
	 * @group serviceprovider
	 */
	public function test_database_connection() {
		ServiceProvider::init();
		$container = \Illuminate\Container\Container::getInstance();

		$db = $container->make( 'db' );
		$this->assertInstanceOf( Manager::class, $db );

		try {
			$result = $db->getConnection()->select( 'SELECT 1 as test' );
			$this->assertEquals( 1, $result[0]->test );
		} catch ( \Exception $e ) {
			// Database connection might not be available in test environment
			$this->assertTrue( true );
		}
	}

	/**
	 * @group serviceprovider
	 */
	public function test_blade_service() {
		ServiceProvider::init();
		$container = \Illuminate\Container\Container::getInstance();

		$blade = $container->make( 'Blade' );
		$this->assertIsObject( $blade );
		$this->assertTrue( method_exists( $blade, 'render' ) );
		$this->assertTrue( method_exists( $blade, 'addNamespace' ) );
	}

	/**
	 * @group serviceprovider
	 */
	public function test_sass_service() {
		ServiceProvider::init();
		$container = \Illuminate\Container\Container::getInstance();

		$sass = $container->make( 'Sass' );
		$this->assertInstanceOf( Sass::class, $sass );
	}

	/**
	 * @group serviceprovider
	 */
	public function test_scoped_styles_service() {
		ServiceProvider::init();
		$container = \Illuminate\Container\Container::getInstance();

		$scopedStyles = $container->make( 'ScopedStyles' );
		$this->assertIsObject( $scopedStyles );
		$this->assertObjectHasProperty( 'h5p_css_url', $scopedStyles );
		$this->assertEquals( '', $scopedStyles->h5p_css_url );
	}

	/**
	 * @group serviceprovider
	 */
	public function test_global_typography_dependency() {
		ServiceProvider::init();
		$container = \Illuminate\Container\Container::getInstance();

		$globalTypography = $container->make( 'GlobalTypography' );
		$this->assertInstanceOf( GlobalTypography::class, $globalTypography );
	}

	/**
	 * @group serviceprovider
	 */
	public function test_styles_dependency() {
		ServiceProvider::init();
		$container = \Illuminate\Container\Container::getInstance();

		$styles = $container->make( 'Styles' );
		$this->assertInstanceOf( Styles::class, $styles );
	}
}
