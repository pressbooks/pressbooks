<?php

class Modules_Export_MobiTest extends \WP_UnitTestCase {

	use utilsTrait;


	public function setUp() {
		parent::setUp();
		$this->kindlegen = new Pressbooks\Modules\Export\Mobi\Kindlegen( [] );
	}

	public function test_deleteTmpDir() {
		$this->assertTrue( file_exists( $this->kindlegen->getTmpDir() ) );
		$this->kindlegen->deleteTmpDir();
		$this->assertFalse( file_exists( $this->kindlegen->getTmpDir() ) );
	}

}
