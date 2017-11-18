<?php

class PDFOptionsTest extends \WP_UnitTestCase {

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
