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
	public function set_up() {
		parent::set_up();
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
		$this->assertStringContainsString( '<h1>MathJax</h1>', $buffer );
		$this->assertStringContainsString( '<input type="hidden" id="pb-mathjax-nonce"', $buffer );
	}

	public function test_options() {
		$options = $this->mathjax->getOptions();
		$this->assertEquals( $options['fg'], '000000' );

		$_POST = [
			'pb-mathjax-nonce' => wp_create_nonce( 'save' ),
			'fg' => 'ff0000',
		];
		$this->mathjax->saveOptions();
		$options = $this->mathjax->getOptions();
		$this->assertEquals( $options['fg'], 'ff0000' );

		// No junk allowed
		$_POST = [
			'pb-mathjax-nonce' => wp_create_nonce( 'save' ),
			'fg' => 'zzzzzz',
		];
		$this->mathjax->saveOptions();
		$options = $this->mathjax->getOptions();
		$this->assertEquals( $options['fg'], '000000' );
	}

	public function test_sectionHasMath() {
		$new_post = [
			'post_title' => 'Test Chapter: ' . rand(),
			'post_type' => 'chapter',
			'post_status' => 'published',
			'post_content' => 'No math',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = $pid;
		$this->assertFalse( $this->mathjax->sectionHasMath());

		$new_post = [
			'post_title' => 'Test Chapter: ' . rand(),
			'post_type' => 'chapter',
			'post_status' => 'published',
			'post_content' => '[latex]\boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}[/latex]',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = $pid;
		$this->assertTrue( $this->mathjax->sectionHasMath());

	}

	public function test_addHeaders() {
		$new_post = [
			'post_title' => 'Test Chapter: ' . rand(),
			'post_type' => 'chapter',
			'post_status' => 'published',
			'post_content' => 'No math',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = $pid;
		ob_start();
		$this->mathjax->addHeaders();
		$buffer = ob_get_clean();
		$this->assertEmpty( $buffer );


		$new_post = [
			'post_title' => 'Test Chapter: ' . rand(),
			'post_type' => 'chapter',
			'post_status' => 'published',
			'post_content' => '[latex]\boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}[/latex]',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = $pid;
		ob_start();
		$this->mathjax->addHeaders();
		$buffer = ob_get_clean();
		$this->assertStringContainsString('window.MathJax', $buffer);
	}

	public function test_latexMarkup() {
		$this->mathjax->usePbMathJax = false;
		$s = $this->mathjax->parseLatexMarkup( '$latex \boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}$' );
		$this->assertEquals( '[latex]\boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}[/latex]', $s );

		$this->mathjax->usePbMathJax = true;
		$s = $this->mathjax->parseLatexMarkup( '$latex \boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}$' );
		$this->assertStringStartsWith( '<img src="http://localhost:3000/latex?latex=%5Cboldsymbol%7B%5Cfrac%7Bm_%7B%5Ctextbf%7Bdrop%7D%7Dgd%7D%7BV%7D%7D', $s );

		$s = $this->mathjax->parseLatexMarkup( 'latex not found$' );
		$this->assertEquals( 'latex not found$', $s );
	}

	public function test_mathJaxDelimiters() {
		$this->mathjax->usePbMathJax = false;
		//This should not be converted unless is an export
		$s = $this->mathjax->parseLatexMarkup( '\( e^{i \pi} + 1 = 0 \)' );
		$this->assertEquals( '\( e^{i \pi} + 1 = 0 \)', $s );

		$this->mathjax->usePbMathJax = true;
		$s = $this->mathjax->replaceLatexDelimitersOnExports( '\( e^{i \pi} + 1 = 0 \)' );
		$this->assertStringStartsWith( '<img src="http://localhost:3000/latex?latex=e%5E%7Bi%20%5Cpi%7D%20%2B%201%20%3D%200&#038;fg=000000', $s );

		$s = $this->mathjax->replaceLatexDelimitersOnExports( '\[ e^{i \pi} + 1 = 0 \]' );
		$this->assertStringStartsWith( '<img src="http://localhost:3000/latex?latex=e%5E%7Bi%20%5Cpi%7D%20%2B%201%20%3D%200&#038;fg=000000', $s );

		$this->mathjax->usePbMathJax = false;
		$s = $this->mathjax->parseLatexMarkup( '\[ e^{i \pi} + 1 = 0 \]' );
		$this->assertEquals( '\[ e^{i \pi} + 1 = 0 \]', $s );


	}

	public function test_asciiMathMarkup() {
		$this->mathjax->usePbMathJax = false;
		$s = $this->mathjax->parseAsciiMathMarkup( '$asciimath \boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}$' );
		$this->assertEquals( '[asciimath]\boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}[/asciimath]', $s );

		$this->mathjax->usePbMathJax = true;
		$s = $this->mathjax->parseAsciiMathMarkup( '$asciimath \boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}$' );
		$this->assertStringStartsWith( '<img src="http://localhost:3000/asciimath?asciimath=%5Cboldsymbol%7B%5Cfrac%7Bm_%7B%5Ctextbf%7Bdrop%7D%7Dgd%7D%7BV%7D%7D', $s );

		$s = $this->mathjax->parseAsciiMathMarkup( 'asciimath not found$' );
		$this->assertEquals( 'asciimath not found$', $s );
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
		$this->assertStringContainsString( 'math[', $options['extended_valid_elements'] );
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
		$this->assertStringContainsString( '<img src="http://localhost:3000/mathml', $content );
	}
}
