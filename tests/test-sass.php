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

	public function test_paths() {

		$this->assertNotEmpty( $this->sass->pathToPartials() );
		$this->assertNotEmpty( $this->sass->pathToGlobals() );
		$this->assertNotEmpty( $this->sass->pathToFonts() );
		$this->assertNotEmpty( $this->sass->pathToUserGeneratedCss() );
		$this->assertNotEmpty( $this->sass->pathToUserGeneratedSass() );
		$this->assertNotEmpty( $this->sass->pathToDebugDir() );
		$this->assertNotEmpty( $this->sass->urlToUserGeneratedCss() );

		$paths = $this->sass->defaultIncludePaths( 'prince' );
		$this->assertTrue( is_array( $paths ) );
		$this->assertNotEmpty( $paths );
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
		$font-size: 
		    14pt;
		$body-font-size: (
			web:   14cm,  
		    epub:  medium,
            prince:10.5pt,
        )   !default;
        $var1: $var2 !default;
        $f: xxx(one, two,  three,    four,     five);
        ignored: becauseKeyHasNoDollarSign;
        ';

		$vars = $this->sass->parseVariables( $scss );

		$this->assertEquals( $vars['red'], '#d4002d' );
		$this->assertEquals( $vars['font-size'], '14pt' );
		$this->assertEquals( $vars['body-font-size'], '(web: 14cm, epub: medium, prince: 10.5pt)' );
		$this->assertEquals( $vars['var1'], '$var2' );
		$this->assertEquals( $vars['f'], 'xxx(one, two, three, four, five)' );
		$this->assertArrayNotHasKey( 'ignored', $vars );
	}


	public function test_compile() {
		$scss = 'p { font-size: $foo }';
		$this->sass->setVariables( [ 'foo' => 999 ] );
		$css = $this->sass->compile( $scss );
		$expected = <<<EOF
p {
  font-size: 999; }
EOF;
		$this->assertEquals( trim( $expected ), trim( $css ) );
	}
}
