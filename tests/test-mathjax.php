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

	public function testSingleDollarOptionDefault() {
		$options = $this->mathjax->getOptions();
		$this->assertFalse( $options['use_single_dollar'] );
	}

	public function testSingleDollarOptionSave() {
		$_POST = [
			'pb-mathjax-nonce'  => wp_create_nonce( 'save' ),
			'fg'                => '000000',
			'use_single_dollar' => '1',
		];
		$this->mathjax->saveOptions();
		$options = $this->mathjax->getOptions();
		$this->assertTrue( $options['use_single_dollar'] );
	}

	public function testSingleDollarOptionSaveUnchecked() {
		// First enable it
		$_POST = [
			'pb-mathjax-nonce'  => wp_create_nonce( 'save' ),
			'fg'                => '000000',
			'use_single_dollar' => '1',
		];
		$this->mathjax->saveOptions();

		// Then save without the checkbox (unchecked = absent from POST)
		$_POST = [
			'pb-mathjax-nonce' => wp_create_nonce( 'save' ),
			'fg'               => '000000',
		];
		$this->mathjax->saveOptions();
		$options = $this->mathjax->getOptions();
		$this->assertFalse( $options['use_single_dollar'] );
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
		$this->mathjax->addScripts();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'window.MathJax', $buffer );
		$result = has_filter('the_content', 'wptexturize');
		$this->assertFalse(
			$result,
			'wptexturize should be removed from the_content filter'
		);
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

	public function testSectionHasMathSingleDollarEnabled() {
		// Enable the option
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

		$new_post = [
			'post_title'   => 'Test Chapter: ' . wp_rand(),
			'post_type'    => 'chapter',
			'post_status'  => 'published',
			'post_content' => 'Euler\'s formula $e^{i\pi}+1=0$ is beautiful.',
		];
		$pid             = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = get_post( $pid );
		$this->assertTrue( $this->mathjax->sectionHasMath() );
	}

	public function testSectionHasMathSingleDollarDisabled() {
		// Explicitly disable the option (default)
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => false ] );

		$new_post = [
			'post_title'   => 'Test Chapter: ' . wp_rand(),
			'post_type'    => 'chapter',
			'post_status'  => 'published',
			'post_content' => 'Euler\'s formula $e^{i\pi}+1=0$ is beautiful.',
		];
		$pid             = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = get_post( $pid );
		$this->assertFalse( $this->mathjax->sectionHasMath() );
	}

	public function testSectionHasMathCurrencyNotMath() {
		// Enable the option — currency must NOT trigger detection
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

		$new_post = [
			'post_title'   => 'Test Chapter: ' . wp_rand(),
			'post_type'    => 'chapter',
			'post_status'  => 'published',
			'post_content' => 'We earned $ 5,000 today and $ 4,000 yesterday.',
		];
		$pid             = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = get_post( $pid );
		$this->assertFalse( $this->mathjax->sectionHasMath() );
	}

	public function testAddHeadersSingleDollarEnabled() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

		$new_post = [
			'post_title'   => 'Test Chapter: ' . wp_rand(),
			'post_type'    => 'chapter',
			'post_status'  => 'published',
			'post_content' => '$e^{i\pi}+1=0$',
		];
		$pid             = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = get_post( $pid );

		ob_start();
		$this->mathjax->addHeaders();
		$buffer = ob_get_clean();

		// Single-dollar is handled server-side by replaceSingleDollarDelimiters(),
		// NOT by adding ['$','$'] to MathJax's inlineMath config.
		$this->assertStringNotContainsString( "['$','$']", $buffer );
		$this->assertStringContainsString( "['\\\\(', '\\\\)']", $buffer );
	}

	public function testAddHeadersSingleDollarDisabled() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => false ] );

		$new_post = [
			'post_title'   => 'Test Chapter: ' . wp_rand(),
			'post_type'    => 'chapter',
			'post_status'  => 'published',
			'post_content' => '[latex]x^2[/latex]',
		];
		$pid             = $this->factory()->post->create_object( $new_post );
		$GLOBALS['post'] = get_post( $pid );

		ob_start();
		$this->mathjax->addHeaders();
		$buffer = ob_get_clean();

		$this->assertStringNotContainsString( "['$','$']", $buffer );
		$this->assertStringContainsString( "['\\\\(', '\\\\)']", $buffer );
	}

	public function testReplaceSingleDollarDelimiters() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

		// Basic inline math
		$result = $this->mathjax->replaceSingleDollarDelimiters( 'Euler formula $e^{i\pi}+1=0$ is beautiful.' );
		$this->assertEquals( 'Euler formula \(e^{i\pi}+1=0\) is beautiful.', $result );
	}

	public function testReplaceSingleDollarDelimitersCurrency() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

		// Currency with space after $ must NOT be converted
		$result = $this->mathjax->replaceSingleDollarDelimiters( 'We earned $ 5,000 today and $ 4,000 yesterday.' );
		$this->assertEquals( 'We earned $ 5,000 today and $ 4,000 yesterday.', $result );
	}

	public function testReplaceSingleDollarDelimitersDisabled() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => false ] );

		// When disabled, content passes through unchanged
		$result = $this->mathjax->replaceSingleDollarDelimiters( '$e^{i\pi}+1=0$' );
		$this->assertEquals( '$e^{i\pi}+1=0$', $result );
	}

	public function testReplaceSingleDollarDelimitersEscaped() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

		// Escaped dollar sign must NOT be converted
		$result = $this->mathjax->replaceSingleDollarDelimiters( '\$5,000' );
		$this->assertEquals( '\$5,000', $result );
	}

	public function testReplaceSingleDollarDelimitersDisplayMath() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

		// Double dollar display math must NOT be affected
		$result = $this->mathjax->replaceSingleDollarDelimiters( '$$e^{i\pi}+1=0$$' );
		$this->assertEquals( '$$e^{i\pi}+1=0$$', $result );
	}

	public function testReplaceSingleDollarDelimitersSpacedFormula() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

		// Formula with internal spaces but NOT adjacent to delimiters should work
		$result = $this->mathjax->replaceSingleDollarDelimiters( '$x + y = z$' );
		$this->assertEquals( '\(x + y = z\)', $result );
	}

	public function testReplaceSingleDollarDelimitersMixedContent() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );

		// Mixed: real math + currency in same content
		$result = $this->mathjax->replaceSingleDollarDelimiters(
			'I think $ 5,000 dolares will be enough $ or maybe $4,000$ pesos.'
		);
		// Only $4,000$ should be converted (no space adjacent to delimiters)
		$this->assertEquals(
			'I think $ 5,000 dolares will be enough $ or maybe \(4,000\) pesos.',
			$result
		);
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

	public function testExportDoubleDollarUnaffected() {
		// Double dollar should still produce display math, unaffected by single-dollar setting
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );
		$this->mathjax->usePbMathJax = true;

		$s = $this->mathjax->replaceLatexDelimitersOnExports( '$$e^{i\pi}+1=0$$' );
		$this->assertStringContainsString( 'display-math', $s );
		$this->assertStringStartsWith( '<div class="display-math"><img', $s );
	}

	/**
	 * Integration tests: simulate the export filter chain where
	 * replaceSingleDollarDelimiters() converts $...$ to \(...\)
	 * then replaceLatexDelimitersOnExports() renders \(...\) via pb-mathjax.
	 */
	public function testExportPipelineSingleDollarRendered() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );
		$this->mathjax->usePbMathJax = true;

		$content = '$f(x)$';
		// Step 1: replaceSingleDollarDelimiters converts $...$ to \(...\)
		$content = $this->mathjax->replaceSingleDollarDelimiters( $content );
		// Step 2: replaceLatexDelimitersOnExports renders \(...\) as <img>
		$content = $this->mathjax->replaceLatexDelimitersOnExports( $content );

		$this->assertStringStartsWith( '<img', $content );
		$this->assertStringContainsString( 'class="latex mathjax"', $content );
	}

	public function testExportPipelineCurrencyUntouched() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );
		$this->mathjax->usePbMathJax = true;

		$content = 'I think $ 5,000 dolares will be enough $ or maybe $4,000$ pesos.';
		$content = $this->mathjax->replaceSingleDollarDelimiters( $content );
		$content = $this->mathjax->replaceLatexDelimitersOnExports( $content );

		// Currency with spaces must remain as plain text
		$this->assertStringContainsString( '$ 5,000 dolares will be enough $', $content );
		// $4,000$ should be rendered (no spaces adjacent to delimiters)
		$this->assertStringContainsString( '<img', $content );
		$this->assertStringContainsString( 'pesos.', $content );
	}

	public function testExportPipelineDoubleDollarNotCorrupted() {
		update_option( MathJax::OPTION, [ 'fg' => '000000', 'use_single_dollar' => true ] );
		$this->mathjax->usePbMathJax = true;

		$content = '$$e^{i\pi}+1=0$$';
		$content = $this->mathjax->replaceSingleDollarDelimiters( $content );
		$content = $this->mathjax->replaceLatexDelimitersOnExports( $content );

		// Must produce display-math, not get mangled by single-dollar processing
		$this->assertStringStartsWith( '<div class="display-math"><img', $content );
	}
}
