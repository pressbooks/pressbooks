<?php

class CovergeneratorTest extends \WP_UnitTestCase {


	/**
	 * @var \Pressbooks\Covergenerator\Covergenerator
	 */
	public $cg;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->cg = new \Pressbooks\Covergenerator\Covergenerator();
	}

	public function  test_checkDependencies() {
		$this->assertInternalType( 'bool', $this->cg->hasDependencies() );
		$this->assertTrue( defined( 'PB_CONVERT_COMMAND' ) );
		$this->assertTrue( defined( 'PB_GS_COMMAND' ) );
		$this->assertTrue( defined( 'PB_PDFINFO_COMMAND' ) );
		$this->assertTrue( defined( 'PB_PDFTOPPM_COMMAND' ) );
		$this->assertTrue( defined( 'PB_PRINCE_COMMAND' ) );
	}

}