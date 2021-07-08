<?php

require_once( PB_PLUGIN_DIR . 'inc/modules/export/namespace.php' );

class Modules_ExportTest extends \WP_UnitTestCase {

	use utilsTrait;

	/**
	 * @group export
	 */
	public function test_dependency_errors() {
		$errors = \Pressbooks\Modules\Export\dependency_errors();
		$this->assertTrue( is_array( $errors ) );
	}

	/**
	 * @group export
	 */
	public function test_dependency_errors_msg() {
		$error = \Pressbooks\Modules\Export\dependency_errors_msg();
		$this->assertTrue( is_string( $error ) );
	}

	/**
	 * @group export
	 */
	public function test_formats() {
		$formats = \Pressbooks\Modules\Export\formats();
		$this->assertArrayHasKey( 'standard', $formats );
		$this->assertArrayHasKey( 'exotic', $formats );
		$this->assertTrue( is_array( $formats['standard'] ) );
		$this->assertTrue( is_array( $formats['exotic'] ) );
	}

	/**
	 * @group export
	 */
	public function test_filetypes() {
		$filetypes = \Pressbooks\Modules\Export\filetypes();
		$this->assertArrayHasKey( 'print_pdf', $filetypes );
		foreach ( $filetypes as $type => $extension ) {
			$this->assertStringStartsWith( '.', $extension );
		}
	}

	/**
	 * @group export
	 */
	public function test_get_name_from_filetype_slug() {
		$type = \Pressbooks\Modules\Export\get_name_from_filetype_slug( 'print_pdf' );
		$this->assertEquals( 'Print PDF', $type );
		$type = \Pressbooks\Modules\Export\get_name_from_filetype_slug( 'wtfbbq' );
		$this->assertEquals( 'Wtfbbq', $type );
	}

	/**
	 * @group export
	 */
	public function test_get_name_from_module_classname() {
		$type = \Pressbooks\Modules\Export\get_name_from_module_classname( '\Pressbooks\Modules\Export\Prince\Pdf' );
		$this->assertEquals( 'Digital PDF', $type );
		$type = \Pressbooks\Modules\Export\get_name_from_module_classname( '\Pressbooks\Modules\Export\Word\Docx' );
		$this->assertEquals( 'Docx', $type );
	}

	/**
	 * @group export
	 */
	public function test_template_data() {
		$data = \Pressbooks\Modules\Export\template_data();
		$this->assertArrayHasKey( 'export_form_url', $data );
		$this->assertArrayHasKey( 'formats', $data );
	}

	/**
	 * @group export
	 */
	public function test_isFormSubmission() {

		$this->assertFalse( \Pressbooks\Modules\Export\Export::isFormSubmission() );

		$_REQUEST['page'] = 'pb_export';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertTrue( \Pressbooks\Modules\Export\Export::isFormSubmission() );
		unset( $_REQUEST['page'], $_SERVER['REQUEST_METHOD'] );

		// Assert that EventSource (Progress bar) returns true, import code works differently than export code
		$reporting = $this->_fakeAjax();
		$_REQUEST['action'] = 'export-book';
		$this->assertTrue( \Pressbooks\Modules\Export\Export::isFormSubmission() );
		$this->_fakeAjaxDone( $reporting );
		unset( $_REQUEST['action'] );
	}
}
