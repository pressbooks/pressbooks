<?php

require_once( PB_PLUGIN_DIR . 'inc/admin/customfonts/namespace.php' );

use Pressbooks\Container;
use Pressbooks\ServiceProvider;

class Admin_CustomFontsTest extends \WP_UnitTestCase {
	use utilsTrait;

	private $test_font_dir;
	private $original_upload_dir;

	/**
	 * Set up test environment
	 */
	public function set_up() {
		parent::set_up();

		// Create a temporary directory for font uploads
		$this->test_font_dir = $this->_createTmpDir() . '/custom-fonts/';
		wp_mkdir_p( $this->test_font_dir );

		// Mock the Blade service
		Container::set( 'Blade', function () {
			$stub = $this
				->getMockBuilder( '\Pressbooks\Blade' )
				->getMock();

			$stub
				->method( 'render' )
				->willReturn( '<div>Mock rendered template</div>' );

			return $stub;
		}, null, true );

		// Store original upload directory
		$this->original_upload_dir = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/';
	}

	/**
	 * Tear down test environment
	 */
	public function tear_down() {
		// Clean up test files
		if ( is_dir( $this->test_font_dir ) ) {
			$this->_rmdir( $this->test_font_dir );
		}

		// Clean up any created font files
		$upload_dir = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/';
		if ( is_dir( $upload_dir ) ) {
			$this->_rmdir( $upload_dir );
		}

		// Clean up site options
		delete_site_option( 'pressbooks_custom_fonts' );

		ServiceProvider::init();
		parent::tear_down();
	}

	/**
	 * Test render_custom_fonts_page function
	 */
	public function test_render_custom_fonts_page() {
		// Set up test data
		$test_fonts = [
			'test-font' => [
				'name' => 'Test Font',
				'fallback' => 'sans-serif',
				'files' => [
					'regular' => [
						'file' => 'http://example.com/test-font.woff',
						'variation' => 'regular',
					],
				],
			],
		];
		update_site_option( 'pressbooks_custom_fonts', $test_fonts );

		// Capture output
		ob_start();
		\Pressbooks\Admin\CustomFonts\render_custom_fonts_page();
		$output = ob_get_clean();

		// Assert output contains expected content
		$this->assertStringContainsString( 'Mock rendered template', $output );
	}

	/**
	 * Test handle_form_submission with valid data
	 */
	public function test_handle_form_submission_valid_data() {
		// Create a super admin user
		$user_id = $this->createSuperAdminUser();
		wp_set_current_user( $user_id );

		// Mock file upload data
		$test_file = [
			'name' => 'test-font.woff',
			'type' => 'font/woff',
			'tmp_name' => $this->test_font_dir . 'test-font.woff',
			'error' => 0,
			'size' => 1024,
		];

		// Create a test font file
		file_put_contents( $test_file['tmp_name'], 'test font content' );

		// Mock POST data
		$_POST = [
			'_wpnonce' => wp_create_nonce( 'pb_save_custom_fonts' ),
			'font_name' => 'Test Font',
			'font_fallback' => 'sans-serif',
		];

		$_FILES = [
			'font_file_regular' => $test_file,
		];

		// Mock wp_safe_redirect to prevent actual redirect
		global $wp_actions;
		$wp_actions['wp_redirect'] = 1;

		// Capture any output
		ob_start();
		\Pressbooks\Admin\CustomFonts\handle_form_submission();
		$output = ob_get_clean();

		// Check that font was saved
		$fonts = get_site_option( 'pressbooks_custom_fonts', [] );
		$this->assertArrayHasKey( 'test-font', $fonts );
		$this->assertEquals( 'Test Font', $fonts['test-font']['name'] );
		$this->assertEquals( 'sans-serif', $fonts['test-font']['fallback'] );
		$this->assertArrayHasKey( 'regular', $fonts['test-font']['files'] );
	}

	/**
	 * Test handle_form_submission with invalid nonce
	 */
	public function test_handle_form_submission_invalid_nonce() {
		// Create a super admin user
		$user_id = $this->createSuperAdminUser();
		wp_set_current_user( $user_id );

		// Mock POST data with invalid nonce
		$_POST = [
			'_wpnonce' => 'invalid-nonce',
			'font_name' => 'Test Font',
			'font_fallback' => 'sans-serif',
		];

		// Expect the function to die with permission denied
		$this->expectOutputString( 'Permission denied' );
		\Pressbooks\Admin\CustomFonts\handle_form_submission();
	}

	/**
	 * Test handle_form_submission with insufficient permissions
	 */
	public function test_handle_form_submission_insufficient_permissions() {
		// Create a regular user (not super admin)
		$user_id = $this->createSubscriberUser();
		wp_set_current_user( $user_id );

		// Mock POST data
		$_POST = [
			'_wpnonce' => wp_create_nonce( 'pb_save_custom_fonts' ),
			'font_name' => 'Test Font',
			'font_fallback' => 'sans-serif',
		];

		// Expect the function to die with permission denied
		$this->expectOutputString( 'Permission denied' );
		\Pressbooks\Admin\CustomFonts\handle_form_submission();
	}

	/**
	 * Test handle_form_submission with invalid file type
	 */
	public function test_handle_form_submission_invalid_file_type() {
		// Create a super admin user
		$user_id = $this->createSuperAdminUser();
		wp_set_current_user( $user_id );

		// Mock file upload data with invalid file type
		$test_file = [
			'name' => 'test-font.txt',
			'type' => 'text/plain',
			'tmp_name' => $this->test_font_dir . 'test-font.txt',
			'error' => 0,
			'size' => 1024,
		];

		// Create a test file
		file_put_contents( $test_file['tmp_name'], 'test content' );

		// Mock POST data
		$_POST = [
			'_wpnonce' => wp_create_nonce( 'pb_save_custom_fonts' ),
			'font_name' => 'Test Font',
			'font_fallback' => 'sans-serif',
		];

		$_FILES = [
			'font_file_regular' => $test_file,
		];

		// Expect the function to die with error message
		$this->expectOutputString( 'Invalid font file type.' );
		\Pressbooks\Admin\CustomFonts\handle_form_submission();
	}

	/**
	 * Test handle_form_submission with upload failure
	 */
	public function test_handle_form_submission_upload_failure() {
		// Create a super admin user
		$user_id = $this->createSuperAdminUser();
		wp_set_current_user( $user_id );

		// Mock file upload data with non-existent tmp_name
		$test_file = [
			'name' => 'test-font.woff',
			'type' => 'font/woff',
			'tmp_name' => '/non/existent/path/test-font.woff',
			'error' => 0,
			'size' => 1024,
		];

		// Mock POST data
		$_POST = [
			'_wpnonce' => wp_create_nonce( 'pb_save_custom_fonts' ),
			'font_name' => 'Test Font',
			'font_fallback' => 'sans-serif',
		];

		$_FILES = [
			'font_file_regular' => $test_file,
		];

		// Expect the function to die with error message
		$this->expectOutputString( 'Font upload failed for regular' );
		\Pressbooks\Admin\CustomFonts\handle_form_submission();
	}

	/**
	 * Test handle_uploaded_font with valid file
	 */
	public function test_handle_uploaded_font_valid_file() {
		$test_file = [
			'name' => 'test-font.woff',
			'type' => 'font/woff',
			'tmp_name' => $this->test_font_dir . 'test-font.woff',
			'error' => 0,
			'size' => 1024,
		];

		// Create a test font file
		file_put_contents( $test_file['tmp_name'], 'test font content' );

		$result = \Pressbooks\Admin\CustomFonts\handle_uploaded_font( $test_file, 'regular', $this->test_font_dir );

		$this->assertIsArray( $result );
		$this->assertArrayHasKey( 'file', $result );
		$this->assertArrayHasKey( 'variation', $result );
		$this->assertEquals( 'regular', $result['variation'] );
		$this->assertStringContainsString( 'test-font.woff', $result['file'] );
	}

	/**
	 * Test handle_uploaded_font with invalid file type
	 */
	public function test_handle_uploaded_font_invalid_file_type() {
		$test_file = [
			'name' => 'test-font.txt',
			'type' => 'text/plain',
			'tmp_name' => $this->test_font_dir . 'test-font.txt',
			'error' => 0,
			'size' => 1024,
		];

		$result = \Pressbooks\Admin\CustomFonts\handle_uploaded_font( $test_file, 'regular', $this->test_font_dir );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'invalid_type', $result->get_error_code() );
		$this->assertEquals( 'Invalid font file type.', $result->get_error_message() );
	}

	/**
	 * Test handle_uploaded_font with upload failure
	 */
	public function test_handle_uploaded_font_upload_failure() {
		$test_file = [
			'name' => 'test-font.woff',
			'type' => 'font/woff',
			'tmp_name' => '/non/existent/path/test-font.woff',
			'error' => 0,
			'size' => 1024,
		];

		$result = \Pressbooks\Admin\CustomFonts\handle_uploaded_font( $test_file, 'regular', $this->test_font_dir );

		$this->assertInstanceOf( 'WP_Error', $result );
		$this->assertEquals( 'upload_failed', $result->get_error_code() );
		$this->assertEquals( 'Font upload failed for regular', $result->get_error_message() );
	}

	/**
	 * Test handle_uploaded_font with different file extensions
	 */
	public function test_handle_uploaded_font_different_extensions() {
		$allowed_extensions = [ 'woff', 'woff2', 'ttf', 'otf' ];

		foreach ( $allowed_extensions as $ext ) {
			$test_file = [
				'name' => "test-font.{$ext}",
				'type' => "font/{$ext}",
				'tmp_name' => $this->test_font_dir . "test-font.{$ext}",
				'error' => 0,
				'size' => 1024,
			];

			// Create a test font file
			file_put_contents( $test_file['tmp_name'], 'test font content' );

			$result = \Pressbooks\Admin\CustomFonts\handle_uploaded_font( $test_file, 'regular', $this->test_font_dir );

			$this->assertIsArray( $result );
			$this->assertArrayHasKey( 'file', $result );
			$this->assertStringContainsString( ".{$ext}", $result['file'] );
		}
	}

	/**
	 * Test generate_custom_font_css with no fonts
	 */
	public function test_generate_custom_font_css_no_fonts() {
		// Ensure no fonts are set
		delete_site_option( 'pressbooks_custom_fonts' );

		// This should not create any files
		\Pressbooks\Admin\CustomFonts\generate_custom_font_css();

		$css_file_path = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/_custom-fonts.scss';
		$this->assertFileDoesNotExist( $css_file_path );
	}

	/**
	 * Test generate_custom_font_css with fonts
	 */
	public function test_generate_custom_font_css_with_fonts() {
		// Set up test fonts
		$test_fonts = [
			'test-font' => [
				'name' => 'Test Font',
				'fallback' => 'sans-serif',
				'files' => [
					'regular' => [
						'file' => 'http://example.com/test-font.woff',
						'variation' => 'regular',
					],
					'bold' => [
						'file' => 'http://example.com/test-font-bold.woff',
						'variation' => 'bold',
					],
					'italic' => [
						'file' => 'http://example.com/test-font-italic.woff',
						'variation' => 'italic',
					],
					'bold_italic' => [
						'file' => 'http://example.com/test-font-bold-italic.woff',
						'variation' => 'bold_italic',
					],
				],
			],
		];
		update_site_option( 'pressbooks_custom_fonts', $test_fonts );

		// Create upload directory
		$upload_dir = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/';
		wp_mkdir_p( $upload_dir );

		\Pressbooks\Admin\CustomFonts\generate_custom_font_css();

		$css_file_path = $upload_dir . '_custom-fonts.scss';
		$this->assertFileExists( $css_file_path );

		$css_content = file_get_contents( $css_file_path );
		$this->assertStringContainsString( "@font-face", $css_content );
		$this->assertStringContainsString( "font-family: 'Test Font'", $css_content );
		$this->assertStringContainsString( "font-style: normal", $css_content );
		$this->assertStringContainsString( "font-weight: normal", $css_content );
		$this->assertStringContainsString( "font-style: normal", $css_content );
		$this->assertStringContainsString( "font-weight: bold", $css_content );
		$this->assertStringContainsString( "font-style: italic", $css_content );
		$this->assertStringContainsString( "font-weight: normal", $css_content );
		$this->assertStringContainsString( "font-style: italic", $css_content );
		$this->assertStringContainsString( "font-weight: bold", $css_content );
	}

	/**
	 * Test generate_custom_font_css with different file formats
	 */
	public function test_generate_custom_font_css_different_formats() {
		// Set up test fonts with different formats
		$test_fonts = [
			'test-font' => [
				'name' => 'Test Font',
				'fallback' => 'sans-serif',
				'files' => [
					'regular' => [
						'file' => 'http://example.com/test-font.woff2',
						'variation' => 'regular',
					],
					'bold' => [
						'file' => 'http://example.com/test-font.ttf',
						'variation' => 'bold',
					],
					'italic' => [
						'file' => 'http://example.com/test-font.otf',
						'variation' => 'italic',
					],
				],
			],
		];
		update_site_option( 'pressbooks_custom_fonts', $test_fonts );

		// Create upload directory
		$upload_dir = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/';
		wp_mkdir_p( $upload_dir );

		\Pressbooks\Admin\CustomFonts\generate_custom_font_css();

		$css_file_path = $upload_dir . '_custom-fonts.scss';
		$this->assertFileExists( $css_file_path );

		$css_content = file_get_contents( $css_file_path );
		$this->assertStringContainsString( "format('woff2')", $css_content );
		$this->assertStringContainsString( "format('truetype')", $css_content );
		$this->assertStringContainsString( "format('opentype')", $css_content );
	}

	/**
	 * Test generate_custom_font_css with unknown file extension
	 */
	public function test_generate_custom_font_css_unknown_extension() {
		// Set up test fonts with unknown extension
		$test_fonts = [
			'test-font' => [
				'name' => 'Test Font',
				'fallback' => 'sans-serif',
				'files' => [
					'regular' => [
						'file' => 'http://example.com/test-font.xyz',
						'variation' => 'regular',
					],
				],
			],
		];
		update_site_option( 'pressbooks_custom_fonts', $test_fonts );

		// Create upload directory
		$upload_dir = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/';
		wp_mkdir_p( $upload_dir );

		\Pressbooks\Admin\CustomFonts\generate_custom_font_css();

		$css_file_path = $upload_dir . '_custom-fonts.scss';
		$this->assertFileExists( $css_file_path );

		$css_content = file_get_contents( $css_file_path );
		// Should default to truetype for unknown extensions
		$this->assertStringContainsString( "format('truetype')", $css_content );
	}

	/**
	 * Test handle_form_submission updates existing font
	 */
	public function test_handle_form_submission_updates_existing_font() {
		// Create a super admin user
		$user_id = $this->createSuperAdminUser();
		wp_set_current_user( $user_id );

		// Set up existing font
		$existing_fonts = [
			'test-font' => [
				'name' => 'Test Font',
				'fallback' => 'serif',
				'files' => [
					'regular' => [
						'file' => 'http://example.com/old-font.woff',
						'variation' => 'regular',
					],
				],
			],
		];
		update_site_option( 'pressbooks_custom_fonts', $existing_fonts );

		// Mock file upload data
		$test_file = [
			'name' => 'new-font.woff',
			'type' => 'font/woff',
			'tmp_name' => $this->test_font_dir . 'new-font.woff',
			'error' => 0,
			'size' => 1024,
		];

		// Create a test font file
		file_put_contents( $test_file['tmp_name'], 'new font content' );

		// Mock POST data
		$_POST = [
			'_wpnonce' => wp_create_nonce( 'pb_save_custom_fonts' ),
			'font_name' => 'Test Font',
			'font_fallback' => 'sans-serif', // Changed fallback
		];

		$_FILES = [
			'font_file_bold' => $test_file, // Adding bold variant
		];

		// Mock wp_safe_redirect to prevent actual redirect
		global $wp_actions;
		$wp_actions['wp_redirect'] = 1;

		// Capture any output
		ob_start();
		\Pressbooks\Admin\CustomFonts\handle_form_submission();
		$output = ob_get_clean();

		// Check that font was updated
		$fonts = get_site_option( 'pressbooks_custom_fonts', [] );
		$this->assertArrayHasKey( 'test-font', $fonts );
		$this->assertEquals( 'sans-serif', $fonts['test-font']['fallback'] ); // Updated fallback
		$this->assertArrayHasKey( 'regular', $fonts['test-font']['files'] ); // Existing file
		$this->assertArrayHasKey( 'bold', $fonts['test-font']['files'] ); // New file
	}

	/**
	 * Test handle_form_submission creates directory if it doesn't exist
	 */
	public function test_handle_form_submission_creates_directory() {
		// Create a super admin user
		$user_id = $this->createSuperAdminUser();
		wp_set_current_user( $user_id );

		// Ensure directory doesn't exist
		$upload_dir = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/';
		if ( is_dir( $upload_dir ) ) {
			$this->_rmdir( $upload_dir );
		}

		// Mock file upload data
		$test_file = [
			'name' => 'test-font.woff',
			'type' => 'font/woff',
			'tmp_name' => $this->test_font_dir . 'test-font.woff',
			'error' => 0,
			'size' => 1024,
		];

		// Create a test font file
		file_put_contents( $test_file['tmp_name'], 'test font content' );

		// Mock POST data
		$_POST = [
			'_wpnonce' => wp_create_nonce( 'pb_save_custom_fonts' ),
			'font_name' => 'Test Font',
			'font_fallback' => 'sans-serif',
		];

		$_FILES = [
			'font_file_regular' => $test_file,
		];

		// Mock wp_safe_redirect to prevent actual redirect
		global $wp_actions;
		$wp_actions['wp_redirect'] = 1;

		// Capture any output
		ob_start();
		\Pressbooks\Admin\CustomFonts\handle_form_submission();
		$output = ob_get_clean();

		// Check that directory was created
		$this->assertDirectoryExists( $upload_dir );
	}

	/**
	 * Helper method to recursively remove directory
	 */
	private function _rmdir( $dir ) {
		if ( is_dir( $dir ) ) {
			$objects = scandir( $dir );
			foreach ( $objects as $object ) {
				if ( $object != "." && $object != ".." ) {
					if ( is_dir( $dir . "/" . $object ) ) {
						$this->_rmdir( $dir . "/" . $object );
					} else {
						unlink( $dir . "/" . $object );
					}
				}
			}
			rmdir( $dir );
		}
	}
}