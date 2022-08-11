<?php

use Pressbooks\HtmlParser;


class HtmlParserTest extends \WP_UnitTestCase {
	/**
	 * @group htmlparser
	 */
	public function test_externalParser() {
		$html5 = new HtmlParser( false );

		$content1 = 'Poorly formatted HTML with no tags';
		$doc = $html5->loadHTML( $content1 );
		$this->assertTrue( $doc instanceof \DOMDocument );
		$this->assertEquals(
			trim( $content1 ),
			trim( $html5->saveHTML( $doc ) )
		);

		$content2 = "<p>Hello</p>\r\n<img src='ééé.png' alt='èèè' />\r\n<p>World</p>";
		$doc = $html5->loadHTML( $content2 );
		$this->assertTrue( $doc instanceof \DOMDocument );
		$this->assertEquals(
			trim( "<p>Hello</p>\n<img src=\"ééé.png\" alt=\"èèè\">\n<p>World</p>" ),
			trim( $html5->saveHTML( $doc ) )
		);
	}

	/**
	 * @group htmlparser
	 */
	public function test_internalParser() {
		$html5 = new HtmlParser( true );

		$content1 = 'Poorly formatted HTML with no tags';
		$doc = $html5->loadHTML( $content1 );
		$this->assertTrue( $doc instanceof \DOMDocument );
		$this->assertEquals(
			trim( $content1 ),
			trim( $html5->saveHTML( $doc ) )
		);

		$content2 = "<p>Hello</p>\r\n<img src='ééé.png' alt='èèè' />\r\n<p>World</p>";
		$doc = $html5->loadHTML( $content2 );
		$this->assertTrue( $doc instanceof \DOMDocument );
		$this->assertEquals(
			trim( "<p>Hello</p>\r\n<img src=\"%C3%A9%C3%A9%C3%A9.png\" alt=\"&egrave;&egrave;&egrave;\">\r\n<p>World</p>" ),
			trim( $html5->saveHTML( $doc ) )
		);
	}

	/**
	 * @group htmlparser
	 */
	public function test_removeFixMeWrapper() {
		$html5 = new HtmlParser( true );
		$html = '<div><!-- pb_fixme --><p>I am a paragraph</p><!-- pb_fixme --></div>';
		$this->assertEquals( '<p>I am a paragraph</p>', $html5->removeFixMeWrapper( $html ) );
		$html = '<p>I am a paragraph</p>';
		$this->assertEquals( $html, $html5->removeFixMeWrapper( $html ) );
	}
}
