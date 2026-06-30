<?php

use Pressbooks\Modules\Import\GoogleDocs\GoogleDocs;
use Pressbooks\Modules\Import\GoogleDocs\DocsMapper;
use Pressbooks\Modules\Import\GoogleDocs\GlossaryParser;

class Modules_ImportGoogleDocsImporterTest extends \WP_UnitTestCase {
	use utilsTrait;

	/**
	 * @var string Path to the headings-only fixture
	 */
	private string $fixture_path;

	public function set_up(): void {
		parent::set_up();
		$this->fixture_path = __DIR__ . '/fixtures/google-docs/headings-only.json';
	}

	public function tear_down(): void {
		delete_option( 'pressbooks_current_import' );
		parent::tear_down();
	}

	/**
	 * @group import
	 */
	public function test_set_current_import_option_returns_false_for_missing_file(): void {
		$importer = new GoogleDocs();
		$result = $importer->setCurrentImportOption( [ 'file' => '/nonexistent/file.json' ] );
		$this->assertFalse( $result );
	}

	/**
	 * @group import
	 */
	public function test_set_current_import_option_returns_false_for_invalid_json(): void {
		$tmp = tempnam( sys_get_temp_dir(), 'pb_test_' );
		file_put_contents( $tmp, 'not valid json' );

		$importer = new GoogleDocs();
		$result = $importer->setCurrentImportOption( [ 'file' => $tmp ] );
		$this->assertFalse( $result );

		unlink( $tmp );
	}

	/**
	 * @group import
	 */
	public function test_set_current_import_option_returns_false_for_empty_body(): void {
		$tmp = tempnam( sys_get_temp_dir(), 'pb_test_' );
		file_put_contents( $tmp, json_encode( [ 'title' => 'Empty', 'body' => [ 'content' => [] ] ] ) );

		$importer = new GoogleDocs();
		$result = $importer->setCurrentImportOption( [ 'file' => $tmp ] );
		$this->assertFalse( $result );

		unlink( $tmp );
	}

	/**
	 * @group import
	 */
	public function test_set_current_import_option_happy_path(): void {
		$importer = new GoogleDocs();
		$result = $importer->setCurrentImportOption( [ 'file' => $this->fixture_path ] );
		$this->assertTrue( $result );

		$option = get_option( 'pressbooks_current_import' );
		$this->assertIsArray( $option );
		$this->assertSame( $this->fixture_path, $option['file'] );
		$this->assertSame( 'application/json', $option['file_type'] );
		$this->assertSame( 'google-docs', $option['type_of'] );
		$this->assertArrayHasKey( 'chapters', $option );
		$this->assertSame( 'Chapter One', $option['chapters'][0] );
		$this->assertSame( 'Chapter Two', $option['chapters'][1] );
		$this->assertCount( 2, $option['chapters'] );
	}

	/**
	 * @group import
	 */
	public function test_import_returns_false_for_missing_file(): void {
		$importer = new GoogleDocs();
		$result = $importer->import( [ 'file' => '/nonexistent/file.json', 'chapters' => [] ] );
		$this->assertFalse( $result );
	}

	/**
	 * @group import
	 */
	public function test_import_returns_false_for_empty_json(): void {
		$tmp = tempnam( sys_get_temp_dir(), 'pb_test_' );
		file_put_contents( $tmp, '' );

		$importer = new GoogleDocs();
		$result = $importer->import( [ 'file' => $tmp, 'chapters' => [] ] );
		$this->assertFalse( $result );

		@unlink( $tmp );
	}

	/**
	 * @group import
	 */
	public function test_import_creates_chapters(): void {
		$this->_book();

		// Copy fixture so revokeCurrentImport can delete it
		$tmp = tempnam( sys_get_temp_dir(), 'pb_test_' );
		copy( $this->fixture_path, $tmp );

		$importer = new GoogleDocs();
		$importer->setCurrentImportOption( [ 'file' => $tmp ] );
		$current_import = get_option( 'pressbooks_current_import' );

		// Simulate form POST: select both chapters for import as 'chapter' type
		$_POST['chapters'] = [
			0 => [ 'import' => '1', 'type' => 'chapter' ],
			1 => [ 'import' => '1', 'type' => 'chapter' ],
		];

		$result = $importer->import( $current_import );
		$this->assertTrue( $result );

		// Verify posts were created
		$chapters = get_posts( [
			'post_type'   => 'chapter',
			'post_status' => 'any',
			'numberposts' => -1,
			'orderby'     => 'date',
			'order'       => 'ASC',
		] );

		// Should have at least the 2 we imported (plus any from _book())
		$imported_titles = wp_list_pluck( $chapters, 'post_title' );
		$this->assertContains( 'Chapter One', $imported_titles );
		$this->assertContains( 'Chapter Two', $imported_titles );

		// Verify revokeCurrentImport cleared the option
		$this->assertFalse( get_option( 'pressbooks_current_import' ) );

		unset( $_POST['chapters'] );
	}

	/**
	 * @group import
	 */
	public function test_import_skips_unselected_chapters(): void {
		$this->_book();

		$tmp = tempnam( sys_get_temp_dir(), 'pb_test_' );
		copy( $this->fixture_path, $tmp );

		$importer = new GoogleDocs();
		$importer->setCurrentImportOption( [ 'file' => $tmp ] );
		$current_import = get_option( 'pressbooks_current_import' );

		// Only select chapter 0
		$_POST['chapters'] = [
			0 => [ 'import' => '1', 'type' => 'chapter' ],
		];

		$before = get_posts( [ 'post_type' => 'chapter', 'post_status' => 'any', 'numberposts' => -1 ] );
		$result = $importer->import( $current_import );
		$this->assertTrue( $result );

		$after = get_posts( [ 'post_type' => 'chapter', 'post_status' => 'any', 'numberposts' => -1 ] );
		$new_count = count( $after ) - count( $before );
		$this->assertSame( 1, $new_count );

		$imported_titles = wp_list_pluck( $after, 'post_title' );
		$this->assertContains( 'Chapter One', $imported_titles );
		$this->assertNotContains( 'Chapter Two', $imported_titles );

		unset( $_POST['chapters'] );
	}

	/**
	 * @group import
	 */
	public function test_process_images_returns_html_unchanged_without_fetcher(): void {
		$importer = new GoogleDocs();

		$reflection = new \ReflectionMethod( $importer, 'processImages' );
		$reflection->setAccessible( true );

		$html = '<p>Text <img src="#gdoc-image-kix.img1" alt="test" /></p>';
		$images = [
			[ 'object_id' => 'kix.img1', 'content_uri' => 'https://example.com/img.png', 'alt' => 'test', 'title' => '' ],
		];

		$result = $reflection->invoke( $importer, $html, $images );
		// Without a fetcher, images are not processed
		$this->assertSame( $html, $result );
	}

	/**
	 * @group import
	 */
	public function test_get_import_warnings_empty_by_default(): void {
		$importer = new GoogleDocs();
		$this->assertSame( [], $importer->getImportWarnings() );
	}

	/**
	 * @group import
	 */
	public function test_import_adds_warning_when_no_oauth(): void {
		$this->_book();

		$tmp = tempnam( sys_get_temp_dir(), 'pb_test_' );
		copy( $this->fixture_path, $tmp );

		$importer = new GoogleDocs();
		$importer->setCurrentImportOption( [ 'file' => $tmp ] );
		$current_import = get_option( 'pressbooks_current_import' );

		$_POST['chapters'] = [
			0 => [ 'import' => '1', 'type' => 'chapter' ],
		];

		$importer->import( $current_import );
		$warnings = $importer->getImportWarnings();
		// Should have a warning about Google auth failing (no credentials configured)
		$this->assertNotEmpty( $warnings );
		$this->assertStringContainsString( 'Could not authenticate', $warnings[0] );

		unset( $_POST['chapters'] );
	}

	/**
	 * @group import
	 */
	public function test_type_of_constant(): void {
		$this->assertSame( 'google-docs', GoogleDocs::TYPE_OF );
	}

	/**
	 * @group import
	 */
	public function test_import_creates_and_links_glossary_terms(): void {
		$this->_book();

		$tmp = tempnam( sys_get_temp_dir(), 'pb_test_' );
		copy( __DIR__ . '/fixtures/google-docs/with-glossary-terms.json', $tmp );

		$importer = new GoogleDocs();
		$importer->setCurrentImportOption( [ 'file' => $tmp ] );
		$current_import = get_option( 'pressbooks_current_import' );

		$_POST['chapters'] = [
			0 => [ 'import' => '1', 'type' => 'chapter' ],
		];

		$this->assertTrue( $importer->import( $current_import ) );

		// Four glossary terms created: operating system (OS), kernel, daemon, RAM.
		$terms = get_posts( [
			'post_type'   => 'glossary',
			'post_status' => 'any',
			'numberposts' => -1,
		] );
		$by_title = [];
		foreach ( $terms as $t ) {
			$by_title[ strtolower( $t->post_title ) ] = $t;
		}

		$this->assertArrayHasKey( 'operating system (os)', $by_title );
		$this->assertArrayHasKey( 'kernel', $by_title );
		$this->assertArrayHasKey( 'daemon', $by_title );
		$this->assertArrayHasKey( 'ram', $by_title );

		// Definitions.
		$this->assertStringContainsString(
			'manages computer hardware',
			$by_title['operating system (os)']->post_content
		);
		// Multiline definition joined.
		$this->assertStringContainsString( 'The core of an operating system.', $by_title['kernel']->post_content );
		$this->assertStringContainsString( 'It manages system resources.', $by_title['kernel']->post_content );
		// Referenced-but-undefined term has empty definition.
		$this->assertSame( '', trim( $by_title['ram']->post_content ) );
		// Terms are published so they surface in back matter.
		$this->assertSame( 'publish', $by_title['kernel']->post_status );

		// Chapter content: markers replaced, glossary section stripped.
		// Select the imported chapter by title; _book() seeds its own sample
		// chapter, so [0] ordering is not deterministic.
		$chapters = get_posts( [
			'post_type'   => 'chapter',
			'post_status' => 'any',
			'numberposts' => -1,
		] );
		$chapter = null;
		foreach ( $chapters as $candidate ) {
			if ( 'Chapter One' === $candidate->post_title ) {
				$chapter = $candidate;
				break;
			}
		}
		$this->assertNotNull( $chapter );

		$this->assertStringNotContainsString( '[GT]', $chapter->post_content );
		$this->assertStringNotContainsString( '[/GT]', $chapter->post_content );
		$this->assertStringNotContainsString( 'daemon: A background process', $chapter->post_content );
		$this->assertStringNotContainsString( '>Glossary<', $chapter->post_content );

		// Shortcodes reference the created term IDs.
		$os_id = $by_title['operating system (os)']->ID;
		$this->assertStringContainsString(
			'[pb_glossary id="' . $os_id . '"]operating system (OS)[/pb_glossary]',
			$chapter->post_content
		);

		@unlink( $tmp );
	}
}
