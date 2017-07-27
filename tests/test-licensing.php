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

	public function test_get_web_license_html() {

		$xml = new \SimpleXMLElement( '<book><title>Hello World!</title></book>' );
		$result = $this->licensing->getWebLicenseHtml( $xml );
		$this->assertContains( 'Hello World!', $result );
		$this->assertContains( 'creativecommons.org', $result );
		$this->assertContains( '</div>', $result );
	}

	public function test_get_url_for_license() {
		$result = $this->licensing->getUrlForLicense( 'public-domain' );
		$this->assertEquals( $result, 'https://creativecommons.org/publicdomain/zero/1.0/' );
	}

	public function test_get_license_xml() {

		$result = $this->licensing->getLicenseXml( 'all-rights-reserved', 'Foo', 'http://pressbooks.dev', 'Bar', 'en' );
		$this->assertContains( 'All Rights Reserved', $result );
		$this->assertContains( '</result>', $result );

		$result = $this->licensing->getLicenseXml( 'cc-by-nc-nd', 'Foo', 'http://pressbooks.dev', 'Bar', 'fr' );
		$this->assertContains( 'by-nc-nd', $result );
		$this->assertContains( 'Ceci peut être', $result );
		$this->assertContains( '</result>', $result );

		$result = $this->licensing->getLicenseXml( 'unsupported-type', 'Foo', 'http://pressbooks.dev', 'Bar', 'fr' );
		$this->assertEmpty( $result );
	}

}