<?php

class Shortcodes_Glossary extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Shortcodes\Glossary\Glossary
	 * @group glossary
	 */
	protected $gl;

	/**
	 * @group glossary
	 */
	public function setUp() {
		parent::setUp();

		$this->gl = $this->getMockBuilder( '\Pressbooks\Shortcodes\Glossary\Glossary' )
			->setMethods( null )
			->disableOriginalConstructor()
			->getMock();

		// Add the filter that tests no HTML ever gets saved
		add_filter( 'wp_insert_post_data', [ $this->gl, 'sanitizeGlossaryTerm' ] );

		$this->_createGlossaryTerms();
		$this->gl->getGlossaryTerms( true ); // Reset cache
	}
	/**
	 * @group glossary
	 */
	private function _createGlossaryTerms() {
		$args1 = [
			'post_type'    => 'glossary',
			'post_title'   => 'Support Vector Machine',
			'post_content' => 'An <i>algorithm</i> that uses a nonlinear mapping to transform the original training data into a higher dimension',
			'post_status'  => 'publish',
		];
		$args2 = [
			'post_type'    => 'glossary',
			'post_title'   => 'Neural Network',
			'post_content' => 'A computer system modeled on the <b>human brain</b> and <a href="https://en.wikipedia.org/wiki/Nervous_system" target="_blank">nervous system</a>.',
			'post_status'  => 'publish',
		];
		$args3 = [
			'post_type'    => 'glossary',
			'post_title'   => 'Not done',
			'post_content' => 'This term is not done so the status is private.',
			'post_status'  => 'private',
		];
		$args4 = [
			'post_type'    => 'glossary',
			'post_title'   => 'Machine Learning (ML)',
			'post_content' => 'Machine learning is a method of data analysis that automates analytical model building',
			'post_status'  => 'publish',
		];

		$p1 = $this->factory()->post->create_object( $args1 );
		wp_set_object_terms( $p1, 'definitions', 'glossary-type' );
		$p2 = $this->factory()->post->create_object( $args2 );
		wp_set_object_terms( $p2, [ 'something', 'else' ], 'glossary-type' );
		$p3 = $this->factory()->post->create_object( $args3 );
		wp_set_object_terms( $p3, 'definitions', 'glossary-type' );
		$p4 = $this->factory()->post->create_object( $args4 );
		wp_set_object_terms( $p4, 'id-test', 'glossary-type' );
	}
	/**
	 * @group glossary
	 */
	private function _createGlossaryPost() {

		$args = [
			'post_type'    => 'glossary',
			'post_title'   => 'PHP',
			'post_content' => 'A "popular" general-purpose <script>scripting</script> language that is <em>especially</em> suited to web development.',
			'post_status'  => 'publish',
		];
		$pid  = $this->factory()->post->create_object( $args );

		return $pid;
	}
	/**
	 * @group glossary
	 */
	public function test_getInstance() {

		$val = $this->gl->init();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Glossary\Glossary );

		global $shortcode_tags;

		$this->assertArrayHasKey( 'pb_glossary', $shortcode_tags );

	}
	/**
	 * @group glossary
	 */
	public function test_glossaryTerms() {
		// assures alphabetical listing and format
		$dl = $this->gl->glossaryTerms();
		$this->assertEquals( '<dl data-type="glossary"><dt data-type="glossterm"><dfn id="dfn-machine-learning-ml">Machine Learning (ML)</dfn></dt><dd data-type="glossdef"><p>Machine learning is a method of data analysis that automates analytical model building</p>
</dd><dt data-type="glossterm"><dfn id="dfn-neural-network">Neural Network</dfn></dt><dd data-type="glossdef"><p>A computer system modeled on the human brain and <a href="https://en.wikipedia.org/wiki/Nervous_system" target="_blank">nervous system</a>.</p>
</dd><dt data-type="glossterm"><dfn id="dfn-support-vector-machine">Support Vector Machine</dfn></dt><dd data-type="glossdef"><p>An algorithm that uses a nonlinear mapping to transform the original training data into a higher dimension</p>
</dd></dl>'
		, $dl );
		// assures found by type
		$dl = $this->gl->glossaryTerms( 'definitions' );
		$this->assertEquals( '<dl data-type="glossary"><dt data-type="glossterm"><dfn id="dfn-support-vector-machine">Support Vector Machine</dfn></dt><dd data-type="glossdef"><p>An algorithm that uses a nonlinear mapping to transform the original training data into a higher dimension</p>
</dd></dl>', $dl );
		// assures empty (because this type is not found)
		$dl = $this->gl->glossaryTerms( 'nothing-to-find' );
		$this->assertEmpty( $dl );
		// // Assures 'id' is sanitized to pass checks
		$dl = $this->gl->glossaryTerms( 'id-test' );
		$this->assertEquals( '<dl data-type="glossary"><dt data-type="glossterm"><dfn id="dfn-machine-learning-ml">Machine Learning (ML)</dfn></dt><dd data-type="glossdef"><p>Machine learning is a method of data analysis that automates analytical model building</p>
</dd></dl>', $dl );
	}
	/**
	 * @group glossary
	 */
	public function test_glossaryTooltip() {
		global $id;
		$id = 42; // Fake it!
		$pid = $this->_createGlossaryPost();
		$result = $this->gl->glossaryTooltip( $pid, 'PHP' );
		$this->assertEquals( '<button class="glossary-term" aria-describedby="42-' . $pid . '">PHP</button>', $result );

		$this->factory()->post->update_object( $pid, [ 'post_status' => 'trash' ] );
		$result = $this->gl->glossaryTooltip( $pid, 'PHP' );
		$this->assertEquals( 'PHP', $result );
	}
	/**
	 * @group glossary
	 */
	public function test_getGlossaryTerms() {
		$terms = $this->gl->getGlossaryTerms();
		$this->assertEquals( 4, count( $terms ) );
		$this->assertEquals( 'A computer system modeled on the human brain and <a href="https://en.wikipedia.org/wiki/Nervous_system" target="_blank">nervous system</a>.', $terms['Neural Network']['content'] );
		$this->assertEquals( 'else,something', $terms['Neural Network']['type'] );
		$this->assertEquals( 'publish', $terms['Neural Network']['status'] );

		// Test cache (and cache reset)
		$args = [
			'post_type' => 'glossary',
			'post_title' => 'Cache Test',
			'post_content' => 'Cache Test',
			'post_status' => 'private',
		];
		$this->factory()->post->create_object( $args );
		$terms = $this->gl->getGlossaryTerms();
		$this->assertArrayNotHasKey( 'Cache Test', $terms );
		$terms = $this->gl->getGlossaryTerms( true );
		$this->assertArrayHasKey( 'Cache Test', $terms );
		$this->assertEquals( 'private', $terms['Cache Test']['status'] );
	}

	/**
	 * @group glossary
	 */
	public function test_tooltipContent() {
		$terms = $this->gl->getGlossaryTerms();

		global $id;
		$id = 1;

		$definitions = [];

		// First, add some terms
		foreach ( $terms as $term ) {
			$_ = $this->gl->webShortCodeHandler( [ 'id' => $term['id'] ], 'First.' );
			$definitions[] = $term['content'];
		}

		$content = $this->gl->tooltipContent( 'Hello World' );
		$this->assertContains( '<div class="glossary">', $content );
		$this->assertContains( $definitions[0], $content );
		$this->assertContains( $definitions[1], $content );

		// Initialize shortcode hooks
		$this->_book();
		\Pressbooks\Shortcodes\Complex\Complex::init();

		// Testing glossary to support shortcodes
		$content_with_shortcode = 'Evolutionary algorithms are a heuristic-based approach to solving problems that cannot be easily solved in polynomial time. [media src="https://www.youtube.com/watch?v=L--IxUH4fac" caption="Introduction to evolutionary algorithms" /]';
		$content_with_shortcode = do_shortcode($content_with_shortcode);

		// Testing media caption
		$this->assertContains( '<figure', $content_with_shortcode );
		$this->assertContains( 'Evolutionary algorithms are a heuristic-based approach to solving problems that cannot be easily solved in polynomial time.', $content_with_shortcode );
		$this->assertContains( '<iframe', $content_with_shortcode );
		$this->assertContains( 'L--IxUH4fac', $content_with_shortcode );
		$this->assertContains( '<figcaption', $content_with_shortcode );
		$this->assertContains( 'Introduction to evolutionary algorithms', $content_with_shortcode );

	}
	/**
	 * @group glossary
	 */
	public function test_sanitizeGlossaryTerm() {
		$data['post_type'] = 'imaginary-post-type';
		$data['post_content'] = '<a href="https://google.com" onclick="event.preventDefault(); alert(\'evil!\');">All</a> is <strong>good.</strong>';
		$results = $this->gl->sanitizeGlossaryTerm( $data );
		$this->assertEquals( $data['post_content'], $results['post_content'] );

		$data['post_type'] = 'glossary';
		$results = $this->gl->sanitizeGlossaryTerm( $data );
		$this->assertEquals( '<a href="https://google.com">All</a> is <strong>good.</strong>', $results['post_content'] );
	}
	/**
	 * @group glossary
	 */
	public function test_backMatterAutoDisplay() {
		// No change
		$content = 'Hello';
		$this->assertEquals( 'Hello', $this->gl->backMatterAutoDisplay( $content ) );

		// No change
		global $post;
		$args = [
			'post_title' => 'Test Glossary: ' . rand(),
			'post_type' => 'back-matter',
			'post_status' => 'publish',
			'post_content' => 'Not empty',
		];
		$pid = $this->factory()->post->create_object( $args );
		wp_set_object_terms( $pid, 'glossary', 'back-matter-type' );
		$post = get_post( $pid );
		$this->assertEquals( 'Not empty', $this->gl->backMatterAutoDisplay( $post->post_content ) );

		// Yes, changed
		$pid = $this->factory()->post->update_object( $pid, [ 'post_content' => ' &nbsp;    ' ] );
		$post = get_post( $pid );
		$this->assertContains( '<dl data-type="glossary">', $this->gl->backMatterAutoDisplay( $post->post_content ) );
	}

}
