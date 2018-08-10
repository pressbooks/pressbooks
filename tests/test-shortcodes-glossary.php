<?php

class Shortcodes_Glossary extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Shortcodes\Glossary\Glossary
	 */
	protected $gl;

	public function setUp() {
		parent::setUp();

		$this->gl = $this->getMockBuilder( '\Pressbooks\Shortcodes\Glossary\Glossary' )
		                 ->setMethods( NULL )
		                 ->disableOriginalConstructor()
		                 ->getMock();

		$this->_createGlossaryTerms();
	}

	private function _createGlossaryTerms() {
		$args1 = [
			'post_type'    => 'glossary',
			'post_title'   => 'Support Vector Machine',
			'post_content' => 'An <i>algorithm</i> that uses a nonlinear mapping to transform the original training data into a higher dimension',
		];

		$args2 = [
			'post_type'    => 'glossary',
			'post_title'   => 'Neural Network',
			'post_content' => 'A computer system modeled on the <b>human brain</b> and <a href="https://en.wikipedia.org/wiki/Nervous_system" target="_blank">nervous system</a>.',
		];

		$this->factory()->post->create_object( $args1 );
		$this->factory()->post->create_object( $args2 );
	}

	private function _createGlossaryPost() {
		$args = [
			'post_type'    => 'glossary',
			'post_title'   => 'Neural Network',
			'post_content' => 'A computer system modeled on the <b>human brain</b> and <a href="https://en.wikipedia.org/wiki/Nervous_system" target="_blank">nervous system</a>.',
		];

		$pid = $this->factory()->post->create_object( $args );

		return $pid;
	}

	public function test_getInstance() {

		$val = $this->gl->init();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Glossary\Glossary );

		global $shortcode_tags;

		$this->assertArrayHasKey( 'pb_glossary', $shortcode_tags );

	}

	public function test_glossaryTerms() {
		$dl = $this->gl->glossaryTerms();

		// assures alphabetical listing and format
		$this->assertEquals( '<section data-type="glossary"><header><h2>Glossary Terms</h2></header><dl data-type="glossary"><dt data-type="glossterm"><dfn id="dfn-neural-network">Neural Network</dfn></dt><dd data-type="glossdef">A computer system modeled on the <b>human brain</b> and <a href="https://en.wikipedia.org/wiki/Nervous_system" target="_blank">nervous system</a>.</dd><dt data-type="glossterm"><dfn id="dfn-support-vector-machine">Support Vector Machine</dfn></dt><dd data-type="glossdef">An <i>algorithm</i> that uses a nonlinear mapping to transform the original training data into a higher dimension</dd></dl></section>', $dl );

	}

	public function test_registerGlossaryButtons() {
		$args = [ 'bold', 'italics', 'underline' ];

		$buttons = $this->gl->registerGlossaryButtons( $args );

		$this->assertEquals( [
			'bold',
			'italics',
			'underline',
			'glossary',
			'glossary_all',
		], $buttons );
	}

	public function test_addGlossaryPlugin() {
		$plugin_array    = [ 'footnotes' => 'http://example.org/wp-content/plugins/pressbooks/assets/src/scripts/footnote.js' ];
		$glossary_plugin = $this->gl->addGlossaryPlugin( $plugin_array );
		$result          = [
			'footnotes' => 'http://example.org/wp-content/plugins/pressbooks/assets/src/scripts/footnote.js',
			'glossary'  => 'http://example.org/wp-content/plugins/pressbooks/assets/src/scripts/glossary.js',
		];
		$this->assertEquals( $result, $glossary_plugin );

	}

	public function test_glossaryTooltip() {
		$pid = $this->_createGlossaryPost();

		$result = $this->gl->glossaryTooltip( $pid, 'Neural Network' );

		$this->assertEquals( '<a href="javascript:void(0);" class="tooltip" title="A computer system modeled on the human brain and nervous system.">Neural Network</a>', $result );
	}

}
