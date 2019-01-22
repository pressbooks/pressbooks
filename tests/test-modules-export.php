<?php

require_once( PB_PLUGIN_DIR . 'inc/modules/export/namespace.php' );

class Modules_ExportTest extends \WP_UnitTestCase {

	public function test_dependency_errors() {
		$errors = \Pressbooks\Modules\Export\dependency_errors();
		$this->assertTrue( is_array( $errors ) );
	}

	public function test_dependency_errors_msg() {
		$error = \Pressbooks\Modules\Export\dependency_errors_msg();
		$this->assertTrue( is_string( $error ) );
	}

	public function test_formats() {
		$formats = \Pressbooks\Modules\Export\formats();
		$this->assertArrayHasKey( 'standard', $formats );
		$this->assertArrayHasKey( 'exotic', $formats );
		$this->assertTrue( is_array( $formats['standard'] ) );
		$this->assertTrue( is_array( $formats['exotic'] ) );
	}

	public function test_filetypes() {
		$filetypes = \Pressbooks\Modules\Export\filetypes();
		$this->assertArrayHasKey( 'print_pdf', $filetypes );
		foreach ( $filetypes as $type => $extension ) {
			$this->assertStringStartsWith( '.', $extension );
		}
	}

	public function get_name_for_filetype() {
		$type = \Pressbooks\Modules\Export\get_name_for_filetype( 'print-pdf' );
		$this->assertEquals( 'Print PDF', $type );
		$type = \Pressbooks\Modules\Export\get_name_for_filetype( 'wtfbbq' );
		$this->assertEquals( 'Wtfbbq', $type );
	}

	public function test_template_data() {
		$data = \Pressbooks\Modules\Export\template_data();
		$this->assertArrayHasKey( 'export_form_url', $data );
		$this->assertArrayHasKey( 'formats', $data );
	}
}
