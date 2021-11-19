<?php

class Modules_Import_OoxmlTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \Pressbooks\Modules\Import\Ooxml\Docx
	 */
	protected $docx;

	/**
	 * @var array file upload
	 */
	protected $upload;

	/**
	 * @var string
	 * @group import
	 */
	protected $mock_docx;

	/**
	 * @group import
	 */
	public function set_up() {
		parent::set_up();

		$this->docx = new Pressbooks\Modules\Import\Ooxml\Docx();

		$this->upload = [
			'file' => __DIR__ . '/data/OnlineWordImportTest.docx',
			'url'  => null,
			'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		];

		$this->mock_docx = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:footnotes xmlns:wpc="http://schemas.microsoft.com/office/word/2010/wordprocessingCanvas" xmlns:cx="http://schemas.microsoft.com/office/drawing/2014/chartex" xmlns:cx1="http://schemas.microsoft.com/office/drawing/2015/9/8/chartex" xmlns:cx2="http://schemas.microsoft.com/office/drawing/2015/10/21/chartex" xmlns:cx3="http://schemas.microsoft.com/office/drawing/2016/5/9/chartex" xmlns:cx4="http://schemas.microsoft.com/office/drawing/2016/5/10/chartex" xmlns:cx5="http://schemas.microsoft.com/office/drawing/2016/5/11/chartex" xmlns:cx6="http://schemas.microsoft.com/office/drawing/2016/5/12/chartex" xmlns:cx7="http://schemas.microsoft.com/office/drawing/2016/5/13/chartex" xmlns:cx8="http://schemas.microsoft.com/office/drawing/2016/5/14/chartex" xmlns:mc="http://schemas.openxmlformats.org/markup-compatibility/2006" xmlns:aink="http://schemas.microsoft.com/office/drawing/2016/ink" xmlns:am3d="http://schemas.microsoft.com/office/drawing/2017/model3d" xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships" xmlns:m="http://schemas.openxmlformats.org/officeDocument/2006/math" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:wp14="http://schemas.microsoft.com/office/word/2010/wordprocessingDrawing" xmlns:wp="http://schemas.openxmlformats.org/drawingml/2006/wordprocessingDrawing" xmlns:w10="urn:schemas-microsoft-com:office:word" xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main" xmlns:w14="http://schemas.microsoft.com/office/word/2010/wordml" xmlns:w15="http://schemas.microsoft.com/office/word/2012/wordml" xmlns:w16cid="http://schemas.microsoft.com/office/word/2016/wordml/cid" xmlns:w16se="http://schemas.microsoft.com/office/word/2015/wordml/symex" xmlns:wpg="http://schemas.microsoft.com/office/word/2010/wordprocessingGroup" xmlns:wpi="http://schemas.microsoft.com/office/word/2010/wordprocessingInk" xmlns:wne="http://schemas.microsoft.com/office/word/2006/wordml" xmlns:wps="http://schemas.microsoft.com/office/word/2010/wordprocessingShape" mc:Ignorable="w14 w15 w16se w16cid wp14"><w:footnote w:type="separator" w:id="-1"><w:p w14:paraId="16441DB0" w14:textId="77777777" w:rsidR="00780114" w:rsidRDefault="00780114" w:rsidP="00DD1971"><w:pPr><w:spacing w:after="0" w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:separator/></w:r></w:p></w:footnote><w:footnote w:type="continuationSeparator" w:id="0"><w:p w14:paraId="2CAB4FB1" w14:textId="77777777" w:rsidR="00780114" w:rsidRDefault="00780114" w:rsidP="00DD1971"><w:pPr><w:spacing w:after="0" w:line="240" w:lineRule="auto"/></w:pPr><w:r><w:continuationSeparator/></w:r></w:p></w:footnote><w:footnote w:id="1"><w:p w14:paraId="612A1E82" w14:textId="0911E913" w:rsidR="00DD1971" w:rsidRDefault="00DD1971"><w:pPr><w:pStyle w:val="FootnoteText"/></w:pPr><w:r><w:rPr><w:rStyle w:val="FootnoteReference"/></w:rPr><w:footnoteRef/></w:r><w:r><w:t xml:space="preserve"> I’m the footnote text.</w:t></w:r></w:p></w:footnote><w:footnote w:id="2"><w:p w14:paraId="0813D26F" w14:textId="751A878E" w:rsidR="00DD1971" w:rsidRPr="00DD1971" w:rsidRDefault="00DD1971"><w:pPr><w:pStyle w:val="FootnoteText"/></w:pPr><w:r><w:rPr><w:rStyle w:val="FootnoteReference"/></w:rPr><w:footnoteRef/></w:r><w:r><w:t xml:space="preserve"> This is the title of a book: </w:t></w:r><w:r><w:rPr><w:i/><w:iCs/></w:rPr><w:t>Book Title</w:t></w:r><w:r><w:t>. It was written in italics.</w:t></w:r></w:p></w:footnote><w:footnote w:id="3"><w:p w14:paraId="4E8C66E3" w14:textId="499AEDDF" w:rsidR="00DD1971" w:rsidRPr="00DD1971" w:rsidRDefault="00DD1971"><w:pPr><w:pStyle w:val="FootnoteText"/></w:pPr><w:r><w:rPr><w:rStyle w:val="FootnoteReference"/></w:rPr><w:footnoteRef/></w:r><w:r><w:t xml:space="preserve"> I’m going to include </w:t></w:r><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t xml:space="preserve">bold </w:t></w:r><w:r><w:t>for emphasis.</w:t></w:r></w:p></w:footnote><w:footnote w:id="4"><w:p w14:paraId="2FC57D1E" w14:textId="3060F2C6" w:rsidR="00DD1971" w:rsidRDefault="00DD1971" w:rsidP="00DD1971"><w:pPr><w:pStyle w:val="FootnoteText"/></w:pPr><w:r><w:rPr><w:rStyle w:val="FootnoteReference"/></w:rPr><w:footnoteRef/></w:r><w:r><w:t xml:space="preserve"> I’m the footnote text</w:t></w:r><w:r><w:t xml:space="preserve"> for another footnote</w:t></w:r><w:r><w:t>.</w:t></w:r></w:p></w:footnote><w:footnote w:id="5"><w:p w14:paraId="465DAD95" w14:textId="68801B24" w:rsidR="00DD1971" w:rsidRPr="00DD1971" w:rsidRDefault="00DD1971" w:rsidP="00DD1971"><w:pPr><w:pStyle w:val="FootnoteText"/></w:pPr><w:r><w:rPr><w:rStyle w:val="FootnoteReference"/></w:rPr><w:footnoteRef/></w:r><w:r><w:t xml:space="preserve"> This is the title of a</w:t></w:r><w:r><w:t>nother</w:t></w:r><w:r><w:t xml:space="preserve"> book: </w:t></w:r><w:r><w:rPr><w:i/><w:iCs/></w:rPr><w:t xml:space="preserve">Amazing </w:t></w:r><w:r><w:rPr><w:i/><w:iCs/></w:rPr><w:t>Book</w:t></w:r><w:r><w:t>. It was written in italics.</w:t></w:r></w:p></w:footnote><w:footnote w:id="6"><w:p w14:paraId="12D90420" w14:textId="36ED3DB2" w:rsidR="00DD1971" w:rsidRPr="00DD1971" w:rsidRDefault="00DD1971" w:rsidP="00DD1971"><w:pPr><w:pStyle w:val="FootnoteText"/></w:pPr><w:r><w:rPr><w:rStyle w:val="FootnoteReference"/></w:rPr><w:footnoteRef/></w:r><w:r><w:t xml:space="preserve"> I’m </w:t></w:r><w:r><w:t xml:space="preserve">including </w:t></w:r><w:r><w:rPr><w:b/><w:bCs/></w:rPr><w:t>bold</w:t></w:r><w:r w:rsidRPr="00DD1971"><w:rPr><w:b/><w:bCs/></w:rPr><w:t xml:space="preserve"> for emphasis</w:t></w:r><w:r><w:t>.</w:t></w:r></w:p></w:footnote></w:footnotes>';

	}

	/**
	 * @group import
	 */
	public function test_setCurrentImportOption() {
		// not a zip
		$not_a_zip = [ 'file' => __DIR__ . '/data/mountains.jpg' ];
		$nope      = $this->docx->setCurrentImportOption( $not_a_zip );
		$this->assertFalse( $nope );

		// happy path for Word doc saved online
		$yes_online     = $this->docx->setCurrentImportOption( $this->upload );
		$current_import = get_option( 'pressbooks_current_import' );

		// assertions for Word doc saved online
		$this->assertTrue( $yes_online );
		$this->assertEquals( $this->upload['file'], $current_import['file'] );
		$this->assertArrayHasKey( 'chapters', $current_import );
		$this->assertEquals( 'Chapter One', $current_import['chapters'][0] );
		$this->assertEquals( 'Chapter Two', $current_import['chapters'][1] );

		// happy path for Word doc saved locally
		$this->upload['file'] = __DIR__ . '/data/WordImportTest.docx';
		$yes_local            = $this->docx->setCurrentImportOption( $this->upload );
		$current_import       = get_option( 'pressbooks_current_import' );

		// assertions for Word doc saved locally
		$this->assertTrue( $yes_local );
		$this->assertEquals( __DIR__ . '/data/WordImportTest.docx', $current_import['file'] );
		$this->assertArrayHasKey( 'chapters', $current_import );
		$this->assertEquals( 'Chapter 1', $current_import['chapters'][0] );
		$this->assertEquals( 'Chapter 2', $current_import['chapters'][1] );
	}

	/**
	 * @group import
	 */
	public function test_import() {
		// necessary for getChapterParent()
		$this->_book();

		// set the import option for Online Word doc
		copy( __DIR__ . '/data/OnlineWordImportTest.docx', __DIR__ . '/data/deleteMe.docx' );
		$this->upload['file'] = __DIR__ . '/data/deleteMe.docx';
		$this->docx->setCurrentImportOption( $this->upload );
		$current_import = get_option( 'pressbooks_current_import' );

		// happy path for Online Word doc
		$yes_online = $this->docx->import( $current_import );
		$this->assertTrue( $yes_online );
		$this->assertFileNotExists( __DIR__ . '/data/deleteMe.docx' );

		// set the import option for local Word doc
		copy( __DIR__ . '/data/WordImportTest.docx', __DIR__ . '/data/deleteMe.docx' );
		$this->upload['file'] = __DIR__ . '/data/deleteMe.docx';
		$this->docx->setCurrentImportOption( $this->upload );
		$current_import = get_option( 'pressbooks_current_import' );

		// happy path for local Word doc
		$yes_local = $this->docx->import( $current_import );
		$this->assertTrue( $yes_local );
		$this->assertFileNotExists( __DIR__ . '/data/deleteMe.docx' );

	}

	/**
	 * @group import
	 */
	public function test_getFootnotesStyles() {
		$chapter = new \DOMDocument( '1.0', 'UTF-8' );
		$chapter->loadXML( $this->mock_docx );
		$dom_elem = $chapter->documentElement;
		$text_tags = $dom_elem->getElementsByTagName( 'footnote' );
		$ids = [ 1, 2, 3, 4, 5, 6 ];

		$import_class = new \ReflectionClass( 'Pressbooks\Modules\Import\Ooxml\Docx' );
		$footnotes_style = $import_class->getMethod( 'getFootnotesStyles' );
		$footnotes_style->setAccessible( true );
		$array_styles = $footnotes_style->invokeArgs( $this->docx, [ $text_tags, $ids ] );
		$this->assertGreaterThan( 0, count( $array_styles ) );
	}

	/**
	 * @group import
	 */
	public function test_addFootnotesToDOM() {
		$chapter = new \DOMDocument( '1.0', 'UTF-8' );
		$footnotes_string = '<?xml version="1.0" encoding="UTF-8"?><div class="chapter-one-title"><p xmlns="http://www.w3.org/1999/xhtml" class="import-Normal">I’m a paragraph.<sup class="import-FootnoteReference"><a name="sdfootnote1anc" id="sdfootnote1anc" href="#sdfootnote1sym">1</a></sup> I’m writing about nothing in particular.</p><p xmlns="http://www.w3.org/1999/xhtml" class="import-Normal">I’m a second paragraph.<sup class="import-FootnoteReference"><a name="sdfootnote2anc" id="sdfootnote2anc" href="#sdfootnote2sym">2</a></sup> This is the second sentence in that new paragraph. I don’t know what else to say.<sup class="import-FootnoteReference"><a name="sdfootnote3anc" id="sdfootnote3anc" href="#sdfootnote3sym">3</a></sup></p></div>';
		$chapter->loadXML( $footnotes_string );
		$fn_ids = [ 1, 2, 3 ];
		$fn_notes = [
			1 => 'I’m the footnote text.',
			2 => 'This is the title of a book: Book Title. It was written in italics.',
			3 => 'I’m going to include bold for emphasis.',
		];
		$fn_styles = [
			2 => [
				[
					'style' => 'i',
					'texts' => [ 'Book Title' ],
				],
			],
			3 => [
				[
					'style' => 'b',
					'texts' => [ 'bold' ],
				],
			],
		];

		$import_class = new \ReflectionClass( 'Pressbooks\Modules\Import\Ooxml\Docx' );
		$footnotes_to_dom = $import_class->getMethod( 'addFootnotesToDOM' );
		$footnotes_to_dom->setAccessible( true );
		$fn_property = $import_class->getProperty( 'fn' );
		$fn_property->setAccessible( true );
		$fn_property->setValue( $this->docx, $fn_notes );
		$fn__style_property = $import_class->getProperty( 'fn_styles' );
		$fn__style_property->setAccessible( true );
		$fn__style_property->setValue( $this->docx, $fn_styles );

		$chapter_with_fn = $footnotes_to_dom->invokeArgs( $this->docx, [ $chapter, $fn_ids ] );
		$italics = $chapter_with_fn->getelementsByTagName( 'i' );
		$bolds = $chapter_with_fn->getelementsByTagName( 'b' );

		$this->assertEquals( 1, $italics->length );
		$this->assertEquals( 1, $bolds->length );
	}
}
