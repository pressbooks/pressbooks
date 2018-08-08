<?php

class Shortcodes_Glossary extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Shortcodes\Glossary\Glossary
	 */
	protected $gl;

	public function setUp() {
		parent::setUp();

		$this->gl = $this->getMockBuilder( '\Pressbooks\Shortcodes\Glossary\Glossary' )
		                 ->setMethods( NULL )
		                 ->disableOriginalConstructor()
		                 ->getMock();

		$this->_createGlossaryTerms();
	}

	private function _createGlossaryTerms() {
		$args1 = [
			'post_type'    => 'glossary',
			'post_title'   => 'Neural Network',
			'post_content' => 'A computer system modeled on the human brain and nervous system',
		];

		$args2 = [
			'post_type'    => 'glossary',
			'post_title'   => 'Support Vector Machine',
			'post_content' => 'An algorithm that uses a nonlinear mapping to transform the original training data into a higher dimension',
		];

		$this->factory()->post->create( $args1 );
		$this->factory()->post->create( $args2 );
	}

	public function test_getInstance() {

		$val = $this->gl->init();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Glossary\Glossary );

		global $shortcode_tags;

		$this->assertArrayHasKey( 'pb_glossary', $shortcode_tags );

	}

	public function test_glossaryTerms() {
		$dl = $this->gl->glossaryTerms();

		$this->assertEquals( '<section data-type="glossary"><header><h2>Glossary Terms</h2></header><dl data-type="glossary"><dt data-type="glossterm"><dfn id="dfn-neural-network">Neural Network</dfn></dt><dd data-type="glossdef">A computer system modeled on the human brain and nervous system</dd><dt data-type="glossterm"><dfn id="dfn-support-vector-machine">Support Vector Machine</dfn></dt><dd data-type="glossdef">An algorithm that uses a nonlinear mapping to transform the original training data into a higher dimension</dd></dl></section>', $dl );

	}

}
