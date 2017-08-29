<?php

use Pressbooks\Container;

class SassTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Sass
	 */
	protected $sass;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();
		$this->sass = Container::get( 'Sass' );
	}


	public function test_getStringsToLocalize() {

		$result = $this->sass->getStringsToLocalize();

		$this->assertTrue( is_array( $result ) );

		$this->assertArrayHasKey( 'chapter', $result );

		$this->assertEquals( 'chapter', strtolower( $result['chapter'] ) );

	}

	public function test_prependLocalizedVars() {

		$scss = '/* Silence is golden. */';

		$result = $this->sass->prependLocalizedVars( $scss );

		$this->assertContains( $scss, $result );

		$this->assertContains( "\$chapter: 'Chapter';", $result );

	}

	public function test_parseVariables() {
		$scss = '$red: #d4002d !default;
		$font-size: 14pt;';

		$vars = $this->sass->parseVariables( $scss );

		$this->assertArrayHasKey( 'red', $vars );
		$this->assertArrayHasKey( 'font-size', $vars );
		$this->assertEquals( $vars['red'], '#d4002d' );
		$this->assertEquals( $vars['font-size'], '14pt' );
	}
}
