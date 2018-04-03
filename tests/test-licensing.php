<?php

class LicensingTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Licensing()
	 */
	protected $licensing;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->licensing = new \Pressbooks\Licensing();
	}

	public function test_getSupportedTypes() {
		// Insert custom term
		wp_insert_term(
			'Fake Records', \Pressbooks\Licensing::TAXONOMY, [
				'slug' => 'fake-records',
			]
		);

		$result = $this->licensing->getSupportedTypes( false );
		$this->assertTrue( is_array( $result ) );
		foreach ( $result as $key => $val ) {
			$this->assertArrayHasKey( 'api', $val );
			$this->assertArrayHasKey( 'url', $val );
			$this->assertArrayHasKey( 'desc', $val );
		}
		$this->assertArrayHasKey( 'fake-records', $result );

		$result = $this->licensing->getSupportedTypes( true );
		$this->assertTrue( is_array( $result ) );
		foreach ( $result as $key => $val ) {
			$this->assertArrayHasKey( 'api', $val );
			$this->assertArrayHasKey( 'url', $val );
			$this->assertArrayHasKey( 'desc', $val );
		}
		$this->assertArrayHasKey( 'fake-records', $result );

		$result = $this->licensing->getSupportedTypes( false, true );
		$this->assertArrayNotHasKey( 'fake-records', $result );
		$result = $this->licensing->getSupportedTypes( true, true );
		$this->assertArrayNotHasKey( 'fake-records', $result );
	}

	public function test_disableTranslation() {
		$var = $this->licensing->disableTranslation( 'a', 'b', 'c' );
		$this->assertEquals( 'b', $var );
	}

	public function test_doLicense() {
		$result = $this->licensing->doLicense( [], 0, 'Hello World!' );
		$this->assertContains( 'All Rights Reserved', $result ); // Returns some default
		$this->assertContains( 'Hello World!', $result ); //
	}

	public function test_getWebLicenseHtml() {

		$xml = new \SimpleXMLElement( '<book><title>Hello World!</title></book>' );

		$result = $this->licensing->getLicenseHtml( $xml );
		$this->assertContains( 'Hello World!', $result );
		$this->assertContains( 'creativecommons.org', $result );
		$this->assertContains( 'except where otherwise noted', $result );
		$this->assertContains( '</div>', $result );

		$result = $this->licensing->getLicenseHtml( $xml, false );
		$this->assertContains( 'Hello World!', $result );
		$this->assertContains( 'creativecommons.org', $result );
		$this->assertNotContains( 'except where otherwise noted', $result );
		$this->assertContains( '</div>', $result );
	}

	public function test_getUrlForLicense() {
		$result = $this->licensing->getUrlForLicense( 'public-domain' );
		$this->assertEquals( $result, 'https://creativecommons.org/publicdomain/zero/1.0/' );
	}

	public function test_getLicenseFromUrl() {
		$result = $this->licensing->getLicenseFromUrl( 'https://creativecommons.org/publicdomain/zero/1.0/' );
		$this->assertEquals( $result, 'public-domain' );
	}

}
