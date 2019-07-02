<?php

use Pressbooks\MathJax;

class MathJaxTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var MathJax
	 */
	protected $mathjax;

	/**
	 * @group taxonomies
	 */
	public function setUp() {
		parent::setUp();
		$this->mathjax = new Mathjax();
	}


	function test_beforeExport() {
		$this->assertFalse( $this->mathjax->usePbMathJax );
		$this->mathjax->beforeExport();
		$this->assertTrue( $this->mathjax->usePbMathJax );
	}

	public function test_addMenu() {
		$this->mathjax->addMenu();
		$this->assertTrue( true ); // Did not crash
	}

	public function test_renderPage() {
		ob_start();
		$this->mathjax->renderPage();
		$buffer = ob_get_clean();
		$this->assertContains( '<h1>MathJax</h1>', $buffer );
		$this->assertContains( '<input type="hidden" id="pb-mathjax-nonce"', $buffer );
	}

	public function test_options() {
		$options = $this->mathjax->getOptions();
		$this->assertEquals( $options['fg'], '000000' );
		$this->assertEquals( $options['font'], 'TeX' );

		$_POST = [
			'pb-mathjax-nonce' => wp_create_nonce( 'save' ),
			'fg' => 'ff0000',
			'font' => 'Asana-Math',
		];
		$this->mathjax->saveOptions();
		$options = $this->mathjax->getOptions();
		$this->assertEquals( $options['fg'], 'ff0000' );
		$this->assertEquals( $options['font'], 'Asana-Math' );

		// No junk allowed
		$_POST = [
			'pb-mathjax-nonce' => wp_create_nonce( 'save' ),
			'fg' => 'zzzzzz',
			'font' => 'FAKE-FONT',
		];
		$this->mathjax->saveOptions();
		$options = $this->mathjax->getOptions();
		$this->assertEquals( $options['fg'], '000000' );
		$this->assertEquals( $options['font'], 'TeX' );
	}

	public function test_sectionHasMath() {

	}

	public function test_addScripts() {

	}

	public function test_addHeaders() {

	}

	public function test_dollarSignLatexMarkup() {

	}

	public function test_latexEntityDecode() {

	}

	public function test_latexRender() {

	}

	public function test_latexShortcode() {

	}

	public function test_dollarSignAsciiMathMarkup() {

	}

	public function test_asciiMathEntityDecode() {

	}

	public function test_asciiMathRender() {

	}

	public function test_asciiMathShortcode() {

	}

	public function test_mathmlTags() {
		$tags = $this->mathjax->mathmlTags();
		$this->assertArrayHasKey( 'math', $tags );
		$this->assertTrue( in_array( 'display', $tags['math'], true ) );
		$this->assertArrayHasKey( 'csymbol', $tags );
		$this->assertTrue( in_array( 'type', $tags['csymbol'], true ) );
	}

	public function test_allowMathmlTags() {
		global $allowedposttags;
		$old_allowedposttags = $allowedposttags;
		$allowedposttags = [];

		$this->mathjax->allowMathmlTags();
		$this->assertArrayHasKey( 'math', $allowedposttags );

		// Put back to the way it was
		$allowedposttags = $old_allowedposttags;

	}

	public function test_allowMathmlTagsInTinyMce() {
		$options = $this->mathjax->allowMathmlTagsInTinyMce( [] );
		$this->assertContains( 'math[', $options['extended_valid_elements'] );
	}

	public function test_filterLineBreakTagsInMthml() {
		$mathml_content = '<math><br><p>...</p></math>';
		$content = $this->mathjax->filterLineBreakTagsInMthml( $mathml_content );
		$this->assertEquals( '<math>...</math>', $content );
	}

	public function test_filterLineBreakTagsInSvg() {
		$mathml_content = '<svg><br><p>...</p></svg>';
		$content = $this->mathjax->filterLineBreakTagsInSvg( $mathml_content );
		$this->assertEquals( '<svg>...</svg>', $content );
	}

	public function test_replaceMathML() {
		$mathml_content = '<math><mrow><mrow><msup><mi>x</mi><mn>2</mn></msup><mo>+</mo><mrow><mn>4</mn><mo>&InvisibleTimes;</mo><mi>x</mi></mrow><mo>+</mo><mn>4</mn></mrow><mo>=</mo><mn>0</mn></mrow></math>';

		$this->mathjax->usePbMathJax = false;
		$content = $this->mathjax->replaceMathML( $mathml_content );
		$this->assertEmpty( $content );

		$this->mathjax->usePbMathJax = true;
		$content = $this->mathjax->replaceMathML( $mathml_content );
		$this->assertContains( '<img src="http://localhost:3000/mathml', $content );
	}

}
