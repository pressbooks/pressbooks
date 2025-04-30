<?php

use Pressbooks\Container;
use Pressbooks\Interactive\H5P;

class Interactive_H5PTest extends \WP_UnitTestCase {
	/**
	 * @var H5P
	 * @group interactivecontent
	 */
	protected $h5p;

	/**
	 * @group interactivecontent
	 */
	public function set_up() {
		parent::set_up();
		$blade = Container::get( 'Blade' );
		$this->h5p = new H5P( $blade );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_isActive() {
		$this->assertTrue( is_bool( $this->h5p->isActive() ) );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_replaceShortcode() {
		$result = $this->h5p->replaceShortcode( [] );
		$this->assertStringContainsString( '<div ', $result );
		$this->assertStringContainsString( 'excluded from this version of the text', $result );
		$result = $this->h5p->replaceShortcode( [ 'slug' => 'foo' ] );
		$this->assertStringContainsString( '<div ', $result );
		$this->assertStringContainsString( 'excluded from this version of the text', $result );
		$result = $this->h5p->replaceShortcode( [ 'id' => 999 ] );
		$this->assertStringContainsString( '<div ', $result );
		$this->assertStringContainsString( 'excluded from this version of the text', $result );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_override() {
		global $shortcode_tags;
		$this->h5p->override();
		$this->assertArrayHasKey( 'h5p', $shortcode_tags );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_replaceCloneable() {
		$content = '[h5p id="1"][h5p id=\'2\' something="else"][h5p id=3]';
		$result = $this->h5p->replaceUncloneable( $content );
		$this->assertStringNotContainsString( '[h5p ', $result );
		$this->assertStringContainsString( 'The original version of this chapter contained H5P content', $result );

		$content = '[h5p id="1"][h5p id=\'2\' something="else"][h5p id=3]';
		$result = $this->h5p->replaceUncloneable( $content, [ 1, '2' ] );
		$this->assertStringNotContainsString( '[h5p id="1', $result );
		$this->assertStringNotContainsString( '[h5p id=\'2', $result );
		$this->assertStringContainsString( '[h5p id=3]', $result );
		$this->assertStringContainsString( 'The original version of this chapter contained H5P content', $result );

		$content = '[h5p id="1"][h5p id=\'2\' something="else"][h5p id=3]';
		$result = $this->h5p->replaceUncloneable( $content, 3 );
		$this->assertStringNotContainsString( '[h5p id=3', $result );
		$this->assertStringContainsString( '[h5p id="1', $result );
		$this->assertStringContainsString( '[h5p id=\'2', $result );
		$this->assertStringContainsString( 'The original version of this chapter contained H5P content', $result );
	}
	/**
	 * @group interactivecontent
	 */
	public function test_h5p_custom_wrapper() {
		$html = '<iframe id="h5p-content"><div></div></iframe>';

		$content = [
			'id' => 1,
		];

		$result = $this->h5p->generateCustomH5pWrapper( $html, $content );

		$expected = '<div id="h5p-1"><iframe id="h5p-content"><div></div></iframe></div>';

		$this->assertEquals( $expected, $result );
	}

	/**
	 * @group interactivecontent
	 */
	public function test_text_addon_matches() {
		$params = [ 'text' => 'This is a test content' ];
		$pattern = '/test/';
		$result = $this->h5p->textAddonMatches( $params, $pattern );
		$this->assertTrue( $result );

		$params = [ 'text' => 'No match here' ];
		$result = $this->h5p->textAddonMatches( $params, $pattern );
		$this->assertFalse( $result );
	}

	/**
	 * Test shortcode replacement when H5P representation is successfully generated.
	 *
	 * @group interactivecontent
	 */
	public function test_replaceShortcode_with_representation() {
		// Mock Blade (likely Illuminate\View\Factory)
		$bladeMock = $this->getMockBuilder( \Illuminate\View\Factory::class )
			->disableOriginalConstructor()
			->addMethods( [ 'render' ] ) // Ensure render method exists for mocking
			->getMock();
		$bladeMock->expects( $this->once() )
			->method( 'render' )
			->with(
				'interactive.h5pextractor',
				$this->callback( function ( $params ) {
					return isset( $params['id'] ) && $params['id'] === 123 &&
						   isset( $params['representation'] ) && $params['representation'] === '<p>Mock H5P Content</p>' &&
						   isset( $params['title'] ) && // Check title exists
						   isset( $params['url'] ); // Check url exists
				} )
			)
			->willReturn( '<div>Rendered Mock H5P</div>' );

		// Mock H5P class partially, specifically the getH5PRepresentation method
		$h5pMock = $this->getMockBuilder( H5P::class )
			->setConstructorArgs( [ $bladeMock ] )
			->onlyMethods( [ 'getH5PRepresentation' ] ) // Mock only this method
			->getMock();

		$h5pMock->expects( $this->once() )
			->method( 'getH5PRepresentation' )
			->with( 123 ) // Expecting the ID from the shortcode
			->willReturn( '<p>Mock H5P Content</p>' ); // Return mock HTML

		// Call the method on the mocked object
		$result = $h5pMock->replaceShortcode( [ 'id' => 123 ] );

		// Assert the final rendered output
		$this->assertEquals( '<div>Rendered Mock H5P</div>', $result );
	}

}
