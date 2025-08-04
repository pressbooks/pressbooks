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
	public function set_up() {
		parent::set_up();
		$this->weblinks = new Pressbooks\Modules\Export\ThinCC\WebLinks( [] );
	}

	public function test_sanityCheckExports() {
		$this->_book();
		$converter = $this->weblinks->convert();
		$this->runGenerator( $converter );
		$validator = $this->weblinks->validate();
		$this->runGenerator( $validator );
		$this->assertInstanceOf( Generator::class, $converter );
		$this->assertInstanceOf( Generator::class, $validator );
		$this->assertNotEmpty( $converter->getReturn() );
		$this->assertTrue( $validator->getReturn() );
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
		$this->assertStringContainsString( '</item>', $xml );
		$this->assertStringContainsString( 'identifier=', $xml );
		$this->assertStringContainsString( 'identifierref=', $xml );
	}

	public function test_resources() {
		$this->_book();
		$xml = $this->weblinks->resources();
		$this->assertStringContainsString( '</resource>', $xml );
		$this->assertStringContainsString( '<file href=', $xml );
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
