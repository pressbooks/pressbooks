<?php

class CoverGenerator_SpineTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Covergenerator\Spine
	 */
	public $spine;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		\Pressbooks\Covergenerator\Covergenerator::commandLineDefaults();
		$this->spine = new \Pressbooks\Covergenerator\Spine();
	}

	/**
	 *  [ $pages, $ppi, $expected ]
	 *
	 * @return array
	 */
	public function spineWidthCalculatorProvider() {

		return [
			[ 50, 400, 0.125 ],
			[ 237, 434, 0.5461 ],
			[ 362, 530, 0.683 ],
			[ 1000, 512, 1.9531 ],
			[ 5000, 448, 11.1607 ],
		];
	}

	/**
	 * @dataProvider spineWidthCalculatorProvider
	 *
	 * @param int $pages
	 * @param int $ppi
	 * @param float $expected Inches
	 */
	public function test_spineWidthCalculator( $pages, $ppi, $expected ) {

		$this->assertEquals( $expected, $this->spine->spineWidthCalculator( $pages, $ppi ) );
		$this->assertNotEquals( $expected + 1, $this->spine->spineWidthCalculator( $pages, $ppi ) );
	}


	/**
	 *  [ $pages, $caliper, $expected ]
	 *
	 * @return array
	 */
	public function spineWidthCalculatorCaliperProvider() {
		return [
			[ 50, 0.005, 0.125 ],
			[ 237, 0.0046, 0.5448 ],
			[ 362, 0.0038, 0.6882 ],
			[ 1000, 0.0039, 1.9493 ],
			[ 5000, 0.0045, 11.2613 ],
		];
	}

	/**
	 * @dataProvider spineWidthCalculatorCaliperProvider
	 *
	 * @param int $pages
	 * @param float $caliper
	 * @param float $expected Inches
	 */
	public function test_spineWidthCalculatorCaliper( $pages, $caliper, $expected ) {

		$this->assertEquals( $expected, $this->spine->spineWidthCalculatorCaliper( $pages, $caliper ) );
		$this->assertNotEquals( $expected + 1, $this->spine->spineWidthCalculatorCaliper( $pages, $caliper ) );
	}


	/**
	 *  [ $caliper, $expected ]
	 *
	 * @return array
	 */
	public function caliperToPpiProvider() {

		return [
			[ 0.002, 1000 ],
			[ 0.00225, 889 ],
			[ 0.0025, 800 ],
			[ 0.00275, 727 ],
			[ 0.003, 667 ],
			[ 0.00325, 615 ],
			[ 0.0035, 571 ],
			[ 0.00375, 533 ],
			[ 0.004, 500 ],
			[ 0.00425, 471 ],
			[ 0.0045, 444 ],
			[ 0.00475, 421 ],
			[ 0.005, 400 ],
			[ 0.00525, 381 ],
			[ 0.0055, 364 ],
			[ 0.00575, 348 ],
			[ 0.006, 333 ],
			[ 0.00625, 320 ],
			[ 0.0065, 308 ],
			[ 0.00675, 296 ],
			[ 0.007, 286 ],
		];
	}


	/**
	 * @dataProvider caliperToPpiProvider
	 *
	 * @param float $caliper
	 * @param float $expected Inches
	 */
	public function test_caliperToPpi( $caliper, $expected ) {

		$this->assertEquals( $expected, $this->spine->caliperToPpi( $caliper ) );
		$this->assertNotEquals( $expected + 1, $this->spine->caliperToPpi( $caliper ) );
	}

	function test_countPagesInMostRecentPdf() {
		$dest = \Pressbooks\Modules\Export\Export::getExportFolder() . 'test.pdf';
		copy( __DIR__ . '/data/test.pdf', $dest );
		$pages = $this->spine->countPagesInMostRecentPdf();
		$this->assertEquals( 11, $pages );
		unlink( $dest );
	}


	/**
	 *
	 */
	public function test_countPagesInPdf() {

		$pages = $this->spine->countPagesInPdf( __DIR__ . '/data/test.pdf' );
		$this->assertEquals( 11, $pages );

		try {
			$pages = $this->spine->countPagesInPdf( '/tmp/file/does/not/exist' );
		} catch ( \Exception $e ) {
			$this->assertTrue( true );
			return;
		}
		$this->fail();
	}

}
