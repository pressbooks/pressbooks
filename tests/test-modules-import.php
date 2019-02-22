<?php


class ImportMock extends \Pressbooks\Modules\Import\Import {
	/**
	 * @group import
	 */
	function setCurrentImportOption( array $upload ) {
		return true;
	}

	/**
	 * @group import
	 */
	function import( array $current_import ) {
		return true;
	}
}


class Modules_ImportTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @var \ImportMock
	 * @group import
	 */
	protected $import;


	/**
	 * @group import
	 */
	public function setUp() {
		parent::setUp();
		$this->import = new \ImportMock();
	}

	/**
	 * @group import
	 */
	public function test_revokeCurrentImport() {
		$this->assertTrue( is_bool( $this->import->revokeCurrentImport() ) );
	}

	/**
	 * @group import
	 */
	public function test_createTmpFile() {

		$file = $this->import->createTmpFile();
		$this->assertFileExists( $file );

		file_put_contents( $file, 'Hello world!' );
		$this->assertEquals( 'Hello world!', file_get_contents( $file ) );
	}

	/**
	 * @group import
	 */
	public function test_isFormSubmission() {

		$this->assertFalse( $this->import::isFormSubmission() );

		$_REQUEST['page'] = 'pb_import';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertTrue( $this->import::isFormSubmission() );
	}

}
