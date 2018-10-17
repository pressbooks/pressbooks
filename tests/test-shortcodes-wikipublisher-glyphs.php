<?php

class Shortcodes_WikiPublisher_GlyphsTest extends \WP_UnitTestCase {

	/**
	 * @var \Pressbooks\Shortcodes\Wikipublisher\Glyphs
	 */
	protected $glyphs;


	/**
	 *
	 */
	public function setUp() {
		parent::setUp();

		$this->glyphs = $this->getMockBuilder( '\Pressbooks\Shortcodes\Wikipublisher\Glyphs' )
							 ->setMethods( null )// pass null to setMethods() to avoid mocking any method
							 ->disableOriginalConstructor()// disable private constructor
							 ->getMock();
	}

	public function test_langShortcode_grk() {

		$content = $this->glyphs->langShortcode(
			[ 'lang' => 'grc' ],
			'aeiou'
		);

		$this->assertContains( '<span lang="grc"', $content );
		$this->assertContains( '&#945;&#949;&#953;&#959;&#965;', $content );

		$content = $this->glyphs->langShortcode(
			[ 'lang' => 'ell' ],
			'aeiou'
		);

		$this->assertContains( '<span lang="el"', $content );
		$this->assertContains( '&#945;&#949;&#953;&#959;&#965;', $content );
	}

	public function test_langShortcode_he() {

		$content = $this->glyphs->langShortcode(
			[ 'lang' => 'hbo' ],
			'aeiou'
		);

		$this->assertContains( '<span lang="he"', $content );
		$this->assertContains( '&#1463;&#1461;&#1460;&#1465;&#1467;', $content );
	}

	public function test_langShortcode_bad() {

		$content = $this->glyphs->langShortcode(
			[ 'lang' => 'foobar' ],
			'aeiou'
		);

		$this->assertContains( 'ERROR', $content );
	}

}
