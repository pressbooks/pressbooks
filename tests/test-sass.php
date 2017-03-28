<?php

class SassTest extends \WP_UnitTestCase {


  /**
	 * @var \Pressbooks\Sass()
	 */
	protected $sass;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->sass = new \Pressbooks\Sass();
	}


  /**
   * @covers \Pressbooks\Sass::getStringsToLocalize
   */
  public function test_getStringsToLocalize() {

    $result = $this->sass->getStringsToLocalize();

    $this->assertTrue( is_array( $result ) );

    $this->assertArrayHasKey( 'chapter', $result );

    $this->assertEquals( 'chapter',  strtolower( $result['chapter'] ) );

  }

  /**
   * @covers \Pressbooks\Sass::prependLocalizedVars
   */
  public function test_prependLocalizedVars() {

    $scss = '/* Silence is golden. */';

    $result = $this->sass->prependLocalizedVars( $scss );

    $this->assertContains( $scss, $result );

    $this->assertContains( "\$chapter: 'Chapter';", $result );

  }

	/**
	 * @covers \Pressbooks\Sass::parseVariables
	 */
	public function test_parseVariables() {
		$scss = "\$red: #d4002d !default;\n
		\$font-size: 14pt;";

		$vars = $this->sass->parseVariables( $scss );
		// $this->assertArrayHasKey( 'red', $vars );
		// $this->assertArrayHasKey( 'font-size', $vars );
		// $this->assertEquals( $vars['red'], '#d4002d' );
		// $this->assertEquals( $vars['font-size'], '14pt' );
	}

	/**
	 * @covers \Pressbooks\Sass::applyOverrides
	 */
	public function test_applyOverrides() {
		$result = $this->sass->applyOverrides( '// SCSS.', '// Override.' );
		$this->assertTrue( strpos( $result, '// SCSS.' ) === 0 );
	}
}
