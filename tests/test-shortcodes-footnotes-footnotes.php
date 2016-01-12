<?php


class Shortcodes_Footnotes_Footnotes extends \WP_UnitTestCase {


	/**
	 * @var \PressBooks\Shortcodes\Footnotes\Footnotes
	 */
	protected $fn;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();

		$this->fn = $this->getMockBuilder( '\PressBooks\Shortcodes\Footnotes\footnotes' )
			->setMethods( null )// pass null to setMethods() to avoid mocking any method
			->disableOriginalConstructor()// disable private constructor
			->getMock();
	}


	/**
	 * @covers \PressBooks\Shortcodes\Footnotes\Footnotes::shortcodeHandler
	 */
	public function test_shortcodeHandler_numbered() {

		global $id;
		$id = 1;

		$content = $this->fn->shortcodeHandler( [ 'numbered' => 'yes' ], 'Hello world!' );
		$this->assertContains( '[1]</sup></a>', $content );

		$content = $this->fn->shortcodeHandler( [ 'symbol' => '!' ], 'Hello again world!' ); // Symbol should be ignored because this is already set to numbered
		$this->assertContains( '[2]</sup></a>', $content );

		$content = $this->fn->shortcodeHandler( [ 'suptext' => 'Foo' ], 'Well this is awkward...' );
		$this->assertContains( '[3. Foo]</sup></a>', $content );

		$this->assertContains( '#footnote-1-3', $content );
	}


	/**
	 * @covers \PressBooks\Shortcodes\Footnotes\Footnotes::shortcodeHandler
	 */
	public function test_shortcodeHandler_notNumbered() {

		global $id;
		$id = 999;

		$content = $this->fn->shortcodeHandler( [ 'numbered' => 'no', ], 'Hello world!' );
		$this->assertContains( '[*]</sup></a>', $content );

		$content = $this->fn->shortcodeHandler( [ 'symbol' => '!' ], 'Hello again world!' );
		$this->assertContains( '[!]</sup></a>', $content );

		$content = $this->fn->shortcodeHandler( [ 'suptext' => 'Foo' ], 'Well this is awkward...' );
		$this->assertContains( '[*Foo]</sup></a>', $content );

		$this->assertContains( '#footnote-999-3', $content );
	}

}
