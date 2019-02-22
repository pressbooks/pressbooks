<?php

use Pressbooks\Activation;

class ActivationTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Activation
	 */
	protected $activation;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();

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
		$this->assertTrue( $instance instanceof Activation );
	}

	/**
	 * @group activation
	 */
	public function test_hooks() {
		$this->activation->hooks( $this->activation );
		$this->assertEquals( 11, has_filter( 'wp_initialize_site', [ $this->activation, 'wpmuNewBlog' ] ) );
		$this->assertEquals( 10, has_filter( 'user_register', [ $this->activation, 'forcePbColors' ] ) );
	}

}
