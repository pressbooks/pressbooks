<?php

class SanitizeTest extends \WP_UnitTestCase {


	/**
	 * @covers \PressBooks\Sanitize\html5_to_xhtml11
	 */
	public function test_html5_to_xhtml11() {

		$html = '<article style="font-weight:bold;">Foo</article><h1>Hello!</h1><command>Bar</command>';
		$html = \PressBooks\Sanitize\html5_to_xhtml11( $html );
		$this->assertEquals(
			'<div class=\'bc-article article\'  style="font-weight:bold;">Foo</div><h1>Hello!</h1><div class=\'bc-command command\' >Bar</div>',
			$html
		);
	}


	/**
	 * @covers \PressBooks\Sanitize\html5_to_epub3
	 */
	public function test_html5_to_epub3() {

		$html = '<article style="font-weight:bold;">Foo</article><h1>Hello!</h1><command>Bar</command>';
		$html = \PressBooks\Sanitize\html5_to_epub3( $html );
		$this->assertEquals(
			'<article style="font-weight:bold;">Foo</article><h1>Hello!</h1><div class=\'bc-command command\' >Bar</div>',
			$html
		);
	}


	/**
	 * @covers \PressBooks\Sanitize\fix_audio_shortcode
	 */
	public function test_fix_audio_shortcode() {

		\PressBooks\Sanitize\fix_audio_shortcode();
		$this->assertTrue( has_filter( 'wp_audio_shortcode' ) );

		// Verify that style attribute is empty.
		$var = wp_audio_shortcode( array( 'src' => 'http://foo/audio.mp3' ) );
		$this->assertContains( 'style=""', $var );
	}


	/**
	 * @covers \PressBooks\Sanitize\sanitize_xml_attribute
	 */
	public function test_sanitize_xml_attribute() {

		$var = 'Hello-World!';
		$this->assertEquals( $var, \PressBooks\Sanitize\sanitize_xml_attribute( $var ) );

		$var = "\t <Hello&World!> ";
		$this->assertEquals( '&lt;Hello&amp;World!&gt;', \PressBooks\Sanitize\sanitize_xml_attribute( $var ) );

		$var = " te\fst";
		$this->assertEquals( 'test', \PressBooks\Sanitize\sanitize_xml_attribute( $var ) );
	}


	/**
	 * @covers \PressBooks\Sanitize\sanitize_xml_id
	 */
	public function test_sanitize_xml_id() {

		$var = "Hello-World!";
		$test = $this->_generateControlCharacters() . $var;
		$test = \PressBooks\Sanitize\sanitize_xml_id( $test );
		$this->assertEquals( 'Hello-World', $test );

		$var = " Héllö Wôrld! ";
		$test = \PressBooks\Sanitize\sanitize_xml_id( $var );
		$this->assertEquals( 'HelloWorld', $test );

		$var = '123';
		$test = \PressBooks\Sanitize\sanitize_xml_id( $var );
		$this->assertStringStartsWith( 'slug-123', $test );

		$var = "こんにちは世界!";
		$test = \PressBooks\Sanitize\sanitize_xml_id( $var );
		$this->assertStringStartsWith( 'slug-', $test );
	}


	/**
	 * @covers \PressBooks\Sanitize\remove_control_characters
	 */
	public function test_remove_control_characters() {

		$var = "Hello-World!";
		$test = $this->_generateControlCharacters() . $var;
		$test = \PressBooks\Sanitize\remove_control_characters( $test );
		$this->assertEquals( 12, strlen( $test ) );

		$var = "Héllö Wôrld!";
		$test = \PressBooks\Sanitize\remove_control_characters( $var );
		$this->assertEquals( 12, mb_strlen( $test, 'UTF-8' ) );

		$var = "こんにちは世界!";
		$test = \PressBooks\Sanitize\remove_control_characters( $var );
		$this->assertEquals( 8, mb_strlen( $test, 'UTF-8' ) );
	}


	/**
	 * @covers \PressBooks\Sanitize\force_ascii
	 */
	public function test_force_ascii() {

		$var = "Hello-World!";
		$test = $this->_generateControlCharacters() . $var;
		$test = \PressBooks\Sanitize\force_ascii( $test );
		$this->assertEquals( 12, strlen( $test ) );

		$var = "Héllö Wôrld!";
		$test = \PressBooks\Sanitize\force_ascii( $var );
		$this->assertEquals( 9, strlen( $test ) );

		$var = "こんにちは世界!";
		$test = \PressBooks\Sanitize\force_ascii( $var );
		$this->assertEquals( 1, strlen( $test ) );
	}


	/**
	 * Generate a string containing all the ASCII control characters
	 *
	 * @return string
	 */
	private function _generateControlCharacters() {

		$controlCharacters = chr( 127 );
		for ( $i = 0; $i < 32; ++ $i ) {
			$controlCharacters .= chr( $i );
		}

		return $controlCharacters;
	}


	/**
	 * @covers \PressBooks\Sanitize\decode
	 */
	public function test_decode() {

		$test = '&#48;&#49;&#50;&#51;&#52;&#53;&#038;&#54;&#55;&#56;&#57;';
		$test = \PressBooks\Sanitize\decode( $test );
		$this->assertEquals( '012345&#038;6789', $test );

		$test = '&#48;&#49;&#50;&#51;&#52;&#53;&amp;&#54;&#55;&#56;&#57;';
		$test = \PressBooks\Sanitize\decode( $test );
		$this->assertEquals( '012345&#038;6789', $test );
	}


	/**
	 * @covers \PressBooks\Sanitize\strip_br
	 */
	public function test_strip_br() {

		$test = 'Hello <br /> World!';
		$test = \PressBooks\Sanitize\strip_br( $test );
		$this->assertEquals( 'Hello   World!', $test );

		$test = 'Hello <br/><br   /> World!';
		$test = \PressBooks\Sanitize\strip_br( $test );
		$this->assertEquals( 'Hello    World!', $test );

		$test = 'Hello &lt;br /&gt; World!';
		$test = \PressBooks\Sanitize\strip_br( $test );
		$this->assertEquals( 'Hello   World!', $test );

		$test = 'Hello &lt;br/&gt;&lt;br   /&gt; World!';
		$test = \PressBooks\Sanitize\strip_br( $test );
		$this->assertEquals( 'Hello    World!', $test );
	}


	/**
	 * @covers \PressBooks\Sanitize\filter_title
	 */
	public function test_filter_title() {

		// Acceptable Tags: <br />, <span> with class, <em>, and <strong>.

		$test = '<h1><em>Hello</em><br/><strong>World!</strong></h1>';
		$test = \PressBooks\Sanitize\filter_title( $test );
		$this->assertEquals( '<em>Hello</em><br /><strong>World!</strong>', $test );

		$test = '<span class="pb" style="font-weight:bold;"><i><b>Foobar</b></i></span><p /><div>Foobaz</div>';
		$test = \PressBooks\Sanitize\filter_title( $test );
		$this->assertEquals( '<span class="pb">Foobar</span>Foobaz', $test );
	}


	/**
	 * @covers \PressBooks\Sanitize\canonicalize_url
	 */
	public function test_canonicalize_url() {

		$url = 'pressbooks.com/';
		$this->assertEquals( 'http://pressbooks.com', \PressBooks\Sanitize\canonicalize_url( $url ) );

		$url = 'https://pressbooks.com/';
		$this->assertEquals( 'https://pressbooks.com', \PressBooks\Sanitize\canonicalize_url( $url ) );

		$url = 'HTTPS://PRESSBOOKS.COM/FOO/BAR/';
		$this->assertEquals( 'https://pressbooks.com/FOO/BAR', \PressBooks\Sanitize\canonicalize_url( $url ) );

		$url = 'ftp://PRESSBOOKS.COM/foo/BAR�/?hello=world&TESTING=��123';
		$this->assertEquals( 'http://pressbooks.com/foo/BAR/?hello=world&TESTING=123', \PressBooks\Sanitize\canonicalize_url( $url ) );

		$url = 'MAILTO:^accepts�!mostly,garb@ge.../';
		$this->assertEquals( 'MAILTO:^accepts!mostly,garb@ge...', \PressBooks\Sanitize\canonicalize_url( $url ) );

		$url = 'mailto:miranda@yourcompany.com?bcc=eventsteam@yourcompany.com&subject=Excited%20to%20meet%20at%20the%20event!&body=Hi%20Miranda,';
		$this->assertEquals( $url, \PressBooks\Sanitize\canonicalize_url( $url ) );
	}


	/**
	 * @covers \PressBooks\Sanitize\maybe_https
	 */
	public function test_maybe_https() {

		if ( isset( $_SERVER['HTTPS'] ) ) {
			$old = $_SERVER['HTTPS'];
		}

		$_SERVER['HTTPS'] = true;

		$url = 'http://pressbooks.com';
		$url = \PressBooks\Sanitize\maybe_https( $url );
		$this->assertStringStartsWith( 'https://', $url );

		$url = 'http://https.org';
		$url = \PressBooks\Sanitize\maybe_https( $url );
		$this->assertEquals( 'https://https.org', $url );

		$_SERVER['HTTPS'] = false;

		$url = 'http://pressbooks.com';
		$url = \PressBooks\Sanitize\maybe_https( $url );
		$this->assertStringStartsNotWith( 'https://', $url );

		$url = 'https://http.org';
		$url = \PressBooks\Sanitize\maybe_https( $url );
		$this->assertEquals( 'https://http.org', $url );

		if ( isset( $old ) ) $_SERVER['HTTPS'] = $old;
		else unset( $_SERVER['HTTPS'] );
	}


}