<?php


class Shortcodes_Footnotes extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Shortcodes\Footnotes\Footnotes
	 * @group footnotes
	 */
	protected $fn;


	/**
	 * @group footnotes
	 */
	public function set_up() {
		parent::set_up();

		$this->fn = $this->getMockBuilder( '\Pressbooks\Shortcodes\Footnotes\footnotes' )
						->setMethods( null )// pass null to setMethods() to avoid mocking any method
						->disableOriginalConstructor()// disable private constructor
						->getMock();
	}

	/**
	 * @group footnotes
	 */
	public function test_getInstance() {

		$val = $this->fn->init();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Footnotes\Footnotes );

		global $shortcode_tags;
		$this->assertArrayHasKey( 'footnote', $shortcode_tags );
	}


	/**
	 * @group footnotes
	 */
	public function test_shortcodeHandler_numbered() {

		global $id;
		$id = 1;

		$content = $this->fn->shortcodeHandler( [ 'numbered' => 'yes' ], 'Hello world!' );
		$this->assertStringContainsString( '[1]</sup></a>', $content );

		$content = $this->fn->shortcodeHandler( [ 'symbol' => '!' ], 'Hello again world!' ); // Symbol should be ignored because this is already set to numbered
		$this->assertStringContainsString( '[2]</sup></a>', $content );

		$content = $this->fn->shortcodeHandler( [ 'suptext' => 'Foo' ], 'Well this is awkward...' );
		$this->assertStringContainsString( '[3. Foo]</sup></a>', $content );

		$this->assertStringContainsString( 'aria-label="Footnote 3"', $content );

		$this->assertStringContainsString( '#footnote-1-3', $content );

		$this->assertEmpty( $this->fn->shortcodeHandler( [] ) );
	}


	/**
	 * @group footnotes
	 */
	public function test_shortcodeHandler_notNumbered() {

		global $id;
		$id = 999;

		$content = $this->fn->shortcodeHandler( [ 'numbered' => 'no' ], 'Hello world!' );
		$this->assertStringContainsString( '[*]</sup></a>', $content );

		$content = $this->fn->shortcodeHandler( [ 'symbol' => '!' ], 'Hello again world!' );
		$this->assertStringContainsString( '[!]</sup></a>', $content );

		$content = $this->fn->shortcodeHandler( [ 'suptext' => 'Foo' ], 'Well this is awkward...' );
		$this->assertStringContainsString( '[*Foo]</sup></a>', $content );

		$this->assertStringContainsString( 'aria-label="Footnote 3"', $content );

		$this->assertStringContainsString( '#footnote-999-3', $content );
	}


	/**
	 * @group footnotes
	 */
	public function test_footnoteContent_numbered() {

		global $id;
		$id = 1;

		// First, add some footnotes
		$_ = $this->fn->shortcodeHandler( [ 'numbered' => 'yes' ], 'First.' );
		$_ = $this->fn->shortcodeHandler( [], 'Second.' );
		$_ = $this->fn->shortcodeHandler( [], 'Third.' );

		$content = $this->fn->footnoteContent( 'Hello World' );

		$this->assertStringContainsString( '<div class="footnotes">', $content );
		$this->assertStringContainsString( 'First.', $content );
		$this->assertStringContainsString( 'Second.', $content );
		$this->assertStringContainsString( 'Third.', $content );
		$this->assertStringContainsString( 'aria-label="Return to footnote 2', $content );
		$this->assertStringContainsString( '</ol></div>', $content );
	}


	/**
	 * @group footnotes
	 */
	public function test_footnoteContent_notNumbered() {

		global $id;
		$id = 999;

		// First, add some footnotes
		$_ = $this->fn->shortcodeHandler( [ 'numbered' => 'no' ], 'First.' );
		$_ = $this->fn->shortcodeHandler( [], 'Second.' );
		$_ = $this->fn->shortcodeHandler( [], 'Third.' );

		$content = $this->fn->footnoteContent( 'Hello World' );

		$this->assertStringContainsString( '<div class="footnotes">', $content );
		$this->assertStringContainsString( 'First.', $content );
		$this->assertStringContainsString( 'Second.', $content );
		$this->assertStringContainsString( 'Third.', $content );
		$this->assertStringContainsString( 'aria-label="Return to footnote 2', $content );
		$this->assertStringContainsString( '</ul></div>', $content );
	}


	/**
	 * @group footnotes
	 */
	public function test_ajaxFailure() {

		$old_error_reporting = $this->_fakeAjax();

		ob_start();
		\Pressbooks\Shortcodes\Footnotes\Footnotes::ajaxFailure( 'foobar' );
		$buffer = ob_get_clean();
		$this->assertStringContainsString( 'foobar', $buffer );

		$this->_fakeAjaxDone( $old_error_reporting );
	}


	/**
	 * @group footnotes
	 */
	public function test_convertWordFootnotes() {

		$old_error_reporting = $this->_fakeAjax();

		// Test invalid permissions
		ob_start();
		\Pressbooks\Shortcodes\Footnotes\Footnotes::convertWordFootnotes();
		$buffer = ob_get_clean();
		$this->assertStringContainsString( __( 'Invalid permissions.', 'pressbooks' ), $buffer );

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
