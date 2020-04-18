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
	 * @group import
	 */
	public function setUp() {
		parent::setUp();

		$this->docx = new Pressbooks\Modules\Import\Ooxml\Docx();

		$this->upload = [
			'file' => __DIR__ . '/data/OnlineWordImportTest.docx',
			'url'  => null,
			'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		];

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

		// set the import option for local Word
		copy( __DIR__ . '/data/WordImportTest.docx', __DIR__ . '/data/deleteMe.docx' );
		$this->upload['file'] = __DIR__ . '/data/deleteMe.docx';
		$this->docx->setCurrentImportOption( $this->upload );
		$current_import = get_option( 'pressbooks_current_import' );

		// happy path for Online Word
		$yes_local = $this->docx->import( $current_import );
		$this->assertTrue( $yes_local );
		$this->assertFileNotExists( __DIR__ . '/data/deleteMe.docx' );

	}

}

