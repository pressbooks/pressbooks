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

        // Initialize ServiceProvider to ensure Blade is available
        ServiceProvider::init();

		// Create a temporary directory for font uploads
		$this->test_font_dir = $this->_createTmpDir() . '/custom-fonts/';
		wp_mkdir_p( $this->test_font_dir );

		// Store original upload directory
		$this->original_upload_dir = WP_CONTENT_DIR . '/uploads/assets/custom-fonts/';
	}

	/**
	 * Tear down test environment
	 */
	public function tear_down() {
        // Clean up global state
        $_POST = [];
        $_FILES = [];
        $_GET = [];

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
 * @dataProvider fontProvider
 */
public function test_font_upload( string $file_name, string $key ) {
    // Create a temp file for the upload
    $tmp_file = tempnam(sys_get_temp_dir(), 'font_');

    // Fill it with dummy content
    file_put_contents($tmp_file, 'dummy font data');

    // Build the $file array like PHP would from $_FILES
    $file = [
        'name'     => $file_name,
        'tmp_name' => $tmp_file,
        // optional fields if other code paths rely on them:
        'type'     => 'application/octet-stream',
        'error'    => 0,
        'size'     => filesize($tmp_file),
    ];

    // Unique target dir for this test run
    $target_dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'pressbooks_fonts_' . uniqid() . DIRECTORY_SEPARATOR;

    if ( ! is_dir( $target_dir ) ) {
        mkdir( $target_dir, 0755, true );
    }

    try {
        $result = \Pressbooks\Admin\CustomFonts\handle_uploaded_font( $file, $key, $target_dir );

        // Not a WP_Error
        $this->assertNotInstanceOf( \WP_Error::class, $result, 'Font upload failed for ' . $key );

        // Should be an array with expected keys
        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'file', $result );
        $this->assertArrayHasKey( 'variation', $result );
        $this->assertEquals( $key, $result['variation'] );

        // The handler returns a URL, but the file itself was written to $target_dir.
        // Verify the physical file exists using the basename from the returned file.
        $written_file = $target_dir . basename( $result['file'] );
        $this->assertFileExists( $written_file, 'Uploaded file was not written to target dir' );
    } finally {
        // Clean up temp file
        if ( file_exists( $tmp_file ) ) {
            @unlink( $tmp_file );
        }
        // Clean up target file and dir
        $written_file = isset($written_file) ? $written_file : null;
        if ( $written_file && file_exists( $written_file ) ) {
            @unlink( $written_file );
        }
        if ( is_dir( $target_dir ) ) {
            @rmdir( $target_dir );
        }
    }
}

public function fontProvider(): array {
    return [
        'regular' => [ 'regular.woff', 'regular' ],
        'bold'    => [ 'bold.woff', 'bold' ],
        'italic'  => [ 'italic.ttf', 'italic' ],
        // add more cases if desired
    ];
}
    /**
     * Test reader_custom_fonts_page renders correctly with mocked data
     */
    public function test_render_custom_fonts_page_with_data() {
        // Set up test fonts with multiple variations
        $test_fonts = [
            'arial-custom' => [
                'name' => 'Arial Custom',
                'fallback' => 'sans-serif',
                'files' => [
                    'regular' => [
                        'file' => 'http://example.com/arial.woff',
                        'variation' => 'regular',
                    ],
                    'bold' => [
                        'file' => 'http://example.com/arial-bold.woff',
                        'variation' => 'bold',
                    ],
                    'italic' => [
                        'file' => 'http://example.com/arial-italic.woff',
                        'variation' => 'italic',
                    ],
                    'bold_italic' => [
                        'file' => 'http://example.com/arial-bold-italic.woff',
                        'variation' => 'bold_italic',
                    ],
                ],
            ],
            'times-custom' => [
                'name' => 'Times Custom',
                'fallback' => 'serif',
                'files' => [
                    'regular' => [
                        'file' => 'http://example.com/times.woff2',
                        'variation' => 'regular',
                    ],
                ],
            ],
        ];
        update_site_option('pressbooks_custom_fonts', $test_fonts);

        // Capture output
        ob_start();
        \Pressbooks\Admin\CustomFonts\render_custom_fonts_page();
        $output = ob_get_clean();

        // Basic structure tests
        $this->assertIsString($output);
        $this->assertNotEmpty($output, 'Output should not be empty');

        // Test for PHP errors
        $this->assertStringNotContainsString('Fatal error', $output);
        $this->assertStringNotContainsString('Warning:', $output);
        $this->assertStringNotContainsString('Notice:', $output);

        // Test main page structure based on template
        $this->assertStringContainsString('<div class="wrap">', $output, 'Should contain wrap div');
        $this->assertStringContainsString('<h1>Upload Custom Font</h1>', $output, 'Should contain main heading');
        $this->assertStringContainsString('Upload custom font files for any additional font families', $output, 'Should contain instructions');

        // Test form structure
        $this->assertStringContainsString('<form method="post" enctype="multipart/form-data"', $output, 'Should contain upload form');
        $this->assertStringContainsString('name="action" value="pb_save_custom_fonts"', $output, 'Should have correct action');
        $this->assertStringContainsString('name="_wpnonce"', $output, 'Should include nonce field');

        // Test form fields
        $this->assertStringContainsString('name="font_name"', $output, 'Should have font name field');
        $this->assertStringContainsString('name="font_file_regular"', $output, 'Should have regular font file field');
        $this->assertStringContainsString('name="font_file_bold"', $output, 'Should have bold font file field');
        $this->assertStringContainsString('name="font_file_italic"', $output, 'Should have italic font file field');
        $this->assertStringContainsString('name="font_file_bold_italic"', $output, 'Should have bold italic font file field');
        $this->assertStringContainsString('name="font_fallback"', $output, 'Should have fallback select');

        // Test file input accepts
        $this->assertStringContainsString('accept=".woff,.woff2,.ttf,.otf"', $output, 'Should have correct file accepts');

        $this->assertStringContainsString('name="font_fallback"', $output, 'Should have fallback radios');
        $this->assertStringContainsString('<input type="radio" name="font_fallback" id="font_fallback_sans" value="sans-serif"', $output, 'Should have sans-serif radio');
        $this->assertStringContainsString('<input type="radio" name="font_fallback" id="font_fallback_serif" value="serif"', $output, 'Should have serif radio');
        $this->assertStringContainsString('Sans-serif', $output, 'Should show label for sans-serif');
        $this->assertStringContainsString('Serif', $output, 'Should show label for serif');

        // Test "Registered Fonts" section appears when fonts exist
        $this->assertStringContainsString('<h2>Registered Fonts</h2>', $output, 'Should show registered fonts heading');
        $this->assertStringContainsString('<table class="widefat fixed striped">', $output, 'Should contain fonts table');

        // Test table headers
        $this->assertStringContainsString('<th>Font Family Name</th>', $output, 'Should have font family header');
        $this->assertStringContainsString('<th>Font Variants</th>', $output, 'Should have font variants header');
        $this->assertStringContainsString('<th>Font Fallback</th>', $output, 'Should have font fallback header');

        // Test actual font data appears in table
        $this->assertStringContainsString('Arial Custom', $output, 'Should display Arial Custom font name');
        $this->assertStringContainsString('Times Custom', $output, 'Should display Times Custom font name');

        // Test fallback values appear
        $this->assertStringContainsString('<td>sans-serif</td>', $output, 'Should display sans-serif fallback');
        $this->assertStringContainsString('<td>serif</td>', $output, 'Should display serif fallback');
    }

    /**
     * Test render_custom_fonts_page with no fonts (empty state)
     */
    public function test_render_custom_fonts_page_empty_state() {
        // Ensure no fonts are set
        delete_site_option('pressbooks_custom_fonts');

        ob_start();
        \Pressbooks\Admin\CustomFonts\render_custom_fonts_page();
        $output = ob_get_clean();

        // Should still render form and basic structure
        $this->assertStringContainsString('<div class="wrap">', $output);
        $this->assertStringContainsString('<h1>Upload Custom Font</h1>', $output);
        $this->assertStringContainsString('<form method="post"', $output);

        // Should NOT show registered fonts section when empty
        $this->assertStringNotContainsString('<h2>Registered Fonts</h2>', $output, 'Should not show registered fonts heading when empty');
        $this->assertStringNotContainsString('<table class="widefat fixed striped">', $output, 'Should not show fonts table when empty');
    }

    /**
     * Test success message display
     */
    public function test_render_custom_fonts_page_success_message() {
        // Simulate the updated=true parameter
        $_GET['updated'] = 'true';

        ob_start();
        \Pressbooks\Admin\CustomFonts\render_custom_fonts_page();
        $output = ob_get_clean();

        // Should show success notice
        $this->assertStringContainsString('<div class="notice notice-success is-dismissible">', $output, 'Should show success notice');
        $this->assertStringContainsString('Font uploaded successfully.', $output, 'Should show success message');

        // Clean up
        unset($_GET['updated']);
    }

    /**
     * Test XSS protection in font data
     */
    public function test_render_custom_fonts_page_xss_protection() {
        // Set up malicious font data
        $malicious_fonts = [
            'xss-test' => [
                'name' => '<script>alert("xss")</script>Evil Font',
                'fallback' => '<script>alert("fallback")</script>',
                'files' => [
                    'regular' => [
                        'file' => 'javascript:alert("xss")',
                        'variation' => 'regular',
                    ],
                ],
            ],
        ];
        update_site_option('pressbooks_custom_fonts', $malicious_fonts);

        ob_start();
        \Pressbooks\Admin\CustomFonts\render_custom_fonts_page();
        $output = ob_get_clean();

        // Should escape malicious content
        $this->assertStringNotContainsString('<script>alert("xss")</script>', $output, 'Should escape script tags in font name');
        $this->assertStringNotContainsString('<script>alert("fallback")</script>', $output, 'Should escape script tags in fallback');
        $this->assertStringNotContainsString('javascript:alert("xss")', $output, 'Should escape javascript URLs');

        // But should still show the safe parts
        $this->assertStringContainsString('Evil Font', $output, 'Should show safe parts of font name');
    }

	/**
	 * Test handle_form_submission with valid data
	 */
	public function test_handle_form_submission_valid_data() {
		// Create a super admin user
		$user_id = $this->createSuperAdminUser();
		wp_set_current_user( $user_id );

        // Mock wp_safe_redirect to prevent actual redirect
        add_filter( 'wp_redirect', function( $location, $status ) {
            // Just return false to prevent redirect
            return false;
        }, 10, 2 );

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
        $user_id = $this->createSuperAdminUser();
        wp_set_current_user( $user_id );

        $_POST = $this->create_valid_post_data();
        $_POST['_wpnonce'] = 'invalid-nonce';

        $this->expectException( WPDieException::class );
        $this->expectExceptionMessage( 'Permission denied' );

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

		// Expect wp_die to be called and converted to WPDieException by the test harness
		$this->expectException( WPDieException::class );
		$this->expectExceptionMessage( 'Permission denied' );
		\Pressbooks\Admin\CustomFonts\handle_form_submission();
	}

	/**
	 * Return a minimal valid POST payload for handle_form_submission tests.
	 */
	private function create_valid_post_data(): array {
		return [
			'_wpnonce' => wp_create_nonce( 'pb_save_custom_fonts' ),
			'font_name' => 'Test Font',
			'font_fallback' => 'sans-serif',
		];
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

        $this->expectException('WPDieException');
        $this->expectExceptionMessage('Font upload failed for regular');

        \Pressbooks\Admin\CustomFonts\handle_form_submission();
    }



	/**
	 * Test handle_uploaded_font with invalid file type (uses temp file)
	 */
	public function test_handle_uploaded_font_invalid_file_type() {
		$tmp_file = tempnam(sys_get_temp_dir(), 'font_');
		file_put_contents($tmp_file, 'not a font');

		$test_file = [
			'name' => 'test-font.txt',
			'tmp_name' => $tmp_file,
			'type' => 'text/plain',
			'error' => 0,
			'size' => filesize($tmp_file),
		];

		try {
			$result = \Pressbooks\Admin\CustomFonts\handle_uploaded_font( $test_file, 'regular', sys_get_temp_dir() . DIRECTORY_SEPARATOR );

			$this->assertInstanceOf( '\\WP_Error', $result );
			$this->assertEquals( 'invalid_type', $result->get_error_code() );
			$this->assertEquals( 'Invalid font file type.', $result->get_error_message() );
		} finally {
			if ( file_exists( $tmp_file ) ) {
				@unlink( $tmp_file );
			}
		}
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

        // Capture any unexpected output
        ob_start();
        $result = \Pressbooks\Admin\CustomFonts\handle_uploaded_font( $test_file, 'regular', $this->test_font_dir );
        $output = ob_get_clean();

        // Assert no output was produced
        $this->assertEmpty($output, 'Unexpected output: ' . $output);

        $this->assertInstanceOf( '\WP_Error', $result );
        $this->assertEquals( 'upload_failed', $result->get_error_code() );
        $this->assertEquals( 'Font upload failed for regular', $result->get_error_message() );
	}

    /**
     * Data provider for font file extensions
     */
    public function font_extension_provider() {
        return [
            'woff' => [ 'woff', 'font/woff' ],
            'woff2' => [ 'woff2', 'font/woff2' ],
            'ttf' => [ 'ttf', 'font/ttf' ],
            'otf' => [ 'otf', 'font/otf' ],
        ];
    }

    /**
     * Test handle_uploaded_font with different file extensions
     * @dataProvider font_extension_provider
     */
    public function test_handle_uploaded_font_different_extensions( $extension, $mime_type ) {
        $test_file = [
            'name' => "test-font.{$extension}",
            'type' => $mime_type,
            'tmp_name' => $this->test_font_dir . "test-font.{$extension}",
            'error' => 0,
            'size' => 1024,
        ];

        // Create a test font file
        file_put_contents( $test_file['tmp_name'], 'test font content' );

        $result = \Pressbooks\Admin\CustomFonts\handle_uploaded_font( $test_file, 'regular', $this->test_font_dir );

        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'file', $result );
        $this->assertStringContainsString( ".{$extension}", $result['file'] );
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

		$css_path = $upload_dir . 'custom-fonts.css';
		$this->assertFileExists( $css_path );

		$css_content = file_get_contents( $css_path );
		$this->assertStringContainsString( "@font-face", $css_content );
		$this->assertStringContainsString( "font-family: 'Test Font'", $css_content );
		$this->assertStringContainsString( "font-style: normal", $css_content );
		$this->assertStringContainsString( "font-weight: normal", $css_content );
		$this->assertStringContainsString( "font-style: italic", $css_content );
		$this->assertStringContainsString( "font-weight: bold", $css_content );
		$this->assertStringContainsString( "http://example.com/test-font", $css_content );
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

		$css_path = $upload_dir . 'custom-fonts.css';
		$this->assertFileExists( $css_path );

		$css_content = file_get_contents( $css_path );
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

		$css_path = $upload_dir . 'custom-fonts.css';
		$this->assertFileExists( $css_path );

		$css_content = file_get_contents( $css_path );
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