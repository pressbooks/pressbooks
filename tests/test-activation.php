<?php

use Pressbooks\Activation;

class ActivationTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @var \Pressbooks\Activation
	 */
	protected $activation;

	public function set_up() {
		parent::set_up();

		$stub1 = $this
			->getMockBuilder( '\Pressbooks\Licensing' )
			->getMock();

		$stub2 = $this
			->getMockBuilder( '\Pressbooks\Contributors' )
			->getMock();

		$stub3 = $this
			->getMockBuilder( '\Pressbooks\Taxonomy' )
			->setConstructorArgs( [ $stub1, $stub2 ] )
			->getMock();

		$this->activation = new Activation( $stub3 );
	}

	/**
	 * @group activation
	 */
	public function test_init() {
		$instance = Activation::init();

		$this->assertInstanceOf( Activation::class, $instance );
	}

	/**
	 * @group activation
	 */
	public function test_hooks() {
		$this->activation->hooks( $this->activation );
		$this->assertEquals( 11, has_filter( 'wp_initialize_site', [ $this->activation, 'wpmuNewBlog' ] ) );
		$this->assertEquals( 10, has_filter( 'user_register', [ $this->activation, 'forcePbColors' ] ) );
	}

	/**
	 * @group activation
	 */
	public function test_defaultAdminColor() {
		$x = $this->activation->defaultAdminColor( 'fresh', 'admin_color' );
		$this->assertEquals( $x, 'pb_colors' );

		$x = $this->activation->defaultAdminColor( 'some_other_color', 'admin_color' );
		$this->assertEquals( $x, 'some_other_color' );

		$x = $this->activation->defaultAdminColor( 'fresh', 'some_other_option' );
		$this->assertEquals( $x, 'fresh' );
	}

}
