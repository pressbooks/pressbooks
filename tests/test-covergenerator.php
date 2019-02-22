<?php

class CoverGeneratorTest extends \WP_UnitTestCase {


	/**
	 * @var \Pressbooks\Covergenerator\Covergenerator
	 * @group covergenerator
	 */
	public $cg;

	/**
	 * @group covergenerator
	 */
	public function setUp() {
		parent::setUp();
		$this->cg = new \Pressbooks\Covergenerator\Covergenerator();
	}

	/**
	 * @group covergenerator
	 */
	public function test_commandLineDefaults() {
		\Pressbooks\Covergenerator\Covergenerator::commandLineDefaults();
		$this->assertTrue( defined( 'PB_CONVERT_COMMAND' ) );
		$this->assertTrue( defined( 'PB_GS_COMMAND' ) );
		$this->assertTrue( defined( 'PB_PDFINFO_COMMAND' ) );
		$this->assertTrue( defined( 'PB_PDFTOPPM_COMMAND' ) );
		$this->assertTrue( defined( 'PB_PRINCE_COMMAND' ) );
	}

	/**
	 * @group covergenerator
	 */
	public function test_hasDependencies() {
		$this->assertInternalType( 'bool', $this->cg->hasDependencies() );
	}

}
