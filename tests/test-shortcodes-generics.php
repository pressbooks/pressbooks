<?php


class Shortcodes_Generics extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Shortcodes\Generics\Generics
	 * @group shortcodes
	 */
	protected $generics;

	/**
	 * @group shortcodes
	 */
	public function set_up() {
		parent::set_up();

		$this->generics = $this->getMockBuilder( '\Pressbooks\Shortcodes\Generics\Generics' )
			->setMethods( null ) // pass null to setMethods() to avoid mocking any method
			->disableOriginalConstructor() // disable private constructor
			->getMock();
	}

	/**
	 * @group shortcodes
	 */
	public function test_getInstance() {
		$val = $this->generics->init();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Generics\Generics );

		global $shortcode_tags;
		$this->assertArrayHasKey( 'blockquote', $shortcode_tags );
	}

	/**
	 * @group shortcodes
	 */
	public function test_blockShortcodeHandler() {
		// Test a straightforward tag.
		$content = $this->generics->blockShortcodeHandler( [], 'heading', 'A heading' );
		$this->assertEquals( '<h1>A heading</h1>', $content );

		// Test a tag with a class attribute.
		$content = $this->generics->blockShortcodeHandler( [ 'class' => 'special' ], 'heading', 'A heading' );
		$this->assertEquals( '<h1 class="special">A heading</h1>', $content );

		$this->assertEmpty( $this->generics->blockShortcodeHandler( [], 'heading' ) );
	}

	/**
	 * @group shortcodes
	 */
	public function test_multilineBlockShortcodeHandler() {
		// Test a straightforward tag.
		$content = $this->generics->multilineBlockShortcodeHandler( [], 'blockquote', 'A normal blockquote' );
		$this->assertEquals( "<blockquote><p>A normal blockquote</p>\n</blockquote>", $content );

		// Test a tag with a class attribute.
		$content = $this->generics->multilineBlockShortcodeHandler( [ 'class' => 'special' ], 'blockquote', 'A special blockquote' );
		$this->assertEquals( "<blockquote class=\"special\"><p>A special blockquote</p>\n</blockquote>", $content );

		// Test a tag which applies a class automatically.
		$content = $this->generics->multilineBlockShortcodeHandler( [], 'textbox', 'A normal textbox' );
		$this->assertEquals( "<div class=\"textbox\"><p>A normal textbox</p>\n</div>", $content );

		// Test a tag which applies a class automatically, with an additional class attribute.
		$content = $this->generics->multilineBlockShortcodeHandler( [ 'class' => 'special' ], 'textbox', 'A special textbox' );
		$this->assertEquals( "<div class=\"textbox special\"><p>A special textbox</p>\n</div>", $content );

		$this->assertEmpty( $this->generics->multilineBlockShortcodeHandler( [], 'blockquote', ) );
	}

	/**
	 * @group shortcodes
	 */
	public function test_inlineShortcodeHandler() {
		// Test a straightforward tag.
		$content = $this->generics->inlineShortcodeHandler( [], 'strong', 'WHY ARE YOU SHOUTING' );

		$this->assertEquals( '<strong>WHY ARE YOU SHOUTING</strong>', $content );

		// Test a tag with a class attribute.
		$content = $this->generics->inlineShortcodeHandler( [ 'class' => 'loud' ], 'strong', 'WHY ARE YOU SHOUTING' );

		$this->assertEquals( '<strong class="loud">WHY ARE YOU SHOUTING</strong>', $content );

		$this->assertEmpty( $this->generics->inlineShortcodeHandler( [], 'strong' ) );
	}
}
