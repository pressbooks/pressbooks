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

	public function test_getLicense() {
		$result = $this->licensing->getLicense( 'public-domain', 'Herman Melville', 'https://mobydick.whale', 'Moby Dick', 1851 );
		$this->assertEquals( $result, '<div class="license-attribution"><p><img src="' . get_template_directory_uri() . '/packages/buckram/assets/images/public-domain.svg" alt="Icon for the Public Domain license" /></p><p>This work (<a href="https://mobydick.whale">Moby Dick</a> by Herman Melville) is free of known copyright restrictions.</p></div>' );
		$result = $this->licensing->getLicense( 'all-rights-reserved', 'Herman Melville', 'https://mobydick.whale', 'Moby Dick', 1851 );
		$this->assertEquals( $result, '<div class="license-attribution"><p><a href="https://mobydick.whale" property="dc:title">Moby Dick</a> Copyright &copy; 1851 by Herman Melville. All Rights Reserved.</p></div>' );
		$result = $this->licensing->getLicense( 'cc-by', 'Herman Melville', 'https://mobydick.whale', 'Moby Dick', 1851 );
		$this->assertEquals( $result, '<div class="license-attribution"><p><img src="' . get_template_directory_uri() . '/packages/buckram/assets/images/cc-by.svg" alt="Icon for the Creative Commons Attribution 4.0 International License" /></p><p><a rel="cc:attributionURL" href="https://mobydick.whale" property="dc:title">Moby Dick</a> by <span property="cc:attributionName">Herman Melville</span> is licensed under a <a rel="license" href="https://creativecommons.org/licenses/by/4.0/">Creative Commons Attribution 4.0 International License</a>, except where otherwise noted.</p></div>' );
	}

	public function test_getUrlForLicense() {
		$result = $this->licensing->getUrlForLicense( 'public-domain' );
		$this->assertEquals( $result, 'https://creativecommons.org/publicdomain/mark/1.0/' );
	}

	public function test_getLicenseFromUrl() {
		$result = $this->licensing->getLicenseFromUrl( 'https://creativecommons.org/publicdomain/mark/1.0/' );
		$this->assertEquals( $result, 'public-domain' );
	}

	public function test_getNameForLicense() {
		$result = $this->licensing->getNameForLicense( 'public-domain' );
		$this->assertEquals( $result, 'Public Domain' );
		$result = $this->licensing->getNameForLicense( 'cc-by-nc-sa' );
		$this->assertEquals( $result, 'Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International License' );
		$result = $this->licensing->getNameForLicense( 'made-up-license' );
		$this->assertEquals( $result, 'All Rights Reserved' );
	}
}
