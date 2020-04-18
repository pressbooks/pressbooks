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

		// happy path
		$this->docx->setCurrentImportOption( $this->upload );
		$current_import = get_option( 'pressbooks_current_import' );

		// assertions
		$this->assertEquals( $this->upload['file'], $current_import['file'] );
		$this->assertArrayHasKey( 'chapters', $current_import );
		$this->assertEquals( 'Chapter One', $current_import['chapters'][0] );
		$this->assertEquals( 'Chapter Two', $current_import['chapters'][1] );

	}

	/**
	 * @group import
	 */
	public function test_import() {
		// necessary for getChapterParent()
		$this->_book();

		// set the import option
		$this->docx->setCurrentImportOption( $this->upload );
		$current_import = get_option( 'pressbooks_current_import' );

		// Import process _revokeCurrentImport() deletes the file. Create a tmp file
		copy( __DIR__ . '/data/OnlineWordImportTest.docx', __DIR__ . '/data/deleteMe.docx' );
		$current_import['file'] = __DIR__ . '/data/deleteMe.docx';
		update_option( 'pressbooks_current_import', $current_import );

		// happy path
		$here_be_zip = $this->docx->import( $current_import );
		$this->assertTrue( $here_be_zip );

	}

}

