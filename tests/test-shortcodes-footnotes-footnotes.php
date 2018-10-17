<?php


class Shortcodes_Footnotes extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Shortcodes\Footnotes\Footnotes
	 */
	protected $fn;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();

		$this->fn = $this->getMockBuilder( '\Pressbooks\Shortcodes\Footnotes\footnotes' )
						 ->setMethods( null )// pass null to setMethods() to avoid mocking any method
						 ->disableOriginalConstructor()// disable private constructor
						 ->getMock();
	}

	public function test_getInstance() {

		$val = $this->fn->init();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Footnotes\Footnotes );

		global $shortcode_tags;
		$this->assertArrayHasKey( 'footnote', $shortcode_tags );
	}

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

		$this->assertEmpty( $this->fn->shortcodeHandler( [] ) );
	}

	public function test_shortcodeHandler_notNumbered() {

		global $id;
		$id = 999;

		$content = $this->fn->shortcodeHandler( [ 'numbered' => 'no' ], 'Hello world!' );
		$this->assertContains( '[*]</sup></a>', $content );

		$content = $this->fn->shortcodeHandler( [ 'symbol' => '!' ], 'Hello again world!' );
		$this->assertContains( '[!]</sup></a>', $content );

		$content = $this->fn->shortcodeHandler( [ 'suptext' => 'Foo' ], 'Well this is awkward...' );
		$this->assertContains( '[*Foo]</sup></a>', $content );

		$this->assertContains( '#footnote-999-3', $content );
	}


	public function test_footnoteContent_numbered() {

		global $id;
		$id = 1;

		// First, add some footnotes
		$_ = $this->fn->shortcodeHandler( [ 'numbered' => 'yes' ], 'First.' );
		$_ = $this->fn->shortcodeHandler( [], 'Second.' );
		$_ = $this->fn->shortcodeHandler( [], 'Third.' );

		$content = $this->fn->footnoteContent( 'Hello World' );

		$this->assertContains( '<div class="footnotes">', $content );
		$this->assertContains( 'First.', $content );
		$this->assertContains( 'Second.', $content );
		$this->assertContains( 'Third.', $content );
		$this->assertContains( '</ol></div>', $content );
	}


	public function test_footnoteContent_notNumbered() {

		global $id;
		$id = 999;

		// First, add some footnotes
		$_ = $this->fn->shortcodeHandler( [ 'numbered' => 'no' ], 'First.' );
		$_ = $this->fn->shortcodeHandler( [], 'Second.' );
		$_ = $this->fn->shortcodeHandler( [], 'Third.' );

		$content = $this->fn->footnoteContent( 'Hello World' );

		$this->assertContains( '<div class="footnotes">', $content );
		$this->assertContains( 'First.', $content );
		$this->assertContains( 'Second.', $content );
		$this->assertContains( 'Third.', $content );
		$this->assertContains( '</ul></div>', $content );
	}


	public function test_ajaxFailure() {

		$old_error_reporting = $this->_fakeAjax();

		ob_start();
		\Pressbooks\Shortcodes\Footnotes\Footnotes::ajaxFailure( 'foobar' );
		$buffer = ob_get_clean();
		$this->assertContains( 'foobar', $buffer );

		$this->_fakeAjaxDone( $old_error_reporting );
	}

	public function test_convertWordFootnotes() {

		$old_error_reporting = $this->_fakeAjax();

		// Test invalid permissions
		ob_start();
		\Pressbooks\Shortcodes\Footnotes\Footnotes::convertWordFootnotes();
		$buffer = ob_get_clean();
		$this->assertContains( __( 'Invalid permissions.', 'pressbooks' ), $buffer );

		// Test is json
		$user_id = $this->factory()->user->create( [ 'role' => 'administrator' ] );
		wp_set_current_user( $user_id );
		$_REQUEST['_ajax_nonce'] = wp_create_nonce( 'pb-footnote-convert' );
		$_POST['content'] = 'Hello world!';
		ob_start();
		\Pressbooks\Shortcodes\Footnotes\Footnotes::convertWordFootnotes();
		$buffer = ob_get_clean();
		$this->assertJson( $buffer );

		// TODO: Test regular expressions by passing Word and LibreOffice footnote HTML

		$this->_fakeAjaxDone( $old_error_reporting );
	}


}
