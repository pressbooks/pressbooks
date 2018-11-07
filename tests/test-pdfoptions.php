<?php

class PDFOptionsTest extends \WP_UnitTestCase {
	use utilsTrait;

	public function test_scssOverrides() {
		$this->_book( 'pressbooks-luther' );

		update_option( 'pressbooks_theme_options_global', [
			'chapter_numbers' => 0,
		] );

		$result = \Pressbooks\Modules\ThemeOptions\PDFOptions::scssOverrides( '' );
		$this->assertContains( 'div.part-title-wrap > .part-number, div.chapter-title-wrap > .chapter-number, #toc .part a::before, #toc .chapter a::before { display: none !important; }', $result );

	}

	public function test_replaceRunningContentTags() {
		$result = \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( '%section_title%' );
		$this->assertEquals( '"" string(section-title) ""', $result );
		$result = \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentTags( 'foo' );
		$this->assertEquals( '"foo"', $result );
	}

	public function test_replaceRunningContentStrings() {
		$result = \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentStrings( 'string(section-title)' );
		$this->assertEquals( '%section_title%', $result );
		$result = \Pressbooks\Modules\ThemeOptions\PDFOptions::replaceRunningContentStrings( 'foo' );
		$this->assertEquals( 'foo', $result );
	}
}
