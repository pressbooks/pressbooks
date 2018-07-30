<?php


class Shortcodes_Complex extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Shortcodes\Complex\Complex
	 */
	protected $complex;

	/**
	 *
	 */
	public function setUp() {
		parent::setUp();

		$this->complex = $this->getMockBuilder( '\Pressbooks\Shortcodes\Complex\Complex' )
			->setMethods( null ) // pass null to setMethods() to avoid mocking any method
			->disableOriginalConstructor() // disable private constructor
			->getMock();
	}

	public function test_getInstance() {
		$val = $this->complex->init();

		$this->assertTrue( $val instanceof \Pressbooks\Shortcodes\Complex\Complex );

		global $shortcode_tags;
		$this->assertArrayHasKey( 'anchor', $shortcode_tags );
		$this->assertArrayHasKey( 'columns', $shortcode_tags );
		$this->assertArrayHasKey( 'email', $shortcode_tags );
		$this->assertArrayHasKey( 'equation', $shortcode_tags );
		$this->assertArrayHasKey( 'media', $shortcode_tags );
	}

	public function test_anchorShortcodeHandler() {

		// Test an anchor with an ID.
		$content = $this->complex->anchorShortCodeHandler( [ 'id' => 'my-anchor' ], '', 'anchor' );
		$this->assertEquals( '<a id="my-anchor"></a>', $content );

		// Test an anchor with an invalid ID.
		$content = $this->complex->anchorShortCodeHandler( [ 'id' => 'This should not have spaces in it' ], '', 'anchor' );
		$this->assertEquals( '<a id="this-should-not-have-spaces-in-it"></a>', $content );

		// Test an anchor with an optional title.
		$content = $this->complex->anchorShortCodeHandler( [ 'id' => 'my-anchor-with-a-title' ], 'My anchor', 'anchor' );
		$this->assertEquals( '<a id="my-anchor-with-a-title" title="My anchor"></a>', $content );

		$this->assertEmpty( $this->generics->anchorShortCodeHandler( [], '', 'anchor' ) );
	}
}
