<?php

use Pressbooks\MathJax;
/**
 * @group taxonomies
 */
class MathjaxTest extends \WP_UnitTestCase {

	protected $mathjax;
	public function set_up() {
		parent::set_up();
		$this->mathjax = new MathJax();
	}

	public function testBeforeExport() {
		$this->assertFalse( $this->mathjax->usePbMathJax );
		$this->mathjax->beforeExport();
		$this->assertTrue( $this->mathjax->usePbMathJax );
	}

	public function testAddMenu() {
		$this->mathjax->addMenu();
		$this->assertTrue( true ); // Did not crash
	}

	public function testRenderPage() {
		ob_start();
		$this->mathjax->renderPage();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( '<h1>MathJax</h1>', $buffer );
		$this->assertStringContainsString( '<input type="hidden" id="pb-mathjax-nonce"', $buffer );
	}

	public function testOptions() {
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

	public function testSectionHasMath() {
		$new_post = [
			'post_title' => 'Test Chapter: ' . wp_rand(),
			'post_type' => 'chapter',
			'post_status' => 'published',
			'post_content' => 'No math',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = $pid;
		$this->assertFalse( $this->mathjax->sectionHasMath() );

		$new_post = [
			'post_title' => 'Test Chapter: ' . wp_rand(),
			'post_type' => 'chapter',
			'post_status' => 'published',
			'post_content' => '[latex]\boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}[/latex]',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = $pid;
		$this->assertTrue( $this->mathjax->sectionHasMath() );

	}

	public function testAddHeaders() {
		$new_post = [
			'post_title' => 'Test Chapter: ' . wp_rand(),
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
			'post_title' => 'Test Chapter: ' . wp_rand(),
			'post_type' => 'chapter',
			'post_status' => 'published',
			'post_content' => '[latex]\boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}[/latex]',
		];
		$pid = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = $pid;
		ob_start();
		$this->mathjax->addHeaders();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'window.MathJax', $buffer );
	}

	public function testLatexMarkup() {
		$this->mathjax->usePbMathJax = false;
		$s = $this->mathjax->parseLatexMarkup( '$latex \boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}$' );
		$this->assertEquals( '[latex]\boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}[/latex]', $s );

		$this->mathjax->usePbMathJax = true;
		$s = $this->mathjax->parseLatexMarkup( '$latex \boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}$' );
		$this->assertStringStartsWith( '<img src="http://localhost:3000/latex?latex=XGJvbGRzeW1ib2x7XGZyYWN7bV97XHRleHRiZntkcm9wfX1nZH17Vn19&', $s );

		$s = $this->mathjax->parseLatexMarkup( 'latex not found$' );
		$this->assertEquals( 'latex not found$', $s );
	}

	public function testMathJaxDelimiters() {
		$this->mathjax->usePbMathJax = false;
		//This should not be converted unless is an export
		$s = $this->mathjax->parseLatexMarkup( '\( e^{i \pi} + 1 = 0 \)' );
		$this->assertEquals( '\( e^{i \pi} + 1 = 0 \)', $s );

		$this->mathjax->usePbMathJax = true;
		$s = $this->mathjax->replaceLatexDelimitersOnExports( '\( e^{i \pi} + 1 = 0 \)' );
		$this->assertStringStartsWith( '<img src="http://localhost:3000/latex?latex=ZV57aSBccGl9ICsgMSA9IDA', $s );

		$s = $this->mathjax->replaceLatexDelimitersOnExports( '\[ e^{i \pi} + 1 = 0 \]' );
		$this->assertStringStartsWith( '<div class="display-math"><img src="http://localhost:3000/latex?latex=ZV57aSBccGl9ICsgMSA9IDA&fg=000000', $s );

		$this->mathjax->usePbMathJax = false;
		$s = $this->mathjax->parseLatexMarkup( '\[ e^{i \pi} + 1 = 0 \]' );
		$this->assertEquals( '\[ e^{i \pi} + 1 = 0 \]', $s );
	}

	public function testAsciiMathMarkup() {
		$this->mathjax->usePbMathJax = false;
		$s = $this->mathjax->parseAsciiMathMarkup( '$asciimath \boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}$' );
		$this->assertEquals( '[asciimath]\boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}[/asciimath]', $s );

		$this->mathjax->usePbMathJax = true;
		$s = $this->mathjax->parseAsciiMathMarkup( '$asciimath \boldsymbol{\frac{m_{\textbf{drop}}gd}{V}}$' );
		$this->assertStringStartsWith( '<img src="http://localhost:3000/asciimath?asciimath=XGJvbGRzeW1ib2x7XGZyYWN7bV97XHRleHRiZntkcm9wfX1nZH17Vn19', $s );

		$s = $this->mathjax->parseAsciiMathMarkup( 'asciimath not found$' );
		$this->assertEquals( 'asciimath not found$', $s );
	}

	public function testMathmlTags() {
		$tags = $this->mathjax->mathmlTags();
		$this->assertArrayHasKey( 'math', $tags );
		$this->assertTrue( in_array( 'display', $tags['math'], true ) );
		$this->assertArrayHasKey( 'csymbol', $tags );
		$this->assertTrue( in_array( 'type', $tags['csymbol'], true ) );
	}

	public function testAllowMathmlTags() {
		global $allowedposttags;
		$old_allowedposttags = $allowedposttags;
		$allowedposttags = [];

		$this->mathjax->allowMathmlTags();
		$this->assertArrayHasKey( 'math', $allowedposttags );

		// Put back to the way it was
		$allowedposttags = $old_allowedposttags;
	}

	public function testAllowMathmlTagsInTinyMce() {
		$options = $this->mathjax->allowMathmlTagsInTinyMce( [] );
		$this->assertStringContainsString( 'math[', $options['extended_valid_elements'] );
	}

	public function testFilterLineBreakTagsInMthml() {
		$mathml_content = '<math><br><p>...</p></math>';
		$content = $this->mathjax->filterLineBreakTagsInMthml( $mathml_content );
		$this->assertEquals( '<math>...</math>', $content );
	}

	public function testFilterLineBreakTagsInSvg() {
		$mathml_content = '<svg><br><p>...</p></svg>';
		$content = $this->mathjax->filterLineBreakTagsInSvg( $mathml_content );
		$this->assertEquals( '<svg>...</svg>', $content );
	}

	public function testReplaceMathML() {
		$mathml_content = '<math><mrow><mrow><msup><mi>x</mi><mn>2</mn></msup><mo>+</mo><mrow><mn>4</mn><mo>&InvisibleTimes;</mo><mi>x</mi></mrow><mo>+</mo><mn>4</mn></mrow><mo>=</mo><mn>0</mn></mrow></math>';

		$this->mathjax->usePbMathJax = false;
		$content = $this->mathjax->replaceMathML( $mathml_content );
		$this->assertEmpty( $content );

		$this->mathjax->usePbMathJax = true;
		$content = $this->mathjax->replaceMathML( $mathml_content );
		$this->assertStringContainsString( 'http://localhost:3000/mathml', $content );
	}
}
