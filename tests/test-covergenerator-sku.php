<?php

class CoverGenerator_SkuTest extends \WP_UnitTestCase {


	/**
	 * @var \Pressbooks\Covergenerator\Sku
	 * @group covergenerator
	 */
	public $sku;

	/**
	 * @group covergenerator
	 */
	public function set_up() {
		parent::set_up();
		\Pressbooks\Covergenerator\Covergenerator::commandLineDefaults();
		$this->sku = new \Pressbooks\Covergenerator\Sku();
	}

	/**
	 * @group covergenerator
	 */
	public function test_createBarcode() {
		$sku = "1234567890123";
		$url = $this->sku->createBarcode( $sku );
		$this->assertStringContainsString( "1234567890123", $url );
		$this->assertEquals( $url, get_option( 'pressbooks_cg_sku' ) );
		$this->assertNotEmpty( \Pressbooks\Image\attachment_id_from_url( $url ) );
	}

}
