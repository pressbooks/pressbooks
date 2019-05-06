<?php

class Modules_Export_ThinCCTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Modules\Export\ThinCC\WebLinks
	 */
	protected $weblinks;


	use utilsTrait;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->weblinks = new Pressbooks\Modules\Export\ThinCC\WebLinks( [] );


	}

	public function test_sanityCheckExports() {
		$this->_book();
		$this->assertTrue( $this->weblinks->convert(), "Could not convert with CommonCartridge11" );
		$this->assertTrue( $this->weblinks->validate(), "Could not validate with CommonCartridge11" );
	}

	public function test_deleteTmpDir() {
		$this->assertTrue( file_exists( $this->weblinks->getTmpDir() ) );
		$this->weblinks->deleteTmpDir();
		$this->assertFalse( file_exists( $this->weblinks->getTmpDir() ) );
	}

	public function test_createManifest() {
		$this->_book();
		$this->weblinks->createManifest();
		$this->assertTrue( file_exists( $this->weblinks->getTmpDir() . '/imsmanifest.xml' ) );
	}

	public function test_identifiers() {
		$this->_book();
		$xml = $this->weblinks->identifiers();
		$this->assertContains( '</item>', $xml );
		$this->assertContains( 'identifier=', $xml );
		$this->assertContains( 'identifierref=', $xml );
	}

	public function test_resources() {
		$this->_book();
		$xml = $this->weblinks->resources();
		$this->assertContains( '</resource>', $xml );
		$this->assertContains( '<file href=', $xml );
	}

	public function test_createResources() {
		$this->_book();
		$this->weblinks->createResources();
		foreach ( scandir( $this->weblinks->getTmpDir() ) as $file ) {
			if ( substr( $file, 0, 2 ) === 'R_' && preg_match( '/\.xml/', $file ) ) {
				// At least one resource was created
				$this->assertTrue( true );
				return;
			}
		}
		$this->fail();
	}

	public function test_showInWeb() {
		$this->assertFalse( $this->weblinks->showInWeb( 'draft' ) );
		$this->assertFalse( $this->weblinks->showInWeb( 'private' ) );
		$this->assertTrue( $this->weblinks->showInWeb( 'web-only' ) );
		$this->assertTrue( $this->weblinks->showInWeb( 'publish' ) );
	}

}