<?php

class HTMLBook_ElementTest extends \WP_UnitTestCase {

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

	public function test_Block() {
		$e = new \Pressbooks\HTMLBook\Block\Admonitions();
		$e->setDataType( 'important' );
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'div' );
		$this->assertEquals( '<div data-type="important">Hi!</div>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\Blockquote();
		$e->setDataType( 'epigraph' );
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'blockquote' );
		$this->assertEquals( '<blockquote data-type="epigraph">Hi!</blockquote>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\CodeListings();
		$e->setCodeLanguage( 'php' );
		$e->setAttributes( [ 'class="foobar"' ] );
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'pre' );
		$this->assertEquals( '<pre data-type="programlisting" class="foobar" data-code-language="php">Hi!</pre>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\DefinitionLists();
		$e->appendContent( 'Hi!' );
		$e->setAttributes( [ 'class' => 'foobar' ] );
		$this->assertEquals( $e->getTag(), 'dl' );
		$this->assertEquals( '<dl class="foobar">Hi!</dl>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\Equation();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'div' );
		$this->assertEquals( '<div data-type="equation">Hi!</div>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\Examples();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'div' );
		$this->assertEquals( '<div data-type="example">Hi!</div>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\Figures();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'figure' );
		$this->assertEquals( '<figure>Hi!</figure>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\ItemizedLists();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'ul' );
		$this->assertEquals( '<ul>Hi!</ul>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\OrderedLists();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'ol' );
		$this->assertEquals( '<ol>Hi!</ol>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\Paragraph();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'p' );
		$this->assertEquals( '<p>Hi!</p>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\ReferenceEntries();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'div' );
		$this->assertEquals( '<div class="refentry">Hi!</div>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\Sidebar();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'aside' );
		$this->assertEquals( '<aside data-type="sidebar">Hi!</aside>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Block\Tables();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'table' );
		$this->assertEquals( '<table>Hi!</table>', $e->render() );
	}

	public function test_Component() {
		$e = new \Pressbooks\HTMLBook\Component\Appendix();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'section' );
		$this->assertEquals( '<section data-type="appendix">Hi!</section>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Component\Backmatter();
		$e->setDataType( 'conclusion' );
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'section' );
		$this->assertEquals( '<section data-type="conclusion">Hi!</section>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Component\Bibliography();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'section' );
		$this->assertEquals( '<section data-type="bibliography">Hi!</section>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Component\Book();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'body' );
		$this->assertEquals( '<body data-type="book">Hi!</body>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Component\Chapter();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'section' );
		$this->assertEquals( '<section data-type="chapter">Hi!</section>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Component\Frontmatter();
		$e->appendContent( 'Hi!' );
		$e->setDataType( 'dedication' );
		$this->assertEquals( $e->getTag(), 'section' );
		$this->assertEquals( '<section data-type="dedication">Hi!</section>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Component\Glossary();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'section' );
		$this->assertEquals( '<section data-type="glossary">Hi!</section>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Component\Index();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'section' );
		$this->assertEquals( '<section data-type="index">Hi!</section>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Component\Part();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'div' );
		$this->assertEquals( '<div data-type="part">Hi!</div>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Component\Preface();
		$e->setDataType( 'introduction' );
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'section' );
		$this->assertEquals( '<section data-type="introduction">Hi!</section>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Component\Sections();
		$e->setDataType( 'sect5' );
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'section' );
		$this->assertEquals( '<section data-type="sect5">Hi!</section>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Component\TableOfContents();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'nav' );
		$this->assertEquals( '<nav data-type="toc">Hi!</nav>', $e->render() );
	}

	public function test_Heading() {
		$e = new \Pressbooks\HTMLBook\Heading\H1();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'h1' );
		$this->assertEquals( '<h1>Hi!</h1>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Heading\H2();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'h2' );
		$this->assertEquals( '<h2>Hi!</h2>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Heading\H3();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'h3' );
		$this->assertEquals( '<h3>Hi!</h3>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Heading\H4();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'h4' );
		$this->assertEquals( '<h4>Hi!</h4>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Heading\H5();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'h5' );
		$this->assertEquals( '<h5>Hi!</h5>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Heading\H6();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'h6' );
		$this->assertEquals( '<h6>Hi!</h6>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Heading\Header();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'header' );
		$this->assertEquals( '<header>Hi!</header>', $e->render() );
	}

	public function test_Inline() {
		$e = new \Pressbooks\HTMLBook\Inline\CrossReferences();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'a' );
		$this->assertEquals( '<a data-type="xref">Hi!</a>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Inline\Emphasis();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'em' );
		$this->assertEquals( '<em>Hi!</em>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Inline\Footnote();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'span' );
		$this->assertEquals( '<span data-type="footnote">Hi!</span>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Inline\General();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'span' );
		$this->assertEquals( '<span>Hi!</span>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Inline\IndexTerm();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'a' );
		$this->assertEquals( '<a data-type="indexterm">Hi!</a>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Inline\Literal();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'code' );
		$this->assertEquals( '<code>Hi!</code>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Inline\Strong();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'strong' );
		$this->assertEquals( '<strong>Hi!</strong>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Inline\Subscripts();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'sub' );
		$this->assertEquals( '<sub>Hi!</sub>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Inline\Superscripts();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'sup' );
		$this->assertEquals( '<sup>Hi!</sup>', $e->render() );
	}

	public function test_Interactive() {
		$e = new \Pressbooks\HTMLBook\Interactive\Audio();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'audio' );
		$this->assertEquals( '<audio>Hi!</audio>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Interactive\Canvas();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'canvas' );
		$this->assertEquals( '<canvas>Hi!</canvas>', $e->render() );

		$e = new \Pressbooks\HTMLBook\Interactive\Video();
		$e->appendContent( 'Hi!' );
		$this->assertEquals( $e->getTag(), 'video' );
		$this->assertEquals( '<video>Hi!</video>', $e->render() );
	}
}
