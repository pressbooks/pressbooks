<?php

use Pressbooks\Modules\Export\Export;
use function Pressbooks\Modules\Export\dependency_errors;
use function Pressbooks\Modules\Export\dependency_errors_msg;
use function Pressbooks\Modules\Export\filetypes;
use function Pressbooks\Modules\Export\formats;
use function Pressbooks\Modules\Export\get_name_from_filetype_slug;
use function Pressbooks\Modules\Export\get_name_from_module_classname;
use function Pressbooks\Modules\Export\get_shortname_from_filetype_slug;
use function Pressbooks\Modules\Export\is_form_submission;
use function Pressbooks\Modules\Export\template_data;

require_once( PB_PLUGIN_DIR . 'inc/modules/export/namespace.php' );

class Modules_ExportTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @group export
	 */
	public function test_dependency_errors() {
		$errors = dependency_errors();
		$this->assertTrue( is_array( $errors ) );
	}

	/**
	 * @group export
	 */
	public function test_dependency_errors_msg() {
		$error = dependency_errors_msg();
		$this->assertTrue( is_string( $error ) );
	}

	/**
	 * @group export
	 */
	public function test_formats() {
		$formats = formats();
		$this->assertArrayHasKey( 'standard', $formats );
		$this->assertArrayHasKey( 'exotic', $formats );
		$this->assertTrue( is_array( $formats['standard'] ) );
		$this->assertTrue( is_array( $formats['exotic'] ) );
	}

	/**
	 * @group export
	 */
	public function test_filetypes() {
		$filetypes = filetypes();
		$this->assertArrayHasKey( 'print_pdf', $filetypes );
		foreach ( $filetypes as $type => $extension ) {
			$this->assertStringStartsWith( '.', $extension );
		}
	}

	/**
	 * @group export
	 */
	public function test_get_name_from_filetype_slug() {
		$type = get_name_from_filetype_slug( 'print_pdf' );
		$this->assertEquals( 'Print PDF', $type );
		$type = get_name_from_filetype_slug( 'wtfbbq' );
		$this->assertEquals( 'Wtfbbq', $type );
		$type = get_name_from_filetype_slug( 'thincc11' );
		$this->assertEquals( 'Common Cartridge (LTI Links)', $type );
	}

	/**
	 * @group export
	 */
	public function test_get_shortname_from_filetype_slug() {
		$type = get_shortname_from_filetype_slug( 'print_pdf' );
		$this->assertEquals( 'Print PDF', $type );
		$type = get_shortname_from_filetype_slug( 'wtfbbq' );
		$this->assertEquals( 'Wtfbbq', $type );
		$type = get_shortname_from_filetype_slug( 'thincc11' );
		$this->assertEquals( 'IMSCC', $type );
	}

	/**
	 * @group export
	 */
	public function test_get_name_from_module_classname() {
		$type = get_name_from_module_classname( '\Pressbooks\Modules\Export\Prince\Pdf' );
		$this->assertEquals( 'Digital PDF', $type );
		$type = get_name_from_module_classname( '\Pressbooks\Modules\Export\Word\Docx' );
		$this->assertEquals( 'Docx', $type );
	}

	/**
	 * @group export
	 */
	public function test_template_data() {
		$data = template_data();
		$this->assertArrayHasKey( 'export_form_url', $data );
		$this->assertArrayHasKey( 'formats', $data );
	}

	/**
	 * @group export
	 */
	public function test_isFormSubmission() {
		$this->assertFalse( is_form_submission() );

		$_REQUEST['page'] = 'pb_export';
		$_SERVER['REQUEST_METHOD'] = 'POST';
		$this->assertTrue( is_form_submission() );
		unset( $_REQUEST['page'], $_SERVER['REQUEST_METHOD'] );

		// Assert that EventSource (Progress bar) returns true, import code works differently than export code
		$reporting = $this->_fakeAjax();
		$_REQUEST['action'] = 'export-book';
		$this->assertTrue( is_form_submission() );
		$this->_fakeAjaxDone( $reporting );
		unset( $_REQUEST['action'] );
	}
}
