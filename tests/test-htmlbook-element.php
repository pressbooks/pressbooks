<?php

class HTMLBookTest extends \WP_UnitTestCase {

	public function test_isInline() {
		$e = new \Pressbooks\HTMLBook\Element();
		$f = new \Pressbooks\HTMLBook\Element();
		$f->setTag( 'ruby' );

		$this->assertTrue( $e->isInline( $f ) );
		$this->assertTrue( $e->isInline( '<ruby>漢 <rt>ㄏㄢˋ</rt></ruby>' ) );
		$this->assertFalse( $e->isInline( '<ruby>漢 <rt>ㄏㄢˋ</rt></ruby><p>How are you?</p>' ) );

		$fn = new \Pressbooks\HTMLBook\Inline\Footnote();
		$p = new \Pressbooks\HTMLBook\Block\Paragraph();

		$this->assertTrue( $e->isInline( $fn ) );
		$this->assertFalse( $e->isInline( $p ) );
	}

	public function test_isBlock() {
		$e = new \Pressbooks\HTMLBook\Element();
		$f = new \Pressbooks\HTMLBook\Element();
		$f->setTag( 'blockquote' );

		$this->assertTrue( $e->isBlock( $f ) );
		$this->assertTrue( $e->isBlock( '<blockquote>Hello World!</blockquote>' ) );
		$this->assertTrue( $e->isBlock( '<blockquote><p><strong>Hello</strong> <em>World!</em></p></blockquote>' ) );
		$this->assertFalse( $e->isBlock( '<blockquote>Hello World!</blockquote><p>How are you?</p>' ) );

		$p = new \Pressbooks\HTMLBook\Block\Paragraph();
		$fn = new \Pressbooks\HTMLBook\Inline\Footnote();

		$this->assertTrue( $e->isBlock( $p ) );
		$this->assertFalse( $e->isBlock( $fn ) );
	}

	public function test_isHeading() {
		$e = new \Pressbooks\HTMLBook\Element();
		$f = new \Pressbooks\HTMLBook\Element();
		$f->setTag( 'h1' );

		$this->assertTrue( $e->isHeading( $f ) );
		$this->assertTrue( $e->isHeading( '<h1>Hello World!</h1>' ) );
		$this->assertTrue( $e->isHeading( '<h1><strong>Hello</strong> <em>World!</em></h1>' ) );

		$this->assertTrue( $e->isHeading( '<h2>Hello World!</h2>' ) );
		$this->assertTrue( $e->isHeading( '<h3>Hello World!</h3>' ) );
		$this->assertTrue( $e->isHeading( '<h4>Hello World!</h4>' ) );
		$this->assertTrue( $e->isHeading( '<h5>Hello World!</h5>' ) );
		$this->assertTrue( $e->isHeading( '<h6>Hello World!</h6>' ) );

		$this->assertFalse( $e->isHeading( '<header>Hello World!</header>' ) );
		$this->assertFalse( $e->isHeading( '<h1>Hello World!</h1><p>How are you?</p>' ) );

		$h1 = new \Pressbooks\HTMLBook\Heading\H1();
		$h2 = new \Pressbooks\HTMLBook\Heading\H2();
		$h3 = new \Pressbooks\HTMLBook\Heading\H3();
		$h4 = new \Pressbooks\HTMLBook\Heading\H4();
		$h5 = new \Pressbooks\HTMLBook\Heading\H5();
		$h6 = new \Pressbooks\HTMLBook\Heading\H6();
		$header = new \Pressbooks\HTMLBook\Heading\Header();

		$this->assertTrue( $e->isHeading( $h1 ) );
		$this->assertTrue( $e->isHeading( $h2 ) );
		$this->assertTrue( $e->isHeading( $h3 ) );
		$this->assertTrue( $e->isHeading( $h4 ) );
		$this->assertTrue( $e->isHeading( $h5 ) );
		$this->assertTrue( $e->isHeading( $h6 ) );

		$this->assertFalse( $e->isHeading( $header ) );
	}

	public function test_Block_CodeListings() {

		$e = new \Pressbooks\HTMLBook\Block\CodeListings();
		$e->setCodeLanguage( 'php' );
		$e->appendAttribute( 'foo="bar"' );
		$e->appendContent( 'Hi!' );

		$this->assertEquals( '<pre data-type="programlisting" foo="bar" data-code-language="php">Hi!</pre>', (string) $e );
	}
}
