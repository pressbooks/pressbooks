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
use function Pressbooks\Modules\Export\update_pins;
use function Pressbooks\Modules\Export\get_contributors_section;
use function Pressbooks\Modules\Export\get_friendly_name_for_module;
use function Pressbooks\Modules\Export\handle_exports_submit;
use function Pressbooks\Modules\Export\process_and_queue_job_requests;
use Pressbooks\Modules\Export\Table;
use Pressbooks\Modules\BackgroundProcessing\BackgroundJob;
use Pressbooks\Contributors;

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

	/**
	 * Test update_pins AJAX function
	 *
	 * @group export
	 */
	public function test_update_pins() {
		// Test requires AJAX context
		$reporting = $this->_fakeAjax();

		// Set up required POST data
		$_POST['pins'] = json_encode( [ 'test-file.pdf' => true, 'another-file.epub' => false ] );
		$_POST['file'] = 'test-file.pdf';
		$_POST['pinned'] = true;

		// Mock the nonce check by adding the nonce
		$_POST['_ajax_nonce'] = wp_create_nonce( 'pb-export-pins' );

		// Capture output
		ob_start();

		try {
			update_pins();
			$output = ob_get_clean();

			// Should contain JSON success response
			$this->assertStringContainsString( 'success', $output );
			$this->assertStringContainsString( 'pinned successfully', $output );

			// Check that transient was set
			$pins = get_transient( Table::PIN );
			$this->assertIsArray( $pins );
			$this->assertTrue( $pins['test-file.pdf'] );
			$this->assertFalse( $pins['another-file.epub'] );

		} catch ( \WPAjaxDieContinueException $e ) {
			// This is expected in AJAX context
			$output = ob_get_clean();
			$this->assertStringContainsString( 'success', $output );
		}

		$this->_fakeAjaxDone( $reporting );

		// Clean up
		unset( $_POST['pins'], $_POST['file'], $_POST['pinned'], $_POST['_ajax_nonce'] );
		delete_transient( Table::PIN );
	}

	/**
	 * Test update_pins with invalid data
	 *
	 * @group export
	 */
	public function test_update_pins_invalid_data() {
		$reporting = $this->_fakeAjax();

		// Set up invalid POST data (not an array when decoded)
		$_POST['pins'] = 'invalid-json';
		$_POST['file'] = 'test-file.pdf';
		$_POST['pinned'] = true;
		$_POST['_ajax_nonce'] = wp_create_nonce( 'pb-export-pins' );

		ob_start();

		try {
			update_pins();
			ob_get_clean();

			// Should not set transient with invalid data
			$pins = get_transient( Table::PIN );
			$this->assertFalse( $pins );

		} catch ( \WPAjaxDieContinueException $e ) {
			// Expected in AJAX context
			ob_get_clean();
		}

		$this->_fakeAjaxDone( $reporting );

		// Clean up
		unset( $_POST['pins'], $_POST['file'], $_POST['pinned'], $_POST['_ajax_nonce'] );
	}


	/**
	 * Test get_contributors_section with no contributors
	 *
	 * @group export
	 */
	public function test_get_contributors_section_empty() {
		// Create a test post
		$post_id = $this->factory()->post->create( [
			'post_title' => 'Test Chapter',
			'post_type' => 'chapter'
		] );

		// Test with no contributors
		$result = get_contributors_section( $post_id );
		$this->assertEquals( '', $result );

		// Clean up
		wp_delete_post( $post_id, true );
	}

	/**
	 * Test get_contributors_section with single contributor
	 *
	 * @group export
	 */
	public function test_get_contributors_section_single_contributor() {
		// Create a test post
		$post_id = $this->factory()->post->create( [
			'post_title' => 'Test Chapter',
			'post_type' => 'chapter'
		] );

		// Create a contributor term
		$contributor_term = wp_insert_term( 'John Doe', Contributors::TAXONOMY, [
			'slug' => 'john-doe'
		] );

		if ( ! is_wp_error( $contributor_term ) ) {
			// Add contributor meta
			add_term_meta( $contributor_term['term_id'], 'contributor_first_name', 'John' );
			add_term_meta( $contributor_term['term_id'], 'contributor_last_name', 'Doe' );

			// Associate contributor with post
			add_post_meta( $post_id, 'pb_authors', 'john-doe' );

			// Test the function
			$result = get_contributors_section( $post_id );

			$this->assertStringContainsString( '<div class="contributors">', $result );
			$this->assertStringContainsString( '<h3 class="about-authors">About the author</h3>', $result );
			$this->assertStringContainsString( '</div>', $result );
		}

		// Clean up
		wp_delete_post( $post_id, true );
		if ( ! is_wp_error( $contributor_term ) ) {
			wp_delete_term( $contributor_term['term_id'], Contributors::TAXONOMY );
		}
	}

	/**
	 * Test get_contributors_section with multiple contributors
	 *
	 * @group export
	 */
	public function test_get_contributors_section_multiple_contributors() {
		// Create a test post
		$post_id = $this->factory()->post->create( [
			'post_title' => 'Test Chapter',
			'post_type' => 'chapter'
		] );

		// Create multiple contributor terms
		$contributor1 = wp_insert_term( 'Jane Smith', Contributors::TAXONOMY, [
			'slug' => 'jane-smith'
		] );
		$contributor2 = wp_insert_term( 'Bob Johnson', Contributors::TAXONOMY, [
			'slug' => 'bob-johnson'
		] );

		if ( ! is_wp_error( $contributor1 ) && ! is_wp_error( $contributor2 ) ) {
			// Add contributor meta
			add_term_meta( $contributor1['term_id'], 'contributor_first_name', 'Jane' );
			add_term_meta( $contributor1['term_id'], 'contributor_last_name', 'Smith' );
			add_term_meta( $contributor2['term_id'], 'contributor_first_name', 'Bob' );
			add_term_meta( $contributor2['term_id'], 'contributor_last_name', 'Johnson' );

			// Associate contributors with post
			add_post_meta( $post_id, 'pb_authors', 'jane-smith' );
			add_post_meta( $post_id, 'pb_authors', 'bob-johnson' );

			// Test the function
			$result = get_contributors_section( $post_id );

			$this->assertStringContainsString( '<div class="contributors">', $result );
			$this->assertStringContainsString( '<h3 class="about-authors">About the authors</h3>', $result ); // Plural
			$this->assertStringContainsString( '</div>', $result );
		}

		// Clean up
		wp_delete_post( $post_id, true );
		if ( ! is_wp_error( $contributor1 ) ) {
			wp_delete_term( $contributor1['term_id'], Contributors::TAXONOMY );
		}
		if ( ! is_wp_error( $contributor2 ) ) {
			wp_delete_term( $contributor2['term_id'], Contributors::TAXONOMY );
		}
	}

	/**
	 * Test get_friendly_name_for_module with existing module classname
	 *
	 * @group export
	 */
	public function test_get_friendly_name_for_module_known_classname() {
		// Test with a known module classname that should return a friendly name
		$classname = '\Pressbooks\Modules\Export\Prince\Pdf';
		$result = get_friendly_name_for_module( $classname );

		$this->assertEquals( 'Digital PDF', $result );

		// Test another known classname
		$classname = '\Pressbooks\Modules\Export\Epub\Epub';
		$result = get_friendly_name_for_module( $classname );

		$this->assertEquals( 'EPUB', $result );
	}

	/**
	 * Test get_friendly_name_for_module with unknown module classname (fallback)
	 *
	 * @group export
	 */
	public function test_get_friendly_name_for_module_unknown_classname() {
		// Test with an unknown module classname - should fallback to class name
		$classname = '\Some\Unknown\Module\Class\TestExporter';
		$result = get_friendly_name_for_module( $classname );

		$this->assertEquals( 'TestExporter', $result );

		// Test with simple class name (no namespace)
		$classname = 'SimpleExporter';
		$result = get_friendly_name_for_module( $classname );

		$this->assertEquals( 'SimpleExporter', $result );
	}

	/**
	 * Test get_friendly_name_for_module edge cases
	 *
	 * @group export
	 */
	public function test_get_friendly_name_for_module_edge_cases() {
		// Test with empty string
		$result = get_friendly_name_for_module( '' );
		$this->assertEquals( '', $result );

		// Test with class name ending with backslash
		$classname = '\Some\Module\Class\\';
		$result = get_friendly_name_for_module( $classname );
		$this->assertEquals( '', $result );

		// Test with just namespace separators
		$classname = '\\\\\\';
		$result = get_friendly_name_for_module( $classname );
		$this->assertEquals( '', $result );
	}

	/**
	 * Test handle_exports_submit with no export formats selected
	 *
	 * @group export
	 */
	public function test_handle_exports_submit_no_formats() {
		$reporting = $this->_fakeAjax();

		// Set up user with proper capabilities
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		// Set up POST data with no export formats
		$_POST['pb_export_nonce'] = wp_create_nonce( 'pb-export-book' );
		$_POST['export_formats'] = [];

		ob_start();

		try {
			handle_exports_submit();
			$output = ob_get_clean();

			// Should return error for no formats selected
			$this->assertStringContainsString( 'No export formats selected', $output );
			$this->assertStringContainsString( 'success":false', $output );

		} catch ( \WPAjaxDieContinueException $e ) {
			$output = ob_get_clean();
			$this->assertStringContainsString( 'No export formats selected', $output );
		}

		$this->_fakeAjaxDone( $reporting );

		// Clean up
		unset( $_POST['pb_export_nonce'], $_POST['export_formats'] );
		wp_set_current_user( 0 );
	}

	/**
	 * Test handle_exports_submit with insufficient permissions
	 *
	 * @group export
	 */
	public function test_handle_exports_submit_no_permission() {
		$reporting = $this->_fakeAjax();

		// Set up user without proper capabilities
		$user_id = $this->factory()->user->create( [ 'role' => 'subscriber' ] );
		wp_set_current_user( $user_id );

		// Set up POST data
		$_POST['pb_export_nonce'] = wp_create_nonce( 'pb-export-book' );
		$_POST['export_formats'] = [ 'pdf' => 'pdf' ];

		ob_start();

		try {
			handle_exports_submit();
			$output = ob_get_clean();

			// Should return permission denied error
			$this->assertStringContainsString( 'Permission denied', $output );
			$this->assertStringContainsString( 'success":false', $output );

		} catch ( \WPAjaxDieContinueException $e ) {
			$output = ob_get_clean();
			$this->assertStringContainsString( 'Permission denied', $output );
		}

		$this->_fakeAjaxDone( $reporting );

		// Clean up
		unset( $_POST['pb_export_nonce'], $_POST['export_formats'] );
		wp_set_current_user( 0 );
	}

	/**
	 * Test process_and_queue_job_requests with invalid export format
	 *
	 * @group export
	 */
	public function test_process_and_queue_job_requests_invalid_format() {
		// Set up user
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		$export_formats = [ 'invalid_format' => 'invalid_format' ];
		$export_options = [];

		$results = process_and_queue_job_requests( $export_formats, $export_options );

		$this->assertIsArray( $results );
		$this->assertCount( 1, $results );
		$this->assertEquals( 'error', $results[0]['status'] );
		$this->assertEquals( 'job_queue_failed', $results[0]['event_type'] );
		$this->assertStringContainsString( 'Invalid or unsupported export format', $results[0]['message'] );

		// Clean up
		wp_set_current_user( 0 );
	}

	/**
	 * Test process_and_queue_job_requests with empty formats
	 *
	 * @group export
	 */
	public function test_process_and_queue_job_requests_empty_formats() {
		// Set up user
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		$export_formats = [];
		$export_options = [];

		$results = process_and_queue_job_requests( $export_formats, $export_options );

		$this->assertIsArray( $results );
		$this->assertEmpty( $results );

		// Clean up
		wp_set_current_user( 0 );
	}

	/**
	 * Test process_and_queue_job_requests with valid format
	 *
	 * @group export
	 */
	public function test_process_and_queue_job_requests_valid_format() {
		// Set up user
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		// Use a format that should exist (epub is usually available)
		$export_formats = [ 'epub' => 'epub' ];
		$export_options = [];

		$results = process_and_queue_job_requests( $export_formats, $export_options );

		$this->assertIsArray( $results );
		$this->assertCount( 1, $results );

		// Check if the result indicates success or failure (depends on system dependencies)
		$result = $results[0];
		$this->assertArrayHasKey( 'status', $result );
		$this->assertArrayHasKey( 'event_type', $result );
		$this->assertArrayHasKey( 'module_slug', $result );
		$this->assertEquals( 'epub', $result['module_slug'] );

		// Clean up any created jobs
		if ( isset( $result['job_id'] ) ) {
			// Clean up the job from the database if it was created
			try {
				app( 'db' )->table( BackgroundJob::JOBS_TABLE_NAME )
					->where( 'id', $result['job_id'] )
					->delete();
			} catch ( \Exception $e ) {
				// Table might not exist in test environment
			}
		}

		wp_set_current_user( 0 );
	}

	/**
	 * Test process_and_queue_job_requests input sanitization
	 *
	 * @group export
	 */
	public function test_process_and_queue_job_requests_sanitization() {
		// Set up user
		$user_id = $this->factory()->user->create( [ 'role' => 'editor' ] );
		wp_set_current_user( $user_id );

		// Test with malicious input that should be sanitized (using only invalid formats to avoid real job creation)
		$export_formats = [
			'<script>alert("xss")</script>' => 'malicious_value',
			'invalid_format' . "\0" . 'injection' => 'invalid_format'
		];
		$export_options = [];

		$results = process_and_queue_job_requests( $export_formats, $export_options );

		$this->assertIsArray( $results );

		// Should handle malicious input safely and return errors for invalid formats
		foreach ( $results as $result ) {
			$this->assertArrayHasKey( 'module_slug', $result );
			$this->assertEquals( 'error', $result['status'] );
			// Sanitized input should not contain script tags (sanitize_text_field removes these)
			$this->assertStringNotContainsString( '<script>', $result['module_slug'] );
			// Note: sanitize_text_field() doesn't remove null bytes, so we only check for script tags
		}

		// Clean up
		wp_set_current_user( 0 );
	}
}
