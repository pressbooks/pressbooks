<?php

class CoverGenerator_SkuTest extends \WP_UnitTestCase {


	/**
	 * @var \Pressbooks\Covergenerator\Sku
	 */
	public $sku;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		\Pressbooks\Covergenerator\Covergenerator::commandLineDefaults();
		$this->sku = new \Pressbooks\Covergenerator\Sku();
	}


	public function test_createBarcode() {
		$sku = "1234567890123";
		$url = $this->sku->createBarcode( $sku );
		$this->assertContains( "1234567890123", $url );
		$this->assertEquals( $url, get_option( 'pressbooks_cg_sku' ) );
		$this->assertNotEmpty( \Pressbooks\Image\attachment_id_from_url( $url ) );
	}

}
