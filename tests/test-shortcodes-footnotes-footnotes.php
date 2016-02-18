<?php


class Shortcodes_Footnotes_Footnotes extends \WP_UnitTestCase {

	use utilsTrait;

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
	 * @covers \PressBooks\Shortcodes\Footnotes\Footnotes::getInstance
	 */
	public function test_getInstance() {

		$val = $this->fn->getInstance();

		$this->assertTrue( $val instanceof \PressBooks\Shortcodes\Footnotes\Footnotes );
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

		$this->assertEmpty( $this->fn->shortcodeHandler( [ ] ) );
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


	/**
	 * @covers \PressBooks\Shortcodes\Footnotes\Footnotes::footnoteContent
	 */
	public function test_footnoteContent_numbered() {

		global $id;
		$id = 1;

		// First, add some footnotes
		$_ = $this->fn->shortcodeHandler( [ 'numbered' => 'yes' ], 'First.' );
		$_ = $this->fn->shortcodeHandler( [ ], 'Second.' );
		$_ = $this->fn->shortcodeHandler( [ ], 'Third.' );

		$content = $this->fn->footnoteContent( 'Hello World' );

		$this->assertContains( '<div class="footnotes">', $content );
		$this->assertContains( 'First.', $content );
		$this->assertContains( 'Second.', $content );
		$this->assertContains( 'Third.', $content );
		$this->assertContains( '</ol></div>', $content );
	}


	/**
	 * @covers \PressBooks\Shortcodes\Footnotes\Footnotes::footnoteContent
	 */
	public function test_footnoteContent_notNumbered() {

		global $id;
		$id = 999;

		// First, add some footnotes
		$_ = $this->fn->shortcodeHandler( [ 'numbered' => 'no' ], 'First.' );
		$_ = $this->fn->shortcodeHandler( [ ], 'Second.' );
		$_ = $this->fn->shortcodeHandler( [ ], 'Third.' );

		$content = $this->fn->footnoteContent( 'Hello World' );

		$this->assertContains( '<div class="footnotes">', $content );
		$this->assertContains( 'First.', $content );
		$this->assertContains( 'Second.', $content );
		$this->assertContains( 'Third.', $content );
		$this->assertContains( '</ul></div>', $content );
	}


	/**
	 * @covers \PressBooks\Shortcodes\Footnotes\Footnotes::myCustomQuicktags
	 */
	public function test_myCustomQuicktags() {

		$this->fn->myCustomQuicktags();

		$this->assertTrue( wp_script_is( 'my_custom_quicktags', 'queue' ) );
	}


	/**
	 * @covers \PressBooks\Shortcodes\Footnotes\Footnotes::registerFootnoteButtons
	 */
	public function test_registerFootnoteButtons() {

		$buttons = $this->fn->registerFootnoteButtons( [ ] );

		$this->assertNotEmpty( $buttons );
	}


	/**
	 * @covers \PressBooks\Shortcodes\Footnotes\Footnotes::addFootnotePlugin
	 */
	public function test_addFootnotePlugin() {

		$val = $this->fn->addFootnotePlugin( [ ] );

		$this->assertNotEmpty( $val );
	}


	/**
	 * @covers \PressBooks\Shortcodes\Footnotes\Footnotes::ajaxFailure
	 */
	public function test_ajaxFailure() {

		$this->_fakeAjax();

		ob_start();
		\PressBooks\Shortcodes\Footnotes\Footnotes::ajaxFailure( 'foobar' );
		$buffer = ob_get_clean();

		$this->assertContains( 'foobar', $buffer );
	}


	/**
	 * @covers \PressBooks\Shortcodes\Footnotes\Footnotes::convertWordFootnotes
	 */
	public function test_convertWordFootnotes() {

		$this->_fakeAjax();

		// Test invalid permissions

		ob_start();
		\PressBooks\Shortcodes\Footnotes\Footnotes::convertWordFootnotes();
		$buffer = ob_get_clean();
		$this->assertContains( __( 'Invalid permissions.', 'pressbooks' ), $buffer );

		// Test is json

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-footnote-convert' );
		$_POST['content'] = 'Hello world!';

		ob_start();
		\PressBooks\Shortcodes\Footnotes\Footnotes::convertWordFootnotes();
		$buffer = ob_get_clean();
		$this->assertJson( $buffer );

		// TODO: Test regular expressions by passing Word and LibreOffice footnote HTML
	}


}
