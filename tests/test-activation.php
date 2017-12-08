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
			->getMockBuilder( '\Pressbooks\Taxonomy' )
			->setConstructorArgs( [ $stub1 ] )
			->getMock();

		$this->activation = new Activation( $stub2 );
	}

	public function test_init() {
		$instance = Activation::init();
		$this->assertTrue( $instance instanceof Activation );
	}

	public function test_hooks() {
		$this->activation->hooks( $this->activation );
		$this->assertEquals( 9, has_filter( 'wpmu_new_blog', [ $this->activation, 'wpmuNewBlog' ] ) );
		$this->assertEquals( 10, has_filter( 'user_register', [ $this->activation, 'forcePbColors' ] ) );
	}

}