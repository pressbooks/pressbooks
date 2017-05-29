<?php


class Shortcodes_Generics extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Shortcodes\Generics\Generics
	 */
	protected $generics;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();

		$this->generics = $this->getMockBuilder( '\Pressbooks\Shortcodes\Generics\Generics' )
			->setMethods( null ) // pass null to setMethods() to avoid mocking any method
			->disableOriginalConstructor() // disable private constructor
			->getMock();
	}

	public function test_getInstance() {
		$val = $this->generics->getInstance();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Generics\Generics );

	}

	public function test_shortcodeHandler() {

		// Test a straightforward tag.
		$content = $this->generics->shortcodeHandler( [], 'A normal blockquote', 'blockquote' );
		$this->assertEquals( "<blockquote><p>A normal blockquote</p>\n</blockquote>", $content );

		// Test a tag with a class attribute.
		$content = $this->generics->shortcodeHandler( [ 'class' => 'special' ], 'A special blockquote', 'blockquote' );
		$this->assertEquals( "<blockquote class=\"special\"><p>A special blockquote</p>\n</blockquote>", $content );

		// Test a tag which applies a class automatically.
		$content = $this->generics->shortcodeHandler( [], 'A normal textbox', 'textbox' );
		$this->assertEquals( "<div class=\"textbox\"><p>A normal textbox</p>\n</div>", $content );

		// Test a tag which applies a class automatically, with an additional class attribute.
		$content = $this->generics->shortcodeHandler( [ 'class' => 'special' ], 'A special textbox', 'textbox' );
		$this->assertEquals( "<div class=\"textbox special\"><p>A special textbox</p>\n</div>", $content );

		$this->assertEmpty( $this->generics->shortcodeHandler( [], '', 'blockquote' ) );
	}

}
