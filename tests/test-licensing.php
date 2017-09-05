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
		$result = $this->licensing->getSupportedTypes();
		$this->assertTrue( is_array( $result ) );
		foreach ( $result as $key => $val ) {
			$this->assertArrayHasKey( 'api', $val );
			$this->assertArrayHasKey( 'url', $val );
			$this->assertArrayHasKey( 'desc', $val );
		}
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

	public function test_getLicenseXml() {

		$result = $this->licensing->getLicenseXml( 'all-rights-reserved', 'Foo', 'http://pressbooks.dev', 'Bar', 'en', 1970 );
		$this->assertContains( 'All Rights Reserved', $result );
		$this->assertContains( '1970', $result );
		$this->assertContains( '</result>', $result );

		$result = $this->licensing->getLicenseXml( 'cc-by-nc-nd', 'Foo', 'http://pressbooks.dev', 'Bar', 'fr' );
		$this->assertContains( 'by-nc-nd', $result );
		$this->assertContains( 'Ceci peut être', $result );
		$this->assertContains( '</result>', $result );

		$result = $this->licensing->getLicenseXml( 'unsupported-type', 'Foo', 'http://pressbooks.dev', 'Bar', 'fr' );
		$this->assertEmpty( $result );
	}

}
