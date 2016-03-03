<?php

class SassTest extends \WP_UnitTestCase {


  /**
	 * @var \PressBooks\Sass()
	 */
	protected $sass;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->sass = new \PressBooks\Sass();
	}


  /**
   * @covers \PressBooks\Sass\getStringsToLocalize
   */
  public function test_getStringsToLocalize() {

    $result = $this->sass->getStringsToLocalize();

    $this->assertTrue( is_array( $result ) );

    $this->assertArrayHasKey( 'chapter', $result );

    $this->assertEquals( 'chapter',  strtolower( $result['chapter'] ) );

  }

  /**
   * @covers \PressBooks\Sass\prependLocalizedVars
   */
  public function test_prependLocalizedVars() {

    $scss = '/* Silence is golden. */';

    $result = $this->sass->prependLocalizedVars( $scss );

    $this->assertContains( $scss, $result );

    $this->assertContains( "\$chapter: 'Chapter';", $result );

  }

}
